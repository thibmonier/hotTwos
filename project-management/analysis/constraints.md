# Contraintes identifiées — Phase d'analyse

**Projet :** HotOnes — refonte ERP agence digitale / ESN
**Source de vérité :** `project-management/cdc/` (chap. 02, 05, 06, 12)
**Date :** 2026-08-31

Ce document consolide les contraintes qui bornent la conception. Les seuils chiffrés sont opposables en recette (toute ENF sans seuil est écartée du CDC).

---

## 1. Contraintes générales (CTR-*)

| Réf | Contrainte | Nature | Impact |
|---|---|---|---|
| `CTR-1` | Opérable par une petite équipe : produit + 2-4 dev | Organisationnelle | Interdit une architecture nécessitant une exploitation dédiée (cf. `ARC-3`, `ENF-SAAS-6`) |
| `CTR-2` | Conformité RGPD, avec données RH et d'évaluation | Réglementaire | Cf. § 4 |
| `CTR-3` | **AI Act européen** : usages RH/évaluation en catégorie à risque | Réglementaire | **Qualification juridique externe obligatoire avant conception des modules RH/REC. Non établie dans le CDC, non négociable.** (`ARB-14`) |
| `CTR-4` | Coût d'inférence IA compatible avec un SaaS par abonnement | Économique | Budget d'inférence par tenant + dégradation gracieuse (`ENF-IA-5`) |
| `CTR-5` | Souveraineté : hébergement + traitement IA dans l'UE | Contractuelle | Restreint le choix des fournisseurs de modèles (`ARB-3`) |

## 2. Contraintes de ressource (structurante — ARB-20 / HYP-15)

- Chiffrage établi pour **~4,5 ETP** sur 18-22 mois (`cdc/08`).
- Équipe réelle au démarrage : **1 personne** (10 ans Symfony).
- Un dev solo dispose de **80-130 j de dev effectif/an** → 8-12 ans pour le périmètre complet, ramené à **5-8 ans** avec le développement assisté (facteur global 1,4-1,6).
- **Décision de cadrage (2026-08-31) : périmètre complet retenu** (équipe cible supposée constituée). L'écart demeure un risque actif (`RSQ-3`, `RSQ-9`, `RSQ-17`, `RSQ-20`).
- Nuance architecturale : le multi-tenant (`ADR-6`) et les clés IA par tenant (`ADR-10`) se posent à bas coût dès le départ mais se rétro-adaptent très cher → **le socle est multi-tenant dès le lot 1**, même en démarrage mono-organisation.

## 3. Contraintes techniques d'architecture (ARC-*)

| Réf | Contrainte |
|---|---|
| `ARC-1` | Application web, sans installation client |
| `ARC-2` | Multi-tenant à isolation logique, **vérifiable par test** |
| `ARC-3` | Exploitable par 2-4 personnes sans astreinte — interdit les architectures distribuées complexes |
| `ARC-4` | Hébergement et traitements dans l'UE |
| `ARC-5` | Toute fonction IA passe par une **couche d'abstraction unique**, jamais d'appel direct depuis le métier |
| `ARC-6` | Calculs financiers/capacitaires dans un **moteur unique testé**, jamais dupliqué (ni backend/frontend) |
| `ARC-7` | API interne cohérente servant l'UI **et** les intégrations |
| `ARC-9` | **Construction de contexte IA appliquant les habilitations à la source des données** avant transmission au modèle — point critique |
| `ARC-10` | Citation : toute réponse générée référence les enregistrements qui l'ont alimentée |
| `ARC-11` | Séparation stricte valeurs calculées / texte généré ; assemblage côté système |
| `ARC-14` | **Minimiser le nombre de technologies/systèmes** à exploiter simultanément |
| `ARC-15/16/17` | Aucune logique métier dans un contrôleur ni un gabarit ; tout cas d'usage invocable sans HTTP |
| `ARC-18/19` | Aucune entité de persistance exposée ; **validation + habilitation dans la couche applicative, jamais dans l'adaptateur** |
| `ARC-47/50/61` | Mode worker : aucun état conservé entre requêtes ; tests d'intégration en config worker ; tenant posé/effacé à chaque requête |
| `ARC-63` | Frontières de modules vérifiées automatiquement (Deptrac) en CI |
| `ARC-80` | Un tenant sans clé IA n'a aucune fonction IA — le produit reste **pleinement utilisable** |
| `ARC-103..108` | Développement assisté : un test nommé par `RG-*`, invariants garantis en base, conventions versionnées, **périmètre de sécurité non délégué**, code généré livré avec ses tests |
| `ARC-111..114` | Modèle analytique alimenté par projection uniquement, reconstructible, test de non-divergence en CI, réconciliation en prod |

