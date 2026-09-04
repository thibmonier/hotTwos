# Tâches — Sprint 9 (Finance & rentabilité, EPIC-005)

## Vue d'ensemble

| US | Titre | Points | Tâches | Heures | Statut |
|----|-------|--------|--------|--------|--------|
| US-071 | Moteur de marge réelle par projet à la clôture | 8 | 9 | 20h | 🔲 |
| US-072 | Budget vs réalisé + alerte de dérive financière | 5 | 5 | 11h | 🔲 |
| US-073 | Tableau de bord finance consolidé (direction) | 8 | 7 | 18h | 🔲 |
| US-074 | Export comptable configurable (**réserve**) | 5 | 5 | 9.5h | 🔲 |
| — | Tâches techniques transverses (dette rétro) | — | 3 | 6h | 🔲 |

**Engagé (US-071+072+073 + transverse) : 21 pts · 55h** · **Réserve (US-074) : 9.5h**

## Répartition par type (engagé, hors réserve)

| Type | Tâches | Heures | % |
|------|--------|--------|---|
| [DOC] | 1 | 1h | ~2% |
| [DB] | 2 | 5h | ~9% |
| [BE] | 9 | 23h | ~42% |
| [FE-WEB] | 3 | 9h | ~16% |
| [TEST] | 3 | 8h | ~15% |
| [OPS] | 3 | 6h | ~11% |
| [REV] | 3 | 3h | ~5% |

## Fichiers
- [US-071 — Moteur de marge](./US-071-tasks.md)
- [US-072 — Budget vs réalisé + dérive](./US-072-tasks.md)
- [US-073 — Dashboard finance consolidé](./US-073-tasks.md)
- [US-074 — Export comptable (réserve)](./US-074-tasks.md)
- [Tâches techniques transverses](./technical-tasks.md)

## Conventions
- **ID** : T-[US]-[Numéro] (ex. T-071-04) ; T-TECH-XX pour le transverse.
- **Taille** : 0.5h – 8h max.
- **Stack réelle** : Symfony 8 / PHP 8.5 (DDD + hexagonal), Twig, Doctrine, PHPUnit. **Mobile (Flutter) hors périmètre** (lecture desktop, cohérent Sprint 8).
- **Ordre conseillé** : ADR facturable (T-071-08) → US-071 → US-072 → US-073 ; T-TECH-01 (recette) tôt ; US-074 en réserve.
