# Tâches — Sprint 25 (Paramétrage EPIC-005 + harmonisation solde absences)

> Décomposition des User Stories du Sprint 25 en tâches techniques.
> Généré au kickoff 2026-11-20 — **validation PO au planning J1**.

## Vue d'ensemble

| US | Titre | Points | Tâches | Heures | Fichier |
|----|-------|--------|--------|--------|---------|
| US-104 | PG-PRC-01 — reskin Profils & taux | 3 | 4 | ~7h | [US-104-tasks.md](US-104-tasks.md) |
| US-105 | PG-FIN-02…05 — reskin Configs finance | 3 | 5 | ~8.5h | [US-105-tasks.md](US-105-tasks.md) |
| US-106 | PG-ADM-01 — reskin Périodes | 2 | 3 | ~4.5h | [US-106-tasks.md](US-106-tasks.md) |
| US-107 | Harmonisation solde d'absences (jours ouvrés) | 3 | 6 | ~9.5h | [US-107-tasks.md](US-107-tasks.md) |
| — | Tâches techniques (rétro S24) | — | 3 | ~2h | [technical-tasks.md](technical-tasks.md) |

**Total : 21 tâches · ~34h · 11 points.**

## Types de tâches

| Type | Description |
|------|-------------|
| [FE-WEB] | Front web (Twig, Stimulus, tokens tailsfadmin) |
| [BE] | Back-end (domaine, application) |
| [DB] | Base de données (migration, backfill) |
| [TEST] | Tests (PHPUnit fonctionnels/unitaires) |
| [DOC] | Documentation (ADR/note) |
| [REV] | Code review |
| [OPS] | Outillage / process |

## Conventions du sprint (rétro S24)

- **ACTION-1** : basculer le statut d'US à `done` **au merge** de la PR.
- **ACTION-3** : ≤ 3 écrans reskin ouverts (US-104/105/106) ; US-107 = domaine.
- **Branche d'abord** : `feature/us-XXX-…` créée avant toute édition ; `git push origin <branche>` explicite.
- **`make ci` vert avant push** (cs · rector · phpstan max · deptrac · tests).

## Point d'entrée
`/sprint:dev US-104`
