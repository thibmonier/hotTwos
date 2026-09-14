# Task Board — Sprint 23 (Ouverture reskin EPIC-002 + enabler a11y)

> Sprint Goal : incrément fini EPIC-002 — bouton accessible du socle, reskin liste & fiche projet, build dashboard projets.
> Mis à jour : 2026-11-06 (CLÔTURÉ — 4/4 US, 13/13 pts).

## Kanban

| 🔵 To Do | 🔄 En cours | 👀 Review | ✅ Done | 🚫 Bloqué |
|----------|-------------|-----------|---------|-----------|
| — | — | — | US-096 (3) #146/#41 · US-097 (2) #147 · US-098 (3) #148 · US-099 (5) #149 | — |

## Engagement
- **Total engagé** : 13 pts (Must 8 + Should 5)
- **Livrés** : 13 pts (100 % DoD stricte) — `goal_met: true` ; + T-TECH-01 #145, T-TECH-02 (cache CodeQL)

## Séquencement
1. **US-096** (Ui:Button a11y) — en tête : enabler socle, solde la dette focus (WCAG 2.4.7).
2. **US-097 / US-098** (Must) — reskin liste + fiche projet, adoptent le bouton.
3. **US-099** (Should, build) — dashboard projets (StatCard/PageHeader + alertes dérive) ; repli.

## Chantiers techniques (rétro S22, hors points)
- ACTION-3 (J1) : php-cs-fixer au hook pre-commit.
- ACTION-2 (J5) : stabiliser CodeQL `Analyze`.

## Point d'entrée dev
`/project:decompose-tasks 023` puis `/sprint:dev US-096`
