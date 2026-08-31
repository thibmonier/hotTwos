# Options techniques et évaluation — Phase d'analyse

**Projet :** HotOnes — refonte ERP agence digitale / ESN
**Source de vérité :** `project-management/cdc/12` (16 ADR) et `cdc/06` (données)
**Date de vérification de la stack :** 2026-08-30 (à **revérifier avant tout engagement** — composants jeunes)

> Particularité de ce projet : **le socle technique est déjà arrêté et documenté** dans le CDC (16 décisions d'architecture). La phase de conception (`/workflow:design`) n'a donc pas à choisir la stack mais à l'instancier et à en instruire les points ouverts (`ARB-25`, provisions de mise au point).

---

## 1. Principe directeur

| Réf | Énoncé |
|---|---|
| `ARC-14` | **Minimiser le nombre de technologies, langages et systèmes à maîtriser et exploiter simultanément.** Toute brique justifie son coût d'exploitation récurrent, pas seulement son intérêt technique. |

Ce principe (couplé à `CTR-1` : équipe 2-4 personnes sans exploitation dédiée) explique la quasi-totalité des choix. La stack est assumée « d'avant-garde » (choix du sponsor), avec un coût de mise au point chiffré (§ 4).

## 2. Stack arrêtée (vérifiée le 2026-08-30)

| Domaine | Retenu | ADR |
|---|---|---|
| Langage | PHP 8.4+ (imposé par Symfony 8.1) | — |
| Cadriciel | Symfony 8.1+, branche stable suivie | `ADR-3` |
| Serveur applicatif | FrankenPHP **en mode worker**, Caddy, supervisé par Ember | `ADR-2` |
| Architecture applicative | Monolithe modulaire, cœur API-first, couche de cas d'usage | `ADR-1` |
| Modélisation | Clean Architecture + DDD **dosés par sous-domaine**, frontières vérifiées (Deptrac) | `ADR-8` |
| Méthode | Développement assisté par agent (**claude-craft**), TDD imposé, conventions versionnées | `ADR-16` |
| Base de données | PostgreSQL + **pgvector** | `ADR-6` |
| Isolation multi-tenant | Discriminant partagé + **RLS** (double barrière) | `ADR-6` |
| API | API Platform 4.3.x en **mode DTO strict** (5.0 alpha exclue) | `ADR-4` |
| Rendu web | Twig + Stimulus + Turbo, rendu serveur par défaut | `ADR-5` |
| Build assets | Symfony **Reprise** (expérimental 0.x) + Vite | `ADR-5` |
| Asynchrone | Bus de messages Symfony, transport base de données au démarrage | `ADR-7` |
| Modèle analytique | **Schéma en étoile physique** dans PostgreSQL, par projection d'événements | `ADR-9` |
| Accès IA | **Symfony AI** (Platform, Agent, Store, AI Bundle, MCP Bundle) + couche produit mince | `ADR-10` |
| Vecteurs | pgvector via le composant Store — aucune base vectorielle dédiée | `ADR-6/10` |
| Dev env | Conteneurisé, **parité worker** dès le développement | `ADR-11` |
| CI/CD | GitHub Actions, **11 étapes bloquantes** | `ADR-12` |
| Staging | Railway Hobby, zone euro, sans données réelles | `ADR-13` |
| Prod | Hébergement UE en services gérés — décision distincte à instruire au lot 2 (`ARB-25`) | `ADR-13` |
| Observabilité | Ember + suivi d'erreurs (palier gratuit UE) + Prometheus/Grafana | `ADR-14` |
| Sécurité | 8 couches outillées (gratuit/libre) + test d'intrusion annuel externe | `ADR-15` |

## 3. Les 16 décisions d'architecture (ADR-*) — en bref

- **ADR-1** Monolithe modulaire, cœur API-first : un déploiement, un dépôt, logique métier dans la couche applicative, 3 adaptateurs (web, API, CLI). Aucun microservice, aucun front découplé.
- **ADR-2** FrankenPHP worker : gain sur `ENF-PERF-2` (< 500 ms P95) mais impose la gestion explicite de l'état inter-requêtes (`ARC-47..50`, `ARC-61`).
- **ADR-3** Suivre la branche stable pendant la construction, se poser sur la prochaine LTS ; tolérance zéro dépréciations (`ARC-51`), Rector dès le lot 1. **Symfony 8.1 hors support janv. 2027.**
- **ADR-4** API Platform 4.3.x en DTO strict, providers/processors sur mesure ; jamais d'exposition d'entités. API interne dès lot 1, API publique contractuelle au lot 3.
- **ADR-5** Rendu serveur Twig + Stimulus + Turbo ; assets via Reprise + Vite ; aucune règle métier réimplémentée côté client (`ARC-27`).
- **ADR-6** PostgreSQL (fenêtrage, vues matérialisées, JSONB, RLS, intervalles pour `INV-2`, pgvector) ; isolation par discriminant + double barrière (filtre ORM `ARC-33` + RLS `ARC-34`).
- **ADR-7** Bus de messages Symfony, transport BD tant que le volume ne l'exige pas ; tout traitement > 3 s asynchrone, messages rejouables et idempotents.
- **ADR-8** Clean Archi + DDD à intensité modulée : complet pour le cœur (Planification, Temps, Finance), intermédiaire pour le support (Projet, Commercial, RH, Recrutement), minimal pour le générique (Référentiels, Pilotage-lecture). **Sourçage d'événements et CQRS généralisé refusés.**
- **ADR-9** Schéma en étoile physique alimenté **exclusivement par projection** → rend `RSQ-5` (divergence des chiffres) structurellement impossible ; garanties `ARC-111..114`.
- **ADR-10** Symfony AI multi-fournisseur (OpenAI, Anthropic, Gemini, Azure, Bedrock, Mistral, Ollama) + couche produit mince (`ARC-73..79`). **Clés d'API fournies par chaque tenant.**
- **ADR-11** Env de dev conteneurisé, même base que la prod (worker exercé dès le dev, `ARC-86`) ; données de test des 3 tailles régénérables ; aucun secret réel au dépôt.
- **ADR-12** GitHub Actions, 11 étapes bloquantes (PHPStan max, Deptrac, style, tests + couverture ≥ 80 %, tests d'isolation multi-tenant, tests worker, zéro dépréciation, audit deps, détection secrets, E2E, perf). Aucune fusion sans chaîne verte.
- **ADR-13** Staging Railway Hobby UE sans données réelles ; prod UE en services gérés ; sauvegardes automatiques testées trimestriellement.
- **ADR-14** Observabilité 4 niveaux (Ember, suivi d'erreurs UE, Prometheus/Grafana, profileur en dev) ; métriques métier au même format que les métriques techniques ; alerte par seuil `ENF-PERF/DISPO`.
- **ADR-15** Défense en profondeur outillée (audit deps, PHPStan max + taint, détecteur de secrets, scanner conteneurs/dynamique, intrusion annuel). **Les deux risques majeurs — fuite inter-tenant et exposition IA — ne sont détectés par aucun analyseur : conception + tests manuels + intrusion humain.**
- **ADR-16** Le développement assisté déplace le goulot d'écriture vers la **relecture/décision** : un test nommé par `RG-*` (`ARC-103`), invariants en base (`ARC-104`), conventions versionnées (`ARC-105`), **périmètre de sécurité non délégué** (`ARC-106`), code livré avec ses tests (`ARC-107`), tests écrits depuis l'exigence (`ARC-108`). Outillage : **claude-craft** (`@the-bearded-bear/claude-craft`).

## 4. Points de fraîcheur et provisions techniques

| Composant | Risque | Provision |
|---|---|---|
| **Symfony 8.1** | Fin de support **janvier 2027** (~5 mois après rédaction) — ne survivra pas au lot 1 | 1-3 j × 2/an si `ARC-51` tenu ; 1-3 semaines sinon |
| **FrankenPHP worker** | Fuites d'état inter-requêtes (= incident d'isolation multi-tenant), fuites mémoire | 3-5 j de mise au point + `ARC-50` |
| **Symfony Reprise** | Expérimental 0.x — ruptures d'API possibles | 0,5-1 j par rupture ; impact borné aux assets (`ARC-60`) |
| **Symfony AI** | Récent, périmètre large, API en évolution | Couche produit tampon (`ARC-73..79`) |
| **API Platform 5.0** | Alpha — **exclue de la production** | Rester sur 4.3.x stable |
| **Modèle dimensionnel maison** | Grain mal défini, divergence silencieuse | `ARC-68` + `ARC-112..114` |
| **Développement assisté** | Code correct que personne n'a vraiment lu | `ADR-16`, notamment `ARC-106` |

**Provision globale recommandée : 27 à 43 j sur les lots 0 et 1** (15-25 j mise au point stack + 12-18 j surcoût du schéma en étoile physique vs vues matérialisées). À ajouter : 2-3 j outillage de sécurité (`ADR-15`), 1-3 j × 2/an montée Symfony (`ARC-52`, charge récurrente).

## 5. Modèle de données conceptuel (cdc/06)

**Entité racine : TENANT** (`INV-1`). Entités pivots : Compte client · Collaborateur · Profil (pivot de valorisation, coût/taux historisés) · Compétence · Calendrier (base de la capacité) · Opportunité → Devis → **Projet** (lien permanent au devis `INV-8`) → Lot → Jalon (déclencheur de facturation) · Affectation · **Imputation de temps** (unité immuable `INV-3`, valorisation figée à la validation) · Facture → Encaissement.

Invariants `INV-1..8` : voir `constraints.md` § 7. **`INV-2` (historisation à date d'effet) et `INV-3` (imputation immuable) sont à poser dès le premier schéma du lot 1** — omission fréquente et coûteuse.

## 6. Socle IA mutualisé et intégrations

**Socle IA** (`ARC-5..13`, `ADR-10`) : couche d'abstraction unique, jamais d'appel direct depuis le métier. **`ARC-9` — filtrage à la source** : contexte filtré par les droits de l'utilisateur *avant* transmission au modèle (ne jamais compter sur une consigne pour limiter la restitution ; ne jamais filtrer la réponse plutôt que l'entrée). Citation des sources (`ARC-10`), séparation calcul/texte (`ARC-11`), commutateur par tenant/fonction (`ARC-13`), produit utilisable sans IA (`ARC-80`).
**Priorité d'investissement IA :** module **Temps** d'abord (réduction de friction, risque le plus faible) ; fonctions conversationnelles du **Pilotage** ensuite, une fois le socle de sécurité éprouvé.

**Intégrations (INT-1..6)** : source de vérité unique par objet (`INT-1`), unidirectionnel par défaut (`INT-2`), mode dégradé obligatoire (`INT-3`), erreurs visibles + rejeu (`INT-4`), consentement pour tout signal de pré-remplissage (`INT-5`), construites sur l'API interne (`INT-6`). Bidirectionnalité recommandée **uniquement** sur signature électronique (statut) et éventuellement CRM, périmètre de champs délimité.
**12 systèmes cibles** échelonnés lots 1-4 ; seule l'inférence IA (fournisseur UE) est en priorité `M` dès le lot 1.

**Reprise de données (REP-1..4)** : l'exigence importante est `REP-3` (**import initial depuis tableur à chaque onboarding tenant**, priorité `M`), pas la reprise du MVP. `REP-2` (historique projets) sans valeur en dessous de 10 projets exploitables. ⚠️ Si `HYP-1` est fausse (MVP en production avec données vivantes) → plan de bascule, **+3 à 5 mois**.

## 7. Points ouverts pour la phase de conception

- `ARB-25` — hébergement de production UE (services gérés) à instruire au lot 2.
- `ARB-3` / `CTR-5` — choix du fournisseur d'inférence UE (souveraineté).
- `ARB-5` — matrice « paramétrable / non paramétrable » à arrêter en lot 0 (`RSQ-14`).
- `ARB-24` — offre commerciale avec inférence incluse (réintroduit le coût d'inférence au budget éditeur).
- `ARC-100` — classement cœur/support/générique de chaque module à documenter au lot 0 (dose de DDD par `ADR-8`).
- **Revérifier les versions et statuts de la stack avant tout engagement** (composants jeunes, avertissement de fraîcheur du CDC).

---

**Documents liés :** `research-summary.md`, `constraints.md`, `risks-opportunities.md`.
