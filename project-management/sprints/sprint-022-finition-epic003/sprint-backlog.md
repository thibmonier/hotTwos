# Sprint Backlog — Sprint 22 (Finition & durcissement EPIC-003)

**Sprint Goal** : solder le parcours de saisie (calendrier conflits absences, relance inline complétude),
attester WCAG 2.2 AA en CI, reskinner validation des temps & relances — refermer EPIC-003 avant EPIC-002.

| Priorité | US | Titre | Points | Dépend de | Statut |
|----------|-----|-------|--------|-----------|--------|
| 🔴 Must | US-091b | Absences — calendrier de conflits (ABS-03) + solde dynamique (CA-2) | 3 | WorkingDaysCalculator, ClosurePeriod | 🔵 To Do |
| 🔴 Must | US-093 | Qualité — WCAG axe/pa11y en CI + tests d'états saisie hebdo | 3 | outillage CI a11y | 🔵 To Do |
| 🟡 Should | US-092b | Complétude — relance inline (CPL-04) + filtre (CPL-05) | 2 | reminders_update | 🔵 To Do |
| 🟡 Should | US-094 | PG-VLD-01 — reskin validation des temps | 2 | tailsfadmin v1.6.0 | 🔵 To Do |
| 🟡 Should | US-095 | PG-REL-01 — reskin relances | 2 | tailsfadmin v1.6.0 | 🔵 To Do |

**Total engagé : 12 points** (Must 6 + Should 6).

## Origine (traçabilité dette S21)
- US-091b, US-092b, US-093 : soldent la dette du Sprint 21 (`sprint-status.yaml` debt sprint-021).
- US-094, US-095 : EPIC-003 Should (`backlog-reskin-priorise.md`).

## Prochaine étape
`/project:decompose-tasks 022` puis `/sprint:dev US-093` (WCAG CI + tests en tête), enchaîner sur US-091b.
