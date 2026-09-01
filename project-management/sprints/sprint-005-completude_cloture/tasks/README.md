# Tâches — Sprint 005 (Complétude et clôture du cycle temps)

## Vue d'ensemble

| US | Titre | Points | Tâches | Heures | Statut |
|----|-------|--------|--------|--------|--------|
| US-057 | Clôture de période et traçabilité | 5 | 9 | 28h | 🔲 |
| US-054 | Déclaration/validation/compteurs d'absences | 5 | 8 | 25h | 🔲 |
| US-058 | Tableau de complétude de saisie | 3 | 6 | 18h | 🔲 |
| US-056 | Relances automatiques de retard | 3 | 7 | 18h | 🔲 |
| US-052 | Saisie quotidienne sur mobile (web responsive) | 3 | 6 | 16h | 🔲 |
| US-059 | Synthèse d'activité & planning depuis la saisie | 3 | 6 | 16h | 🔲 |
| Tech | Hardening `set_config` + fixtures démo | — | 2 | 5h | 🔲 |

**Total : 44 tâches | ~126h | 22 points**

## Répartition par type (indicative)

| Type | Tâches | ~Heures |
|------|--------|---------|
| [DB] | 5 | 13h |
| [BE] | 16 | 51h |
| [FE-WEB] | 11 | 36h |
| [TEST] | 6 | 21h |
| [DOC][REV] | 6 | 8h |
| [OPS] | — | (fixtures) |

## Fichiers
- [US-057 — Clôture de période](./US-057-tasks.md) 🔴 Must (anchor rétro — lève le stub US-060)
- [US-054 — Absences](./US-054-tasks.md) 🔴 Must (HAB-3 données de santé)
- [US-058 — Complétude](./US-058-tasks.md) 🔴 Must
- [US-056 — Relances](./US-056-tasks.md) 🟡 Should (nouveau handler async)
- [US-052 — Saisie mobile](./US-052-tasks.md) 🟡 Should
- [US-059 — Synthèse activité](./US-059-tasks.md) 🟢 Could (planning dégradé — US-037 absente)
- [Tâches techniques](./technical-tasks.md)

## Ordre de démarrage recommandé
1. **T-TECH-03** (hardening `set_config`) — socle tenant-session propre.
2. **US-057** (clôture) — raccorde le vrai verrou au recompute US-060 ; débloque la sémantique de période.
3. **US-054** (absences) puis **US-058** (complétude, consomme absences) puis **US-056** (relances, consomme complétude).
4. **US-052** / **US-059** (UI saisie) en parallèle des FE des autres US.

## Conventions
- **ID** : `T-<US>-<NN>` · **Taille** : 0.5h – 8h · **Statuts** : 🔲 🔄 👀 ✅ 🚫
- **DoD process** : tout handler async écrivant en base ⇒ test d'intrusion **RLS via consume** (rétro S4).
- Slices verticales (Domain → Application → Infra/API → FE → Test), hexagonal + RLS + `authorizeSensitiveRead` pour les données sensibles.
