# Tâches - Sprint 019 (Conception UX — EPIC-013)

> **Sprint de conception** : livrables **documentaires** (référentiel, parcours, mapping, backlog).
> Pas de code produit → types adaptés `[DOC]` (rédaction/analyse) et `[REV]` (validation PO).
> Les types stack habituels (`[DB]/[BE]/[FE-WEB]/[FE-MOB]/[TEST]/[OPS]`) ne s'appliquent pas.

## Vue d'ensemble

| US | Titre | Points | Tâches | Heures | Statut |
|----|-------|--------|--------|--------|--------|
| US-080 | Référentiel exhaustif des pages cibles | 5 | 5 | 12h | 🔲 Ready |
| US-081 | Cartographie des parcours par persona (P1–P6) | 5 | 5 | 12h | 🔲 Ready |
| US-083 | Mapping pages ↔ composants tailsfadmin (+ gaps) | 3 | 4 | 9h | 🔲 Ready |
| US-085 | Backlog de refonte/reskin priorisé (MoSCoW) | 3 | 3 | 5h | 🔲 Ready |

**Total** : 17 tâches | 38h | 16 points

## Répartition par type

| Type | Tâches | Heures | % |
|------|--------|--------|---|
| [DOC] | 13 | 34h | 89 % |
| [REV] | 4 | 4h | 11 % |

## Ordre d'exécution (chaîne stricte)

`US-080` (référentiel) → `US-081` (parcours) → `US-083` (mapping ↔ composants) → `US-085` (backlog priorisé).

> **US-080 est une pré-condition stricte** : sa couverture doit être validée PO à 100 % avant de démarrer US-081 et US-083.

## Livrables produits (dans `project-management/architecture/`)

| US | Livrable |
|----|----------|
| US-080 | `page-inventory.md` |
| US-081 | `parcours-personas.md` (diagrammes Mermaid P1–P6) |
| US-083 | `page-component-mapping.md` + liste des *gaps* (issues bundle) |
| US-085 | `backlog-reskin-priorise.md` |

## Reporté (hors sprint — accès `hotones` requis)

| US | Titre | Points |
|----|-------|--------|
| US-082 | Audit & critique UX de l'existant (inspiré hotones) | 5 |
| US-084 | Maquettes haute-fidélité validées PO | 8 |

## Fichiers
- [US-080 - Référentiel pages cibles](./US-080-tasks.md)
- [US-081 - Parcours par persona](./US-081-tasks.md)
- [US-083 - Mapping pages ↔ composants](./US-083-tasks.md)
- [US-085 - Backlog reskin priorisé](./US-085-tasks.md)

## Conventions
- **ID** : T-[US]-[Numéro] (ex : T-080-02)
- **Taille** : 0.5h – 8h max
- **Statuts** : 🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué
