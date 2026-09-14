# Sprint Backlog — Sprint 24 (Amorce EPIC-005 finance + suite EPIC-002 clients + enabler bouton)

**Sprint Goal** : étendre le socle reskin à la valorisation et au pilotage financier (amorce EPIC-005),
poursuivre EPIC-002 par les clients, et rendre le composant bouton pleinement adoptable (pass-through d'attributs).

| Priorité | US | Titre | Points | Dépend de | Statut |
|----------|-----|-------|--------|-----------|--------|
| 🔴 Must | US-100 | Enabler `Ui:Button` — pass-through d'attributs Stimulus + migration des boutons câblés | 3 | bundle tailsfadmin (release + bump) | 🔵 To Do |
| 🔴 Must | US-101 | PG-VAL-01 — reskin Valorisation (`templates/valuation/index.html.twig`) | 3 | US-100, tokens + `StatCard` G1 | 🔵 To Do |
| 🔴 Must | US-102 | PG-FIN-01 — reskin Tableau de bord financier (`templates/finance/index.html.twig`) | 3 | US-100, `StatCard` G1 + `PageHeader` G4 | 🔵 To Do |
| 🟡 Should | US-103 | PG-CLI-01 — reskin Liste des clients (`templates/client/index.html.twig`) | 2 | US-100, filtre/recherche Stimulus (G3) | 🔵 To Do |

**Total engagé : 11 points** (Must 9 + Should 2).

## Chantiers / actions rétro S23 (hors points)

- **ACTION-2 (J1)** : « branche d'abord » — `feature/us-XXX` créée avant toute édition ; `git branch --show-current` vérifié avant le 1er commit. Critère : 0 commit sur `main`.
- **ACTION-4 (mi-sprint)** : arbitrage PO base de décompte du solde d'absences (US-091b) — harmoniser (jours ouvrés) ou documenter l'écart (ADR/note). Reporté depuis S22.
- **Suivi CodeQL** : reconfirmer la stabilité `Analyze` ; sinon advanced-setup + `continue-on-error` documenté.

## Origine (traçabilité)

- **US-100** : rétrospective S23 — ACTION-1 (thème n°1) ; pass-through `{{ attributes }}` sur `tsf:Ui:Button` + migration des boutons câblés (dette suivie S24, Sprint Review S23).
- **US-101 / US-102** : `backlog-reskin-priorise.md` — EPIC-005 Must (PG-VAL-01 Valorisation, PG-FIN-01 Dashboard financier, gaps G1).
- **US-103** : `backlog-reskin-priorise.md` — EPIC-002/006 Should (PG-CLI-01 Liste clients, gap G3).

## Prochaine étape

`/project:decompose-tasks 024` (créer les tâches par US) puis `/sprint:dev US-100` (enabler bouton en tête), enchaîner sur US-101/102 (Must EPIC-005), puis US-103 (Should EPIC-002) en repli.
