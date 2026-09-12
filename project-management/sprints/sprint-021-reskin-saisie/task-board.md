# Task Board — Sprint 21 (Reskin parcours de saisie, EPIC-003)

> Sprint Goal : porter le parcours de saisie P1 sur le socle tailsfadmin (bundle G1/G4 puis 5 écrans), accessible et conforme aux maquettes validées.
> Mis à jour : 2026-09-12 (clôture — review vérifiée indépendamment).

## Kanban

| 🔵 To Do | 🔄 En cours (→ S22) | 👀 Review | ✅ Done | 🚫 Bloqué |
|----------|---------------------|-----------|---------|-----------|
| — | US-091 (2)* | — | US-087 (3) · US-088 (5) · US-089 (3) · US-090 (2) · US-092 (3) | — |

> **Sprint clôturé (goal ⚠️ partiellement atteint)** — bundle v1.6.0 + 6 écrans portés, code mergé. *US-091 **non Done** : Must CA « calendrier de conflits » (ABS-03) + solde dynamique (CA-2) manquants → reste à finir S22 (US-091b). Dette tracée : US-092 relance inline (CPL-04)/filtre (CPL-05), US-089 tests d'états, WCAG en CI. Voir `sprint-review.md` + `sprint-retro.md`.

## Engagement
- **Total engagé** : 18 pts · **Livrés (DoD stricte)** : ~15 pts (5/6 US complètes ; US-091 partielle reportée S22)
- **Reste** : 18 pts

## Séquencement
1. **US-087** (bundle G1/G4) — en tête ; débloque US-088 (G1+G4) et US-092 (G1).
2. **US-088** DSH-COLLAB (build) — après US-087.
3. **US-089 / US-090 / US-091** (reskin pur) — indépendants, en parallèle du bundle.
4. **US-092** Complétude — après US-087 (G1).

## Point d'entrée dev
`/sprint:dev US-087` (bundle G1/G4, cross-repo tailsfadmin), puis enchaîner sur les écrans.
