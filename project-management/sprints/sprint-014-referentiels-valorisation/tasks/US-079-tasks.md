# Tâches — US-079 : Raffinements pilotage (export / courbe / seuil)

## Informations US
- **Epic** : EPIC-002 · **Persona** : P2 / Direction · **Points** : 8 (parapluie, ~13 réels → découpée) · **Sprint** : 14 · **EF** : EF-PRJ-14/15/16

> **Découpage** : **US-079a** (export, EF-PRJ-14) + **US-079c** (courbe, EF-PRJ-16) livrés en S14 ;
> **US-079b** (seuil par type, EF-PRJ-15) → **stretch/S15**.

## US-079a — Export tableau de pilotage (CSV, EF-PRJ-14)
| ID | Type | Tâche | Est. | Statut |
|----|------|-------|------|--------|
| T-079-01 | [FE-WEB] | Route/contrôleur export CSV : budget courant, consommé, RAF, atterrissage, écart par lot + total projet ; gating HAB-1 (colonnes coût réservées `VIEW_COLLABORATOR_COST`) | 3h | 🔲 |
| T-079-02 | [TEST] | Functional : contenu CSV + gating (coût masqué sans habilitation) | 1.5h | 🔲 |

## US-079c — Courbe d'atterrissage historisée (EF-PRJ-16)
| ID | Type | Tâche | Est. | Statut |
|----|------|-------|------|--------|
| T-079-03 | [DB] | `ChargeLandingSnapshot` (tenant, projet, période, landingCostCents, overrunPercent, figé le) + port + migration RLS | 2h | 🔲 |
| T-079-04 | [BE] | Historiser l'atterrissage à la clôture (hook `ComputeProjectMargins::forClosedPeriod`) + lecture série | 2.5h | 🔲 |
| T-079-05 | [FE-WEB] | Courbe/évolution dans l'onglet Suivi budgétaire (série de snapshots) | 2.5h | 🔲 |
| T-079-06 | [TEST] | Unit snapshot + functional courbe | 2h | 🔲 |
| T-079-07 | [REV] | Revue (a + c) | 0.5h | 🔲 |

## US-079b — Seuil de dérive par type + 2e seuil (EF-PRJ-15) — 🟢 stretch/S15
| ID | Type | Tâche | Est. | Statut |
|----|------|-------|------|--------|
| T-079-08 | [BE/FE] | Seuil de dérive charge **par type de projet** (remplace les constantes `ChargeLandingCalculator`) + 2e seuil (escalade direction) + config | ~5h | 🟢 |

## Notes
- Export : réutiliser `ViewProjectBudgetTracking` (budget courant + atterrissage). Colonnes coût gated (HAB-1).
- Courbe : le snapshot se fige au même moment que `ProjectMargin` (clôture de période) → série cohérente.
- Seuil par type : généralise la décision S12 (constantes OBJ-2 10 %/50 %) et le pattern tenant d'US-018.
