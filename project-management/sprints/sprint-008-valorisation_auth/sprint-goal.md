# Sprint 8 : Valorisation démontrable & authentification web (EPIC-003 / EPIC-000 / EPIC-012)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 8 |
| Planifié le | 2026-09-03 |
| Début | 2026-11-24 |
| Fin | 2026-12-05 |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~22 points (1 dev — vélocité S1-S7 : 29/20/23/21/22/21/33 → moy. récente ~24, sécurité 10%) |
| Base git | `main` (après clôture S7 : review + rétro) |

## Sprint Goal

> « La **valorisation du temps validé est calculée automatiquement et démontrable** sur le jeu de démo, les
> **irritants de recette** (build, unités, seed) sont résorbés, et les utilisateurs disposent d'**écrans
> d'authentification web** et d'un **profil enrichi** (nom/prénom). »

> **Périmètre à deux volets** (décision PO — combiner finance/valorisation + auth/profil). Priorité au volet
> **finance** (dette S4 + findings recette), puis **auth/profil web**. Volet auth conditionné à la capacité.

## Definition of Done (rappel + ajout rétro S7)

- [ ] Code review approuvée (`symfony-reviewer` / `php-reviewer` en clôture de lot)
- [ ] Tests (couverture ≥ 80%), `make ci` vert (PHPStan max, Deptrac, gitleaks)
- [ ] Documentation & ADR si décision structurante
- [ ] **Recette navigateur sur données peuplées** (action rétro S7) tracée dans `.recette/`
- [ ] Pas de dette technique ajoutée · déployable

## Sprint Backlog (candidat)

| Priorité | ID | Titre | Points | EPIC | Statut |
|----------|-----|-------|--------|------|--------|
| 🔴 Must | US-070 | Findings recette (build Dockerfile, unité `/validation`, seed valorisation) | 3 | EPIC-012 | 🔵 To Do |
| 🔴 Must | US-060 | Valorisation automatique du temps validé | 8 | EPIC-003 | 🔵 To Do |
| 🟡 Should | US-067 | Enrichissement profil (nom/prénom) | 3 | EPIC-000 | 🔵 To Do |
| 🟡 Should | US-068 | Écrans d'authentification web | 8 | EPIC-000 | 🔵 To Do |

**Total candidat : 22 points** (capacité ~22 — sprint chargé, volet auth ajustable).

## Ordre d'exécution conseillé

1. **US-070 / T-070-01** (build `make up`) — **en premier** : débloque l'outillage de dev (action rétro S7).
2. **US-060** (valorisation auto) — cœur du volet finance ; résout structurellement le finding F2.
3. **US-070 / T-070-03** (seed profils/tarifs) + **T-070-02** (unité `/validation`) — rendent la valorisation démontrable.
4. **US-067** puis **US-068** — volet auth/profil (si capacité).

## Dépendances

| US | Dépend de | Note |
|----|-----------|------|
| US-070 T-070-03 (seed valo) | US-060 (calcul auto) | Le seed peuple ce que la valorisation calcule |
| US-060 | modèle profile/profile_rate/fact_project_revenue | Vérifier l'état du modèle finance existant |
| US-068 | US-067 (profil enrichi) | Écrans auth cohérents avec le profil |

## Risques identifiés

| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| Sprint chargé (22 pts, 2 volets) | Moyenne | Moyen | Volet auth (US-067/068) ajustable ; prioriser finance |
| Modèle finance US-060 plus complexe que prévu | Moyenne | Fort | Spike/analyse en début de sprint (règle 01) |
| Build `make up` bloque le dev | Faible | Fort | **T-070-01 traité en premier** |

## Cérémonies

| Cérémonie | Timing |
|-----------|--------|
| Planning P1/P2 | Début S8 |
| Daily | Quotidien |
| Affinage | Mi-sprint |
| **Review + Rétro** | **Fin de sprint** (action rétro S7 : ne plus les mettre en dette) |

## Notes

Issu du déroulé classique repris après clôture S7. Le volet finance clôt la **dette S4** (US-060 jamais livrée)
et transforme les findings de recette peuplée (`.recette/reports/REC-20260903-us069-report.md`) en valeur
démontrable. Décomposition en tâches : `/project:decompose-tasks 008`.
