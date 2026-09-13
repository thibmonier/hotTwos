# Sprint Backlog — Sprint 23 (Ouverture reskin EPIC-002 + enabler a11y)

**Sprint Goal** : ouvrir le reskin EPIC-002 par un incrément fini — composant bouton accessible du socle,
puis reskin liste & fiche projet, et build du dashboard projets — parcours de pilotage P2 cohérent et accessible.

| Priorité | US | Titre | Points | Dépend de | Statut |
|----------|-----|-------|--------|-----------|--------|
| 🔴 Must | US-096 | Composant `Ui:Button` accessible (focus visible) — socle | 3 | bundle tailsfadmin (release+bump) | 🔵 To Do |
| 🔴 Must | US-097 | PG-PRJ-01 — reskin liste des projets | 2 | US-096, tokens tailsfadmin (G3) | 🔵 To Do |
| 🔴 Must | US-098 | PG-PRJ-02 — reskin fiche projet (onglets + cycle de vie) | 3 | US-096, PageHeader G4 ; G5 custom | 🔵 To Do |
| 🟡 Should | US-099 | DSH-PRJ — dashboard projets (build) | 5 | StatCard G1 + PageHeader G4 + services dérive/marge | 🔵 To Do |

**Total engagé : 13 points** (Must 8 + Should 5).

## Chantiers techniques rétro S22 (hors points)
- **ACTION-3 (J1)** : `php-cs-fixer --dry-run` dans `.githooks/pre-commit` (miroir CI).
- **ACTION-2 (J5)** : stabiliser CodeQL `Analyze` (disque/cache) ou `continue-on-error` documenté.

## Origine (traçabilité)
- **US-096** : rétrospective S22 — ACTION-1 (dette a11y focus transverse, WCAG 2.4.7) ; enabler des reskins.
- **US-097 / US-098 / US-099** : `backlog-reskin-priorise.md` — EPIC-002 (PG-PRJ-01 Must, PG-PRJ-02 Must, DSH-PRJ Should).

## Prochaine étape
`/project:decompose-tasks 023` puis `/sprint:dev US-096` (enabler bouton en tête), enchaîner sur US-097/098, puis US-099 en repli.
