# Task Board — Sprint 14 (Référentiels de valorisation)

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🔲 À Faire

### US-015 — Taux de vente multi-niveaux (Must, 5 pts)
| ID | Tâche | Est. |
|----|-------|------|
| T-015-01 | [DB] `SellingRate` + port | 2.5h |
| T-015-02 | [DB] Migration + impl Doctrine | 2h |
| T-015-03 | [BE] `SellingRateResolver` (priorité) | 2.5h |
| T-015-04 | [BE] `DefineSellingRate` (gated) | 2h |
| T-015-05 | [FE-WEB] UI surcharges + règle appliquée | 3h |
| T-015-06 | [TEST] Unit + functional | 3h |
| T-015-07 | [REV] Revue | 0.5h |

### US-016 — Devises (Should, 3 pts)
| ID | Tâche | Est. |
|----|-------|------|
| T-016-01 | [DB] Currency/ExchangeRate/devise réf + migration | 3h |
| T-016-02 | [BE] `CurrencyConverter` | 2h |
| T-016-03 | [BE] Use case config | 1.5h |
| T-016-04 | [FE-WEB] UI config | 2h |
| T-016-05 | [TEST] Unit + functional | 2.5h |
| T-016-06 | [REV] Revue | 0.5h |

### US-079a — Export CSV (Must, ~3 pts)
| ID | Tâche | Est. |
|----|-------|------|
| T-079-01 | [FE-WEB] Export CSV pilotage (gating HAB-1) | 3h |
| T-079-02 | [TEST] Functional export | 1.5h |

### US-079c — Courbe d'atterrissage (Must, ~5 pts)
| ID | Tâche | Est. |
|----|-------|------|
| T-079-03 | [DB] `ChargeLandingSnapshot` + migration | 2h |
| T-079-04 | [BE] Historisation (hook clôture) + série | 2.5h |
| T-079-05 | [FE-WEB] Courbe onglet Suivi budgétaire | 2.5h |
| T-079-06 | [TEST] Unit + functional | 2h |
| T-079-07 | [REV] Revue (a+c) | 0.5h |

## 🟢 Stretch / S15
| ID | Tâche | Est. |
|----|-------|------|
| T-079-08 | US-079b Seuil de dérive par type + 2e seuil direction (EF-PRJ-15) | ~5h |

## 🔄 En Cours
| ID | Tâche | Démarré |
|----|-------|---------|

## 👀 En Review
| ID | Tâche | Reviewer |
|----|-------|----------|

## ✅ Terminé
| ID | Tâche | Terminé |
|----|-------|---------|
| Setup | Affinage EPIC-001 + décomposition S14 | 2026-09-06 |

## Ordre d'exécution
1. **US-015** (taux multi-niveaux) → 2. **US-016** (devises) → 3. **US-079a** (export) → 4. **US-079c** (courbe). US-079b en réserve S15.

## Métriques
- **Engagé** : ~16 pts (US-015 5 + US-016 3 + US-079a ~3 + US-079c ~5). Stretch : US-079b (~5) → S15.
