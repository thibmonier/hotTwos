# Tâches — Sprint 8 (Valorisation & authentification web)

## Vue d'ensemble

| US | Titre | Points | Tâches | Heures | Volet |
|----|-------|--------|--------|--------|-------|
| US-070 | Findings recette (build, unité, seed) | 3 | 3 | 7h | Finance/OPS |
| US-060 | Valorisation automatique (≤ 15 min) | 8 | 8 | 24h | Finance |
| US-067 | Enrichissement profil (nom/prénom) | 3 | 6 | 11h | Socle |
| US-068 | Écrans web d'authentification | 8 | 9 | 21h | Socle |
| **Total** | | **22** | **26** | **63h** | |

> **Fait marquant** : **US-060 est déjà ~70-80% implémentée** (pipeline async de valorisation, snapshot figé
> anti-rétroactif, dashboard opérationnels). Les 24h ciblent le **reste** (occupation, ventilation par projet,
> création des affectations profil↔collaborateur, projection `fact_project_revenue`). Le risque du sprint est
> donc moindre que ne le suggèrent les 22 points nominaux.

## Répartition par type

| Type | Heures | % |
|------|--------|---|
| [BE] | 26h | 41% |
| [FE-WEB] | 19h | 30% |
| [TEST] | 8h | 13% |
| [DB] | 5h | 8% |
| [OPS] | 3h | 5% |
| [REV] | 2h | 3% |
| **TOTAL** | **63h** | |

## Ordre d'exécution conseillé

1. **T-070-01** (Dockerfile `tailwind:build`) — **en premier**, débloque `make up` (action rétro S7).
2. **US-060** : T-060-01 (affectations) → résout la cause de la valorisation vide ; puis occupation / par-projet / dashboard / projection.
3. **T-070-03** (seed profils/tarifs) + **T-070-02** (unité `/validation`) — rendent la valorisation démontrable.
4. **US-067** (profil nom/prénom) — socle, prérequis du « changement de mot de passe » US-068.
5. **US-068** (auth web) — volet ajustable selon la capacité (Should).

## Fichiers
- [US-070 — Findings recette](./US-070-tasks.md)
- [US-060 — Valorisation automatique](./US-060-tasks.md)
- [US-067 — Enrichissement profil](./US-067-tasks.md)
- [US-068 — Authentification web](./US-068-tasks.md)

> Pas de fichier `technical-tasks.md` : les tâches transverses (Dockerfile, mailer, `make db-reset`) sont
> portées par les US (T-070-01, T-068-01, T-070-03).

## Conventions
- **ID** : T-[US]-[NN] · **Taille** : 0.5h – 8h max · **Statuts** : 🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué
- **Vertical slicing** hexagonal : Domain → Application → Infrastructure → UI (+ tests). Pas de mobile (hors périmètre projet).
