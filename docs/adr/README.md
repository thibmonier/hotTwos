# Architecture Decision Records (ADR) — HotOnes

> **Statut particulier de ce projet.** Les décisions d'architecture ont été **arrêtées et documentées en amont** dans le cahier des charges, chapitre `project-management/cdc/12-socle-technique.md` (identifiants `ADR-1` à `ADR-16`), avec leur contexte, leurs alternatives et leur argumentaire complet. Cet index les **formalise et les trace** au format ADR standard ; il ne les rejoue pas. La source de vérité argumentée reste `cdc/12`.
>
> Les ADR ci-dessous sont donc au statut **« Adopté »** dès l'origine. Toute remise en cause passe par un nouvel ADR (`ADR-0017+`) qui référence celui qu'il supersède.

## Convention

- `ADR-00NN` : identifiant local de cet index, mappé 1:1 sur `ADR-N` du CDC.
- Statut : `Adopté` (CDC) · `À instruire` (décision différée, arbitrage ouvert) · `Superseded by ADR-XXXX`.
- Les nouveaux ADR propres à la réalisation (non couverts par le CDC) sont créés à partir de `ADR-0017`, via `/common:architecture-decision`.

## Index des décisions

| ADR | Réf CDC | Décision | Statut |
|-----|---------|----------|--------|
| [ADR-0001](#adr-0001) | ADR-1 | Monolithe modulaire à cœur API-first (un déploiement, un dépôt, logique en couche applicative, adaptateurs web/API/CLI) | Adopté |
| [ADR-0002](#adr-0002) | ADR-2 | Runtime FrankenPHP en mode worker, Caddy, supervisé par Ember | Adopté |
| [ADR-0003](#adr-0003) | ADR-3 | Symfony : suivre la branche stable puis se poser sur la LTS ; tolérance zéro dépréciations (Rector) | Adopté |
| [ADR-0004](#adr-0004) | ADR-4 | API Platform 4.3.x en mode DTO strict (5.0 alpha exclue) | Adopté |
| [ADR-0005](#adr-0005) | ADR-5 | Présentation Twig + Stimulus + Turbo, rendu serveur ; assets Symfony Reprise + Vite | Adopté |
| [ADR-0006](#adr-0006) | ADR-6 | PostgreSQL + pgvector ; isolation multi-tenant par discriminant partagé + RLS (double barrière) | Adopté |
| [ADR-0007](#adr-0007) | ADR-7 | Traitements asynchrones via bus Symfony Messenger, transport base de données au démarrage | Adopté |
| [ADR-0008](#adr-0008) | ADR-8 | Clean Architecture + DDD dosés par sous-domaine (cœur/support/générique), frontières vérifiées (Deptrac) | Adopté |
| [ADR-0009](#adr-0009) | ADR-9 | Modèle analytique en étoile physique, alimenté exclusivement par projection d'événements de domaine | Adopté |
| [ADR-0010](#adr-0010) | ADR-10 | Socle IA Symfony AI multi-fournisseur + couche produit mince ; clés d'API fournies par chaque tenant | Adopté |
| [ADR-0011](#adr-0011) | ADR-11 | Environnement de développement conteneurisé, parité worker dès le développement | Adopté |
| [ADR-0012](#adr-0012) | ADR-12 | CI/CD GitHub Actions, 11 étapes bloquantes (dont tests d'isolation et tests worker) | Adopté |
| [ADR-0013](#adr-0013) | ADR-13 | Staging Railway Hobby (UE, sans données réelles) ; prod UE services gérés (`ARB-25` ouvert) | Adopté / À instruire (prod) |
| [ADR-0014](#adr-0014) | ADR-14 | Observabilité Ember + suivi d'erreurs UE + Prometheus/Grafana | Adopté |
| [ADR-0015](#adr-0015) | ADR-15 | Sécurité automatisée : 8 couches outillées + test d'intrusion annuel externe | Adopté |
| [ADR-0016](#adr-0016) | ADR-16 | Développement assisté par agent (claude-craft), TDD imposé, périmètre de sécurité non délégué | Adopté |

---

## Décisions à instruire (arbitrages ouverts)

Ces points ne sont pas encore des ADR fermés — ils portent un arbitrage attendu du sponsor (voir `cdc/10` et `workflow-status.yaml`) :

| Arbitrage | Objet | Impact ADR |
|---|---|---|
| `ARB-25` | Hébergement de production UE (services gérés) — à instruire au lot 2 | Précise ADR-0013 |
| `ARB-3` / `CTR-5` | Choix du fournisseur d'inférence IA UE (souveraineté) | Précise ADR-0010 |
| `ARB-24` | Offre commerciale avec inférence incluse (réintroduit le coût d'inférence côté éditeur) | Précise ADR-0010 |
| `ARB-5` | Matrice « paramétrable / non paramétrable » (arrêtée en lot 0) | Cadre EPIC-001 |

---

## Détail des décisions

> Chaque entrée résume la décision. **Contexte, alternatives écartées et conséquences complètes : `cdc/12-socle-technique.md`, section de l'`ADR-N` correspondant.**

### ADR-0001
**Monolithe modulaire à cœur API-first.** Un seul déploiement, un seul dépôt. Toute la logique métier dans une couche applicative (cas d'usage) indépendante du transport, consommée par trois adaptateurs : contrôleurs web, contrôleurs d'API, commandes CLI. Aucun microservice, aucun front découplé. → `ARC-1, ARC-15..17`.

### ADR-0002
**FrankenPHP en mode worker** (application chargée en mémoire entre requêtes), servi par Caddy, supervisé par Ember. Gain décisif sur `ENF-PERF-2` (saisie < 500 ms P95) au prix d'une gestion explicite de l'état inter-requêtes (`ARC-47..50, ARC-61`). Risque associé : `RSQ-15` (fuite d'état).

### ADR-0003
**Version Symfony et politique de mise à jour.** Suivre la branche stable pendant la construction (8.1 → 8.2 → …), se poser sur la prochaine LTS en socle de production. Tolérance zéro aux dépréciations (`ARC-51`), montée dans le mois (`ARC-52`), Rector intégré dès le lot 1 (`ARC-53`). Point daté : **Symfony 8.1 hors support janvier 2027** (`RSQ-16`).

### ADR-0004
**API Platform 4.3.x en mode DTO strict.** Providers et processors sur mesure ; jamais d'exposition directe d'entités (`ARC-18`). API interne dès le lot 1 ; API publique contractuelle au lot 3. API Platform 5.0 (alpha) exclue de la production.

### ADR-0005
**Présentation Twig + Stimulus + Turbo**, rendu serveur par défaut ; assets construits via Symfony Reprise (expérimental 0.x) + Vite. Aucune règle métier réimplémentée côté client (`ARC-27`). Les composants riches (plan de charge) sont une exception justifiée.

### ADR-0006
**PostgreSQL + pgvector ; isolation multi-tenant par discriminant partagé + RLS.** Double barrière : filtre ORM Doctrine automatique (`ARC-33`) + politique RLS (`ARC-34`). PostgreSQL retenu pour l'analytique (fenêtrage, vues matérialisées), JSONB indexable (champs personnalisés), intervalles pour `INV-2`, pgvector pour l'IA. Détail : `architecture/erd.md`, `architecture/security.md`.

### ADR-0007
**Traitements asynchrones via Symfony Messenger**, transport base de données tant que le volume ne l'exige pas. Tout traitement > 3 s est asynchrone (`ARC-29`), messages rejouables et idempotents (`ARC-31`). Planification versionnée avec le code (`ARC-62`).

### ADR-0008
**Clean Architecture + DDD dosés par sous-domaine.** Traitement complet pour le cœur (Planification, Temps, Finance), intermédiaire pour le support (Projet, Commercial, RH, Recrutement), minimal pour le générique (Référentiels, Pilotage-lecture). Couche applicative universelle. Frontières vérifiées par Deptrac (`ARC-63`). Event sourcing et CQRS généralisé refusés. Classement documenté au lot 0 (`ARC-100`).

### ADR-0009
**Modèle analytique en étoile physique**, alimenté exclusivement par projection d'événements de domaine. Rend `RSQ-5` (divergence des chiffres) structurellement impossible. Garanties : projection uniquement (`ARC-111`), reconstruction complète (`ARC-112`), test de non-divergence en CI (`ARC-113`), réconciliation en production (`ARC-114`), tenant + RLS sur l'analytique (`ARC-119`). Détail : `architecture/erd.md`.

### ADR-0010
**Socle IA Symfony AI multi-fournisseur** (OpenAI, Anthropic, Gemini, Azure, Bedrock, Mistral, Ollama) + couche produit mince (`ARC-73..79`) : contexte sous habilitations, plafond par tenant, journalisation, citation des sources, séparation calcul/texte, commutateur par tenant, chemin manuel équivalent. **Clés d'API fournies par chaque tenant** ; un tenant sans clé n'a aucune fonction IA, le produit reste pleinement utilisable (`ARC-80`).

### ADR-0011
**Environnement de développement conteneurisé**, reproductible en une commande, même base que la production (worker exercé dès le dev, `ARC-86`). Données de test des 3 tailles de tenant régénérables (`ARC-87`). Aucun secret réel au dépôt (`ARC-88`).

### ADR-0012
**CI/CD GitHub Actions, 11 étapes bloquantes** : PHPStan max, Deptrac, style, tests + couverture ≥ 80 % (`ENF-MAINT-1`), tests d'isolation multi-tenant, tests en mode worker (`ARC-50`), tolérance zéro dépréciations, audit des dépendances, détection de secrets, E2E parcours critiques, tests de performance. Aucune fusion sans chaîne verte (`ARC-89`).

### ADR-0013
**Hébergement.** Staging Railway Hobby, région UE, sans données réelles. Production sur hébergement UE en services gérés — décision distincte à instruire au lot 2 (`ARB-25`). Services gérés (BD, cache, stockage), sauvegardes automatiques testées trimestriellement (`ARC-46`).

### ADR-0014
**Observabilité 4 niveaux** : Ember (serveur applicatif, métriques P50/P90/P95/P99), suivi d'erreurs (palier gratuit UE), Prometheus + Grafana, profileur en développement. Métriques métier au même format que les métriques techniques (`ARC-93`) ; alerte par seuil `ENF-PERF/DISPO` (`ARC-94`).

### ADR-0015
**Sécurité automatisée, défense en profondeur** (outils gratuits/libres) : composer audit, PHPStan max + taint, analyseur de motifs, détecteur de secrets, scanner de conteneurs, scanner dynamique en préproduction, test d'intrusion annuel externe. Les deux risques majeurs (fuite inter-tenant, exposition IA) ne sont détectés par aucun analyseur : conception + tests manuels + intrusion humain (`ARC-106`). Détail : `architecture/security.md`.

### ADR-0016
**Développement assisté par agent** (claude-craft). Déplace le goulot d'étranglement de l'écriture vers la relecture. Un test nommé par `RG-*` (`ARC-103`), invariants garantis en base (`ARC-104`), conventions versionnées (`ARC-105`), **périmètre de sécurité non délégué** (`ARC-106`), code livré avec ses tests (`ARC-107`), tests écrits depuis l'exigence (`ARC-108`). Risques : `RSQ-20` (relecture saturée), `RSQ-21` (habilitation générée non relue).
