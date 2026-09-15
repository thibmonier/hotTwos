# Task Board — Sprint 24 (Amorce EPIC-005 finance + suite EPIC-002 clients + enabler bouton)

> Sprint Goal : amorce finance (valorisation + pilotage financier) + suite EPIC-002 (clients) + bouton pleinement adoptable.
> Mis à jour : 2026-11-20 (CLÔTURÉ ✅ — 4/4 US, 11/11 pts, `goal_met: true`). Review + rétro #156.

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🔲 À Faire

| ID | US | Tâche | Type | Est. |
|----|-----|-------|------|------|
| T-100-01 | US-100 | Bundle : pass-through `{{ attributes }}` sur Ui:Button | [OPS] | 2h |
| T-100-02 | US-100 | Release bundle v1.6.2 + bump Composer | [OPS] | 1h |
| T-100-03 | US-100 | Migration boutons câblés (complétude/validation/absences/relances) | [FE-WEB] | 3h |
| T-100-04 | US-100 | Tests pass-through + suites écrans + a11y 2.4.7 | [TEST] | 2h |
| T-100-05 | US-100 | Doc pass-through | [DOC] | 0.5h |
| T-100-06 | US-100 | Code review | [REV] | 1h |
| T-101-01 | US-101 | Reskin `valuation/index` sur tokens | [FE-WEB] | 3h |
| T-101-02 | US-101 | StatCard (G1) + PageHeader (G4) + Button | [FE-WEB] | 2h |
| T-101-03 | US-101 | Passe hover:/dark: + vérif build | [FE-WEB] | 1h |
| T-101-04 | US-101 | Tests (accès/breakdown) + assertions reskin | [TEST] | 2h |
| T-101-05 | US-101 | Code review | [REV] | 1h |
| T-102-01 | US-102 | Reskin `finance/index` sur tokens | [FE-WEB] | 2.5h |
| T-102-02 | US-102 | StatCard (G1) + PageHeader (G4) + Button | [FE-WEB] | 2h |
| T-102-03 | US-102 | Passe hover:/dark: + vérif build | [FE-WEB] | 1h |
| T-102-04 | US-102 | `FinanceDashboardTest` + gating finance (403) | [TEST] | 2h |
| T-102-05 | US-102 | Code review | [REV] | 1h |
| T-103-01 | US-103 | Reskin `client/index` + bouton création (gating) | [FE-WEB] | 2h |
| T-103-02 | US-103 | Filtre + recherche Stimulus (G3) | [FE-WEB] | 2h |
| T-103-03 | US-103 | `ClientPageTest` (reskin + filtre + gating) | [TEST] | 1.5h |
| T-103-04 | US-103 | Code review | [REV] | 1h |
| T-TECH-01 | — | Branche d'abord (garde-fou pre-commit, J1) | [OPS] | 1h |
| T-TECH-02 | — | Suivi stabilité CodeQL | [OPS] | 1h |
| T-TECH-03 | — | Arbitrage PO solde absences | [DOC] | 1h |

## 🔄 En Cours
| ID | US | Tâche | Démarré |
|----|-----|-------|---------|
| — | — | — | — |

## 👀 En Review
| ID | US | Tâche | Reviewer |
|----|-----|-------|----------|
| — | — | — | — |

## ✅ Terminé
| ID | US | Tâche | Réel | Terminé |
|----|-----|-------|------|---------|
| — | — | — | — | — |

## 🚫 Bloqué
| ID | US | Raison | Action |
|----|-----|--------|--------|
| — | — | — | — |

## Métriques
- **Tâches** : 23 total | 23 terminées (100 %)
- **Points** : 11 engagés | **11 livrés** (Must 9 + Should 2) — DoD stricte
- **US** : 4 / 4 livrées et mergées (#152, #153, #154, #155) + release bundle v1.6.2
- **Qualité** : `make ci` vert (cs · rector · phpstan max · deptrac 0 violation · 721 tests)

## Séquencement
1. **US-100** (enabler) — bundle → release/bump → migration → tests/a11y.
2. **US-101 / US-102** (Must EPIC-005) — reskin Valorisation + Dashboard financier.
3. **US-103** (Should EPIC-002) — reskin Liste clients (repli).
4. **T-TECH-01** dès J1 (branche d'abord) ; T-TECH-02/03 en cours de sprint.

## Point d'entrée dev
`/sprint:dev US-100`
