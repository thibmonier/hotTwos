# Tâches - US-040 : Recherche de staffing par compétence & disponibilité

## Informations US
- **Epic** : EPIC-004 · **Persona** : P3 (Resource Manager) · **Points** : 8 · **Sprint** : sprint-018 · **Ordre** : #4 (dépend US-041)

## Résumé
Rechercher les collaborateurs par (compétence, niveau min) filtrés par disponibilité sur une période.

## Vue d'ensemble des tâches

| ID | Type | Tâche | Est. | Dépend | Statut |
|----|------|-------|------|--------|--------|
| T-040-01 | [DB] | `SkillAssignmentRepository::findUserIdsBySkillAtLeast(tenant, skillId, minLevel)` (Doctrine + fake) | 2h | - | 🔲 |
| T-040-02 | [BE] | Read model `Application\Staffing\SearchStaffing` (croise compétence + dispo US-041) + `StaffingCandidateView` | 3.5h | 01, US-041 | 🔲 |
| T-040-03 | [FE-WEB] | Controller `/planification/recherche` + Twig (form compétence/niveau/période, résultats, gating HAB-1) | 3h | 02 | 🔲 |
| T-040-04 | [TEST] | Unit (filtre compétence+niveau, croisement dispo, vide) + Functional (recherche, 403, HAB-1) | 3.5h | 02,03 | 🔲 |
| T-040-05 | [DOC]+[REV] | PHPDoc + review + `make ci` | 1h | 04 | 🔲 |

**Total estimé** : ~13h

## Détails clés
- **T-040-01** : DQL sur `SkillAssignment` filtrant `skillId` + `level >= minLevel`, ne retenant que les compétences **actives** ; retourne les userIds. Port + Doctrine + fake.
- **T-040-02** : pour chaque candidat, calculer la disponibilité via le read model d'US-041 sur la période ; construire `StaffingCandidateView` (userId, displayName, niveau, joursDisponibles). Recherche simple (ET), pas de scoring.
- **T-040-03** : sélecteur compétence (actives) + niveau min (échelle US-013) + période ; liste triée par disponibilité décroissante ; gating planification + HAB-1.
- **T-040-04** : SchemaTool via trait QUAL-3.

## Résumé
| Couche | Tâches | Heures |
|--------|--------|--------|
| [DB] | 1 | 2h |
| [BE] | 1 | 3.5h |
| [FE-WEB] | 1 | 3h |
| [TEST] | 1 | 3.5h |
| [DOC]/[REV] | 1 | 1h |
| **TOTAL** | **5** | **~13h** |
