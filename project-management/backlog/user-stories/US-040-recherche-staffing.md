# US-040: Recherche de staffing par compétence & disponibilité

## Métadonnées
- **ID**: US-040
- **EPIC**: EPIC-004 (Planification & Staffing)
- **Sprint**: 18
- **Statut**: 🔵 Backlog (à affiner)
- **Points**: 8 (estimation)
- **Persona**: P3 (Resource Manager)
- **Créé le**: 2026-09-07

## Traçabilité
- **Implémente**: EPIC-004 (module RES — recherche de staffing) ; OBJ-3
- **Dépend de**: US-013 (compétences), US-021/022 (disponibilité/jours ouvrés), US-037 (affectations)
- **Réutilise / ne re-spécifie pas** : `Skill`/`SkillAssignment` (US-013), `WorkingDaysCalculator` (dispo).

## User Story (esquisse — à affiner)

**En tant que** resource manager,
**je veux** rechercher les collaborateurs maîtrisant une compétence (niveau minimal) et disponibles sur une période,
**afin de** staffer les projets sur des critères objectifs sans tableur.

## Notes de cadrage (pour l'affinage)
- Recherche = ET des critères : compétence + niveau min + disponibilité (jours ouvrés − absences − charge ferme) sur la période.
- Réutiliser `SkillAssignmentRepository` (par compétence/niveau) et le calcul de capacité (US-041) pour la dispo.
- Périmètre : recherche simple (pas de scoring avancé) ; gating HAB-1 (pas de coût dans les résultats).
- **DoR** : critères Gherkin, requête repo, RLS, tests.
