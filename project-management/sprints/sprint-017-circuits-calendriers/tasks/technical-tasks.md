# Tâches Techniques Transverses - Sprint 017

## Process / Git
### T-TECH-01 : Branche par story avant tout code
- **Type** : [OPS] · **Estimation** : 0.25h/story · Base `main` post-#95. 1 PR/story, TDD, merge squash.

## Coordination inter-US
### T-TECH-02 : Séquencement
- **Ordre : US-022 → US-021 → US-017.**
  - US-022 (sûre) enrichit `WorkingDaysCalculator` (fermetures) d'abord.
  - US-021 ajoute les variantes **par utilisateur** au même calculateur (après US-022 pour éviter les conflits) et bascule occupation/complétude/activité.
  - US-017 (indépendante) touche le flux absences.

## Qualité / Clôture
### T-TECH-03 : Revue de clôture S17 + `make ci` vert
- **Type** : [REV] · **Dépend de** : toutes les US
- `make ci` vert (PHPStan max, Deptrac UI⇏Infra, gitleaks) ; couverture ≥ 80 %.
- Migrations + RLS : `closure_period`, `work_schedule`, `absence_validation_circuit` (+ colonne `current_step`).
- `make cache-dev` après migrations (cache Doctrine).
- MAJ `sprint-status.yaml` + review/rétro + statut EPIC-001 (**bouclage EPIC-001** si tout livré).

## Rappels de pièges (mémoire projet)
| Piège | Mitigation |
|-------|------------|
| Nouveau read partagé (jours ouvrés par user, fermetures) | Ajouter la table au `SchemaTool` de CHAQUE test fonctionnel l'atteignant (occupation/complétude/activité/absence) — recenser au design (action rétro S16) |
| Modifier `WorkingDaysCalculator` | **Ne pas** changer `isWorkingDay(tenant,day)` (ScheduleReminders en dépend) ; ajouter des variantes |
| Twig 3 | `for|filter`, `max`/`min` = fonctions |
| Migration additive sur table existante | `current_step` avec défaut 1 pour les lignes existantes |
| CS/Rector | `make cs-fix` + `rector-fix` avant commit |

## Notes
- Solo dev ; pas de tâches Flutter/API Platform (server-rendered Twig + config).
- **Reporté S18** : fériés mobiles (onboarding US-019), extension audit (US-020 org/profils).