Détail de la stack et des 16 ADR : `technical-options.md`.

## 4. Contraintes non-fonctionnelles (ENF-*) — seuils opposables

### Performance (dimensionnement sur tenant grand = 150 collab., pointe fin de mois × 5)
- `ENF-PERF-1` (M) consultation courante **< 1 s P95** · `ENF-PERF-2` (M) **saisie de temps < 500 ms P95** · `ENF-PERF-3` (M) tableaux de bord **< 3 s P95** (5 ans d'historique) · `ENF-PERF-4` (S) plan de charge 12 mois/150 collab. **< 2 s** · `ENF-PERF-5` (M) répercussion validation→indicateurs **≤ 15 min** · `ENF-PERF-6` (S) tenue sous pointe ×5, test de charge obligatoire avant MEP.

### Disponibilité et continuité
- `ENF-DISPO-1` (M) **≥ 99,5 %** heures ouvrées (engagement contractuel) · `ENF-DISPO-2` (M) **RPO ≤ 1 h** · `ENF-DISPO-3` (M) **RTO ≤ 4 h** · `ENF-DISPO-5` (S) **dégradation d'un service IA ne bloque jamais les fonctions cœur** (contrainte d'architecture : chemin manuel équivalent à toute fonction IA).

### Sécurité — 🔴 critères bloquants avant MEP
- `ENF-SEC-4` (M) **isolation stricte inter-tenant** — test d'intrusion dédié (identifiant forgé, export, IA). **BLOQUANT.**
- `ENF-SEC-6` (M) **toute fonction IA accède aux données via le même contrôle d'habilitation que l'utilisateur** (`HAB-5`) — test d'intrusion (injection de consigne, extraction par recoupement). **BLOQUANT.**
- `ENF-SEC-1/2` authentification MDP + 2FA activable par tenant, SSO (S) · `ENF-SEC-3` chiffrement transit + repos · `ENF-SEC-5` habilitations au niveau de l'accès données, pas de l'affichage · `ENF-SEC-7` journalisation des accès sensibles · `ENF-SEC-8` accès éditeur exceptionnel/motivé/tracé/notifié · `ENF-SEC-11` (S) correction des vulnérabilités critiques **sous 15 j** en CI.

### RGPD / conformité — 🔴 prérequis bloquant
- `ENF-RGPD-5` (M) **AIPD** pour RH, recrutement et pré-remplissage par signaux d'activité — **prérequis bloquant aux lots 1 (`EF-TMP-10`) et 4**.
- `ENF-RGPD-2` purge/anonymisation **vérifiable techniquement** · `ENF-RGPD-3` droits des personnes **< 5 j ouvrés** · `ENF-RGPD-7` hébergement + inférence IA dans l'UE · `ENF-RGPD-8` **données tenant jamais utilisées pour l'entraînement** sauf accord explicite révocable (engagement contractuel + vérification technique) · `ENF-RGPD-9` réversibilité (export complet en autonomie) · `ENF-RGPD-10` information sur traitements automatisés.

### Multi-tenant / exploitabilité (SAAS)
- `ENF-SAAS-1` isolation logique + paramétrage indépendant par tenant · `ENF-SAAS-2` création d'un tenant **< 15 min sans intervention infra** · `ENF-SAAS-5` supervision (dispo, temps de réponse, erreurs, conso IA par tenant) · `ENF-SAAS-6` exploitable par 2-4 personnes sans astreinte 24/7 (`CTR-1`).

