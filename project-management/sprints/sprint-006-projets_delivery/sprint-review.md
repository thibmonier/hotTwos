# Sprint Review — Sprint 6 : Projets & delivery (EPIC-002)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-02 |
| Base git | `feature/sprint-6-planning` (Sprint 5 mergé, PR #7) |
| Animateur | Scrum Master |
| Contexte | Développement piloté IA — **conception UX/UI en amont** + revues croisées `security-auditor` / `symfony-reviewer` / `accessibility-expert` |

## Sprint Goal

> « Un chef de projet **crée et structure un projet** (cycle de vie à 7 statuts, lots/jalons,
> engagements externes), y **affecte des collaborateurs** qui seuls peuvent imputer, et le **clôture**
> avec traçabilité — complétant le delivery projet (EPIC-002) et débloquant le planning d'US-059. »

**Atteint : ✅ OUI**

Justification : **EPIC-002 est ouvert et livré** — chemin critique du lot 1, prérequis du lot 2.
L'entité `Project`, minimale jusqu'au Sprint 5, est devenue un **agrégat métier** (client, budget,
contractualisation, dates, cycle de vie). Le cycle de vie, l'affectation et la clôture **conditionnent
l'imputation** de temps (chaîne de gardes) ; la clôture réutilise le pattern de réouverture 4-eyes
d'US-057. La charge prévisionnelle d'affectation (US-037) débloque le planning à venir d'US-059.

## User Stories livrées

| ID | Titre | Points | Démo | Statut |
|----|-------|--------|------|--------|
| US-030 | Création de projet et cycle de vie | 5 | ✅ | ✅ Livré |
| US-031 | Structure en lots et jalons | 5 | ✅ | ✅ Livré |
| US-037 | Affectation et restriction d'imputation | 5 | ✅ | ✅ Livré |
| US-034 | Engagements externes rattachés au projet | 3 | ✅ | ✅ Livré |
| US-038 | Clôture opérationnelle du projet | 3 | ✅ | ✅ Livré |

**Livré : 21/21 points (100 %)** — 37/37 tâches.

## User Stories non terminées

Aucune. Le périmètre engagé est intégralement livré.

## Métriques

| Métrique | Valeur | Tendance |
|----------|--------|----------|
| Points planifiés / livrés | 21 / 21 | 100 % |
| Vélocité | 21 | ➡️ stable (S1=29, S2=20, S3=23, S4=21, S5=22) |
| Tests (suite) | 403 | ↗️ (372 → 403, +31) |
| PHPStan (max) / Deptrac | 0 / 0 | ➡️ stable |
| Migrations RLS | 5 (project évolué + 5 tables neuves) | — |
| Findings de revue corrigés | 3 durcissements (L3/L4 + O(n²)) | — |
| Écrans précédés d'une conception UX/UI | 100 % (module projet) | ✅ |

## Démonstration (scénarios de bout en bout)

```gherkin
# US-030 — création & cycle de vie
Given un chef de projet sur /projets/nouveau
When il crée un projet (client, responsable, budget, contractualisation)
Then le projet est « En préparation » avec un code PRJ-XXXX, et l'imputation est refusée
When il passe le projet « En cours »
Then l'imputation devient possible (transitions autorisées uniquement)

# US-031 — structure
Given un projet de 200 000 €
When on crée des lots (charge j + montant €) dont la somme dépasse le budget
Then l'écart est signalé et l'enregistrement exige une confirmation explicite
When un jalon « Recette » à déclencheur de facturation passe « Atteint »
Then l'intention de facturation est enregistrée une seule fois (idempotent)

# US-037 — affectation & restriction
Given un projet avec des collaborateurs affectés
When un collaborateur non affecté tente d'imputer
Then la saisie est refusée, sauf ouverture exceptionnelle tracée sur la semaine

# US-038 — clôture
Given un projet avec des imputations en attente de validation
When le chef de projet tente de clôturer
Then la clôture est bloquée (RG-PRJ-5) ; sinon confirmation des jalons/engagements en cours
When une réouverture est demandée puis approuvée par un ADMIN distinct (4-eyes)
Then l'imputation redevient possible sur la fenêtre approuvée
```

Ordre de démo : (1) création + gate de statut, (2) structure lots/jalons + écart, (3) affectation +
restriction + ouverture exceptionnelle, (4) engagements externes, (5) clôture + réouverture 4-eyes.

## Feedback (revues croisées)

### Positif
- **Chaîne de gardes d'imputation** lisible et cohérente (période → absence → statut → clôture →
  affectation), jugée maintenable.
- **RLS fail-closed** confirmée sur les 5 nouvelles tables + 4-eyes réellement appliqué au domaine.
- **Rétro-compatibilité** soignée : l'évolution de `Project` n'a cassé aucun test existant (statut
  par défaut « En cours », restriction d'affectation inactive sans affectation).
- **Conception UX/UI systématique** avant les écrans (onglets WAI-ARIA, badges non-couleur-seule).

### À améliorer (détecté en revue, traité)
- **[Low] Approbation de réouverture** non liée au projet de l'URL → `projectId` vérifié.
- **[Low] Borne basse de durée** absente → `RecordTimeEntry` rejette `minutes ≤ 0`.
- **[Perf] `structureView`** en O(n²) → sous-lots pré-groupés.

### Suivi (non bloquant — dette assumée)
- **`userId` non ancré à l'acteur** dans `RecordTimeEntry` (pré-existant US-050 ; mitigé par les
  contrôleurs) → refactor vers un objet `User`.
- **Fenêtre de réouverture sans borne basse** (`openFrom`) → durcissement.
- **`ProjectPageController` à 10 dépendances** (agrégation de lecture) → extraction d'un presenter.
- Dégradations : facturation (EPIC-005), budget de vente (US-033), plan de charge (EPIC-004), client
  texte libre (US-014).

## Impact sur le Backlog

| Action | US | Description |
|--------|-----|-------------|
| Débloqué | US-059 | Le planning à venir peut s'alimenter de la charge d'affectation (US-037) |
| Reste EPIC-002 | US-032/033/035/036 | Projets internes, budget de vente, RAF, vue d'atterrissage |
| Dette de suivi | — | Notifications effectives (action rétro S5, toujours ouverte) |

## Prochaines étapes

1. Rétrospective du sprint (`sprint-retro.md`).
2. Push + PR #8 « ready » → merge vers `main` quand la CI est verte.
3. Planifier le Sprint 7 : poursuite EPIC-002 (budget/atterrissage) et/ou livraison des notifications.
