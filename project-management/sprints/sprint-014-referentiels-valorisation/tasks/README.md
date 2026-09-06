# Tâches — Sprint 14 (Référentiels de valorisation)

## Vue d'ensemble

| US | Titre | Points | Tâches | Statut |
|----|-------|--------|--------|--------|
| US-015 | Taux de vente multi-niveaux + priorité (EF-REF-19) | 5 | 7 | 🔲 |
| US-016 | Devises & devise de référence (EF-REF-22) | 3 | 6 | 🔲 |
| US-079a | Export tableau de pilotage CSV (EF-PRJ-14) | ~3 | — | 🔲 |
| US-079c | Courbe d'atterrissage historisée (EF-PRJ-16) | ~5 | — | 🔲 |
| US-079b | Seuil de dérive par type (EF-PRJ-15) | ~5 | — | 🟢 stretch/S15 |

**Engagé ≈ 16 pts** (US-015 + US-016 + US-079a + US-079c). US-079b en réserve S15.

## ⚠️ Note Tech Lead
US-079 (parapluie, 8 pts) couvre 3 capacités ≈ 13 pts réelles → S14 livre **export + courbe** (finissent
l'essentiel d'EPIC-002) ; **seuil-par-type différé S15** (raffinement de l'alerte déjà fonctionnelle US-036
+ seuil tenant US-018).

## Fichiers
- [US-015 — Taux de vente multi-niveaux](./US-015-tasks.md)
- [US-016 — Devises](./US-016-tasks.md)
- [US-079 — Raffinements pilotage (a/c/b)](./US-079-tasks.md)
- [Tâches techniques](./technical-tasks.md)

## Conventions
- **ID** : T-[US]-[NN] · heures (0,5-8h) · statuts 🔲 🔄 👀 ✅ 🚫.
- **Ordre** : US-015 → US-016 → US-079a → US-079c (→ US-079b si capacité).
- **Réutilisation** : socle `Pricing` (`RateResolver`, `ProfileRate`, `EffectivePeriod`, pattern `DefineProfileRate`), `ChargeLandingCalculator`/`ViewProjectBudgetTracking`.
