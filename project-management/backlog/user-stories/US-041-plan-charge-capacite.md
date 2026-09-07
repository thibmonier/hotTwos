# US-041: Plan de charge — capacité vs charge ferme

## Métadonnées
- **ID**: US-041
- **EPIC**: EPIC-004 (Planification & Staffing)
- **Sprint**: 18
- **Statut**: 🔵 Backlog (à affiner)
- **Points**: 5 (estimation)
- **Persona**: P3 (Resource Manager)
- **Créé le**: 2026-09-07

## Traçabilité
- **Implémente**: EPIC-004 (OBJ-4) ; INV-5 (charge ferme ≠ probable — part ferme ici)
- **Dépend de**: US-021/022 (capacité), US-037 (affectations), US-033/078 (budgets charge)
- **Reporté**: **charge probable** (pipeline pondéré) → dépend d'EPIC-006 (CRM). INV-5 : champ distinct anticipé.

## User Story (esquisse — à affiner)

**En tant que** resource manager,
**je veux** visualiser, par collaborateur et sur une période, la **capacité** (jours ouvrés nets)
et la **charge ferme** affectée,
**afin de** repérer sur/sous-charges sans tableur.

## Notes de cadrage (pour l'affinage)
- Capacité = `WorkingDaysForUser` − absences validées (réutilise US-021/022 + occupation).
- Charge ferme = jours affectés (affectations US-037 / budgets par profil) sur la période.
- Détection sur/sous-charge = comparaison capacité vs charge ferme (seuil simple).
- **Charge probable = hors périmètre** (CRM absent) : prévoir le champ mais ne pas l'alimenter.
- Gating HAB-1 (pas de coût). **DoR** : Gherkin, modèle de charge ferme, RLS, tests.