### IA — 🔴 explicabilité bloquante
- `ENF-IA-1` (M) **explicabilité** de toute suggestion/alerte/synthèse — **aucune MEP sans ce dispositif. BLOQUANT.**
- `ENF-IA-2` non-substitution (l'IA propose, un humain décide) · `ENF-IA-3` séparation calcul/rédaction (aucun chiffre issu d'un LLM) · `ENF-IA-4` traçabilité (fonction, utilisateur, périmètre, coût) · `ENF-IA-5` maîtrise du coût (suivi + plafond par tenant, dégradation gracieuse) · `ENF-IA-7` (S) mesure de qualité, retrait après 3 mois sous seuil · `ENF-IA-9` (M) **désactivation par tenant, produit pleinement fonctionnel sans IA**.

### UX — 🔴 critère bloquant du lot 1
- `ENF-UX-1` (M) **saisie de temps ≤ 2 min/semaine** (`EF-TMP-3`), test utilisateur sur 5 profils. **BLOQUANT DU LOT 1.**
- `ENF-UX-2` autonomie sans formation (test sur 3 novices) · `ENF-UX-3` écrans collaborateurs utilisables sur mobile · `ENF-UX-4` (S) accessibilité niveau **AA** · `ENF-UX-5` FR + EN, aucune chaîne en dur · `ENF-UX-6` actions destructives confirmées et réversibles.

### Maintenabilité — 🔴 seuil bloquant CI
- `ENF-MAINT-1` (M) **couverture de tests ≥ 80 %** sur règles critiques (valorisation, marge, capacité, habilitations) — **seuil bloquant CI avant MEP**.
- `ENF-MAINT-2` CI/CD dev/recette/prod distincts · `ENF-MAINT-4` API documentée et versionnée · `ENF-MAINT-5` jeux de test représentatifs des 3 tailles de tenant, régénérables.

## 5. Périmètre — inclus / exclu (cdc/02)

**Inclus (9 modules) :** Référentiels (`REF`, lot 1), CRM avant-vente (`CRM`, lot 3), Projets/delivery (`PRJ`, lot 1), Planification/staffing (`PLN`, lot 2), Temps/activité (`TMP`, lot 1), Finance/rentabilité (`FIN`, lot 2), RH (`RH`, lot 4), Recrutement (`REC`, lot 4), Pilotage/reporting (`PIL`, lot 3).

**Exclu :** comptabilité générale (export `EF-FIN-22`), calcul de paie (export éléments variables `EF-RH-18`), gestion de tâches/tickets (intégration Jira/Linear `EF-PRJ-25`), signature électronique (tiers `EF-CRM-19`), emailing/marketing, gestion documentaire d'entreprise, portail client externe (backlog, statut `W`).

## 6. Habilitations transverses (HAB-*)

| Réf | Règle |
|---|---|
| `HAB-1` | Le **coût** d'un collaborateur n'est jamais visible d'un chef de projet (marge oui, détail des coûts non). |
| `HAB-2` | Le contenu d'un **entretien** n'est visible que de l'intéressé, son manager direct et la RH ; la direction n'a que l'agrégat. |
| `HAB-3` | Données de **santé** réduites au minimum : type « arrêt » + dates, jamais de motif médical. |
| `HAB-4` | Cloisonnement strict des tenants ; aucun rôle applicatif ne traverse les tenants (`ENF-SEC-8`). |
| `HAB-5` | **Toute suggestion IA est soumise aux mêmes habilitations que la donnée qui la fonde — filtrage à la source, pas filtre d'affichage.** Point de vigilance sécurité principal. |
| `HAB-6` | Toute lecture d'une donnée RH sensible ou de coût est **tracée** (piste d'audit consultable par l'admin tenant). |

## 7. Invariants du modèle de données (INV-*) — non rétro-adaptables

`INV-1` tenant sur toute entité · `INV-2` **historisation à date d'effet** de toute donnée financière · `INV-3` **imputation de temps = unité immuable**, valorisation figée à la validation · `INV-4` avancement / RAF / consommation = trois données distinctes · `INV-5` charge ferme ≠ charge probable · `INV-6` aucune suppression physique · `INV-7` journalisation de toute action à effet métier · `INV-8` lien permanent projet → devis + avenants.

> **`INV-2` et `INV-3` sont les plus fréquemment omis et les plus coûteux à récupérer. À poser dès le premier schéma du lot 1 (`CDR-1`).**

---

**Documents liés :** `research-summary.md`, `risks-opportunities.md`, `technical-options.md`.
