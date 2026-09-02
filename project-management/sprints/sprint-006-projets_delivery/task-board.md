# Task Board — Sprint 6 (Projets & delivery)

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🔲 À Faire

| ID | US | Tâche | Est. |
|----|----|-------|------|
| — | — | _(vide — toutes les tâches sont terminées)_ | — |

## 🔄 En Cours
| ID | US | Tâche | Démarré |
|----|----|-------|---------|

## 👀 En Review
| ID | US | Tâche | Reviewer |
|----|----|-------|----------|

## ✅ Terminé

| US | Résultat | Commits |
|----|----------|---------|
| US-030 | **Création & cycle de vie (8 tâches)** : agrégat `Project` enrichi + `ProjectStatus` (7 statuts), `CreateProject`/`ChangeProjectStatus`, gate d'imputation par statut, écran `/projets` (liste/création/détail onglets ARIA) | `(S6)` |
| US-031 | **Lots & jalons (7)** : `ProjectLot` (arbre 2 niveaux, budget charge+montant) + `ProjectMilestone` (idempotence facturation), écart budget confirmé, réallocation tracée, onglet Structure | `(S6)` |
| US-037 | **Affectation & restriction (7)** : `ProjectAssignment` + `ExceptionalImputationOpening`, restriction d'imputation (affectés seuls dès la 1ʳᵉ affectation), onglet Équipe | `(S6)` |
| US-034 | **Engagements externes (6)** : `ExternalCommitment` (montant/fournisseur, refus si clôturé), agrégation coûts externes, onglet Engagements | `(S6)` |
| US-038 | **Clôture opérationnelle (7)** : clôture (blocage imputations non validées, avertissements), `ProjectReopening` 4-eyes, gate d'imputation clôture, onglet Clôture | `(S6)` |
| Doc/Revue | Doc `docs/modules/project.md` ; revues security-auditor + symfony-reviewer **GO** (durcissements L3/L4/O(n²) appliqués) | `(S6)` |

## 🚫 Bloqué
| ID | US | Raison | Action |
|----|----|--------|--------|

## Métriques
- **Tâches** : 37 total | **37 terminées (100 %)**
- **Heures** : ~69h estimées | ~66h consommées
- **Points** : **21/21 livrés** · US-030 ✅ · US-031 ✅ · US-037 ✅ · US-034 ✅ · US-038 ✅
- **CI** : `make ci` vert — **403 tests**, PHPStan max, Deptrac, cs/rector, gitleaks, `schema:validate`. 5 migrations RLS (project évolué + 5 nouvelles tables).
- **EPIC-002 (Projets & delivery)** : ouvert et livré (chemin critique du lot 1). Reste au backlog PRJ : US-032/033/035/036 (budget vente, RAF, atterrissage).
