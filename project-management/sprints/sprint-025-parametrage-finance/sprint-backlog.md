# Sprint Backlog — Sprint 25 (Paramétrage EPIC-005 + harmonisation solde absences)

**Sprint Goal** : solder le paramétrage EPIC-005 sur le socle tailsfadmin (profils & taux, configs finance, périodes)
et harmoniser définitivement la base de décompte du solde d'absences en jours ouvrés.

| Priorité | US | Titre | Points | Dépend de | Statut |
|----------|-----|-------|--------|-----------|--------|
| 🔴 Must | US-104 | PG-PRC-01 — reskin Profils & taux (`templates/pricing/index.html.twig`) | 3 | tokens + `StatCard` G1 + `PageHeader` G4 | 🔵 To Do |
| 🔴 Must | US-105 | PG-FIN-02…05 — reskin Configs finance (dérive, devises, FEC — 4 templates) | 3 | `PageHeader` G4 + gating finance | 🔵 To Do |
| 🔴 Must | US-107 | Harmonisation du solde d'absences en jours ouvrés | 3 | `WorkingDaysCalculator` + `/api/absences/impact` (US-091b) | 🔵 To Do |
| 🟡 Should | US-106 | PG-ADM-01 — reskin Périodes (`templates/period/index.html.twig`) | 2 | `PageHeader` G4 + confirmation clôture | 🔵 To Do |

**Total engagé : 11 points** (Must 9 + Should 2).

## Chantiers / actions rétro S24 (hors points)

- **ACTION-1 (au fil de l'eau)** : statut d'US → `done` **au merge** de la PR (pas en clôture). Critère : 0 US mergée encore `ready` en fin de sprint.
- **ACTION-3 (garde-fou)** : ≤ 3 écrans reskin ouverts (US-104/105/106) ; US-107 = domaine, hors compte.
- **ACTION-4 (J2, Could)** : nettoyer le bruit du working tree (`.idea/`, `RESUME-*`, churn `tailadmin-ref/`) via `.gitignore`.

## Origine (traçabilité)

- **US-104** : `backlog-reskin-priorise.md` — EPIC-005 Should (PG-PRC-01 Profils & taux, paramétrage valorisation).
- **US-105** : `backlog-reskin-priorise.md` — EPIC-005 Should (PG-FIN-02…05 Configs finance : dérive marge/charge, devises, FEC).
- **US-106** : `backlog-reskin-priorise.md` — EPIC-005 Should (PG-ADM-01 Périodes, clôture).
- **US-107** : rétrospective S24 — ACTION-2 (dette US-091-CA2-solde, récurrente S22→S24) ; décision PO 2026-11-20 : harmoniser en jours ouvrés.

## Prochaine étape

`/project:decompose-tasks 025` (créer les tâches par US) puis `/sprint:dev US-104` (profils & taux en tête),
enchaîner US-105 (configs finance) et US-107 (harmonisation solde, en parallèle domaine), puis US-106 (périodes) en repli.
