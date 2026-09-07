# Tâches Techniques Transverses - Sprint 016

## Process / Git

### T-TECH-01 : Branche par story avant tout code
- **Type** : [OPS] · **Estimation** : 0.25h/story
- Convention run-sprint : 1 PR/story, TDD, merge squash. Créer la branche **avant** de coder (piège récurrent).
- Base = `main` post-#87.

## Coordination inter-US (ordre imposé par les dépendances)

### T-TECH-02 : Séquencement
- **Type** : [BE] · **Estimation** : —
- Ordre : **US-012 → US-013 → US-020 → US-019**.
  - US-020 **instrumente** les points d'écriture d'US-012 (fériés) et US-013 (compétences) → doit venir après.
  - US-019 **provisionne** les défauts issus d'US-012 (fériés), US-013 (échelle), US-016 (EUR) → en dernier.

## Refactoring (dette utile)

### T-TECH-03 : Centralisation des jours ouvrés (porté par US-012 T-012-05)
- **Type** : [BE] · **Raison** : la logique « jours ouvrés » est dupliquée 4× (`OccupationReport`,
  `CompletenessGrid`, `ActivitySummary`, `ScheduleReminders`). Le refactor DRY est **dans** US-012 ;
  veiller à la non-régression des tests fonctionnels de ces 4 zones.

## Qualité / Clôture

### T-TECH-04 : Revue de clôture S16 + `make ci` vert
- **Type** : [REV] · **Estimation** : 2h · **Dépend de** : toutes les US
- `make ci` vert (PHPStan max, Deptrac UI⇏Infra, gitleaks) ; couverture ≥ 80 % (`make coverage`).
- Migrations + RLS présentes pour toute nouvelle table (`holiday`, `skill*`, `config_audit_entry`, éventuelle `tenant_onboarding`).
- `make cache-dev` après migrations (piège : cache métadonnées Doctrine périmé → `schema:validate` faux désync).
- MAJ `sprint-status.yaml` (US → done) + review/rétro + statut EPIC-001.

## Rappels de pièges (mémoire projet)
| Piège | Mitigation |
|-------|------------|
| Test fonctionnel atteignant une page (occupation, dashboard, paramétrage) | Ajouter les nouvelles entités au `SchemaTool` du test |
| UI dépendant de l'Infra | Ports dans le Domaine (Deptrac) ; défaut par constante sur le port |
| Cache Doctrine périmé après migration | `make cache-dev` avant `schema:validate` |
| CSRF | Valider le jeton **avant** toute écriture (et avant un `.first()` de formulaire) |
| `git add -A` | Stager explicitement (`compose.override.yaml` désormais gitignore) |

## Notes
- **Solo dev** : pas de tâches Flutter/mobile ni API Platform (server-rendered Twig + commande console).
- Réserve si capacité : US-017 (statuts & circuits, 8 pts) — hors périmètre engagé.
