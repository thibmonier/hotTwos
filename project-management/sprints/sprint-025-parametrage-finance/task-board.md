# Task Board — Sprint 25 (Paramétrage EPIC-005 + harmonisation solde absences)

> Sprint Goal : solder le paramétrage finance sur le socle (profils/taux, configs, périodes) + harmoniser le solde d'absences en jours ouvrés.
> Mis à jour : 2026-12-04 (CLÔTURÉ ✅ — 4/4 US, 11/11 pts, `goal_met: true`). Review + rétro #163.

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🔲 À Faire

| ID | US | Tâche | Type | Est. |
|----|-----|-------|------|------|
| T-104-01 | US-104 | Reskin `pricing/index` (tokens + StatCard G1 + PageHeader G4 + Button) | [FE-WEB] | 3h |
| T-104-02 | US-104 | Passe hover:/dark: + vérif build (`app.built.css`) | [FE-WEB] | 1h |
| T-104-03 | US-104 | Suite `pricing` (accès + taux + affectations + gating) + assertions reskin | [TEST] | 2h |
| T-104-04 | US-104 | Code review | [REV] | 1h |
| T-105-01 | US-105 | Patron commun config (PageHeader + form tokenisé) + reskin dérive marge & charge | [FE-WEB] | 2.5h |
| T-105-02 | US-105 | Reskin config devises + config FEC | [FE-WEB] | 2h |
| T-105-03 | US-105 | Passe hover:/dark: + vérif build | [FE-WEB] | 1h |
| T-105-04 | US-105 | Suites config (gating 403 + POST/CSRF) + assertions reskin | [TEST] | 2h |
| T-105-05 | US-105 | Code review | [REV] | 1h |
| T-107-01 | US-107 | Test de non-régression du solde (RED) — comportement actuel + cas divergent | [TEST] | 1.5h |
| T-107-02 | US-107 | Aligner le compteur persisté sur jours ouvrés (source unique) | [BE] | 3h |
| T-107-03 | US-107 | Migration/backfill des soldes existants (si persistés) | [DB] | 1.5h |
| T-107-04 | US-107 | Tests domaine + fonctionnels (solde affiché == impact jours ouvrés) | [TEST] | 2h |
| T-107-05 | US-107 | Note/ADR : source unique de décompte du solde | [DOC] | 0.5h |
| T-107-06 | US-107 | Code review | [REV] | 1h |
| T-106-01 | US-106 | Reskin `period/index` + action clôture en `tsf:Ui:Button` (confirmation) | [FE-WEB] | 2h |
| T-106-02 | US-106 | Suite `period` (liste + clôture + gating) + assertions reskin | [TEST] | 1.5h |
| T-106-03 | US-106 | Code review | [REV] | 1h |
| T-TECH-01 | — | ACTION-4 : nettoyage bruit working tree (`.gitignore`) | [OPS] | 1h |
| T-TECH-02 | — | Suivi stabilité CodeQL `Analyze` | [OPS] | 0.5h |
| T-TECH-03 | — | ACTION-1 : statut US → `done` au merge (rappel process) | [OPS] | 0.5h |

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
- **Tâches** : 21 total | 21 terminées (100 %)
- **Points** : 11 engagés | **11 livrés** (Must 9 + Should 2) — DoD stricte
- **US** : 4 / 4 livrées et mergées (#158, #159, #160, #161 ; statuts #162)
- **Qualité** : `make ci` vert (cs · rector · phpstan max · deptrac 0 violation · 724 tests) ; ADR-0024 (US-107)

## Séquencement
1. **US-104** (profils & taux) — patron finance déjà rodé (S24).
2. **US-105** (configs finance) — patron commun sur 4 templates.
3. **US-107** (harmonisation solde) — domaine, en parallèle (TDD : test de non-régression d'abord).
4. **US-106** (périodes) — repli.
5. **T-TECH-01/03** dès J1-J2 ; T-TECH-02 en cours de sprint.

## Point d'entrée dev
`/sprint:dev US-104`
