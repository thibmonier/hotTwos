# Tâches - Sprint 018 : Staffing (EPIC-004) + finitions Review & qualité

## Vue d'ensemble

| ID | Titre | Points | Tâches | Heures | Ordre | Statut |
|----|-------|--------|--------|--------|-------|--------|
| QUAL-3 | Schéma de test mutualisé | 2 | 3 | ~5.5h | 1 | 🔲 |
| US-023 | Raffinements Review (fériés mobiles + audit étendu) | 5 | 5 | ~10h | 2 | 🔲 |
| US-041 | Plan de charge — capacité vs charge ferme | 5 | 5 | ~11.5h | 3 | 🔲 |
| US-040 | Recherche de staffing par compétence & disponibilité | 8 | 5 | ~13h | 4 | 🔲 |

**Total** : 18 tâches | ~40h | **20 points engagés** (capacité 22)

## Répartition par type (~)
| Type | Tâches | Heures |
|------|--------|--------|
| [DB] | 2 | 4h |
| [BE] | 5 | 15.5h |
| [FE-WEB] | 2 | 5.5h |
| [TEST] | 5 | 13.5h |
| [DOC]/[REV] | 4 | 4h |

*(Pas de [FE-MOB] ni API Platform : server-rendered Symfony/Twig + commande/console.)*

## Ordre d'exécution
**QUAL-3 → US-023 → US-041 → US-040** (QUAL-3 outille les tests ; US-040 dépend d'US-041).

## Invariants DoD
- Migration + RLS par nouvelle table ; UI ⇏ Infra (Deptrac) ; TDD ; `make ci` vert ; couv. ≥ 80 %.
- US-041/040 : **HAB-1** (aucun coût dans les vues planification) ; **charge probable reportée** (INV-5).
- US-023 : idempotence onboarding conservée ; audit gated (VIEW_AUDIT_LOG).

## Fichiers
- [QUAL-3](./QUAL-3-tasks.md) · [US-023](./US-023-tasks.md) · [US-041](./US-041-tasks.md) · [US-040](./US-040-tasks.md)

## Conventions
- **ID** : T-[US]-[NN] · **Statuts** : 🔲 🔄 👀 ✅ 🚫
