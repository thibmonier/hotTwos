# Sprint Review — Sprint 3 (Première saisie de temps)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-01 |
| Animateur | Scrum Master |
| Incrément | branche `feature/sprint-3-planning` (planning + dev), CI verte |
| Staging | https://hottwos-production.up.railway.app — mode worker, migrations Sprint 3 déployées, smoke 5/5 |

## Sprint Goal

> « Un collaborateur saisit sa semaine de temps en moins de 2 minutes, et son chef de projet la valide par lot. »

**Atteint : ✅ OUI** (première valeur métier livrée — engagement `RSQ-17` honoré). Réserve : la bascule RLS prod (TECH-3) est une action ops en attente ; la mesure chronométrée ≤ 2 min est une validation d'usage à faire.

## Stories livrées

| ID | Titre | Points | Démo | Statut |
|----|-------|--------|------|--------|
| US-050 | Saisie hebdomadaire (+ référentiel Projet minimal) | 5 | ✅ | ✅ Livré |
| US-051 | Semaine ≤ 2 min (critère bloquant `RSQ-1`) | 8 | ✅ | ✅ Livré (capacité) |
| US-055 | Validation des temps par lot | 5 | ✅ | ✅ Livré |
| TECH-3 | RLS prod + smoke de déploiement | 5 | ✅ | ⚠️ code + smoke livrés ; bascule ops en attente |

**Points engagés : 23 · Points livrés : 23** (TECH-3 : code livré, activation prod = geste ops restant).

## Démonstration (parcours)

```gherkin
Given je suis Camille (collaboratrice) authentifiée
When j'ouvre /saisie
Then je vois la grille de ma semaine (mes projets × 7 jours, ligne Absence)
When je saisis au clavier (Entrée = valider + descendre) ou clique « Dupliquer la semaine précédente »
And je clique « Enregistrer la semaine »
Then toute la semaine (jusqu'à 5 projets × 5 jours) est enregistrée en une requête

Given je suis Marc (chef de projet) responsable du projet
When j'ouvre /validation
Then je vois les imputations en attente sur MES projets
When je sélectionne un lot et clique « Valider » (ou « Refuser » avec motif)
Then elles sont validées/refusées ; hors de mon périmètre → 403 ; refus sans motif → 422
```

## Métriques

| Métrique | Valeur |
|----------|--------|
| Points livrés | 23 / 23 |
| Tests | 112 / 249 assertions (tous verts) |
| Migrations | 3 nouvelles (project, time_entry, statut de validation, LOGIN rôle) — schema:validate in sync |
| PHPStan / Deptrac | niveau max / 0 violation |
| Staging | worker + migrations déployées, smoke 5/5 |

## Cadrage / reports (documentés)

| Élément | Report |
|---------|--------|
| Valorisation (chiffrage €) | Sprint 4 — dépend des taux (US-011) |
| Référentiel Projet riche (lots, jalons, budgets) | US-030/031/033 ultérieurs ; Projet minimal au Sprint 3 |
| Affectation / restriction d'imputation | US-037 ultérieur |
| Absences complètes (types, compteurs) | US-054 ; ligne Absence minimale au Sprint 3 |
| Mesure chronométrée ≤ 2 min (E2E navigateur) | harnais Panther/Playwright à installer |
| Bascule RLS prod (`DATABASE_URL` → `hotones_app`) | geste ops Railway (préparé, procédure au runbook) |
| Extension RLS aux tables métier | DBT-SEC-1, après bascule |

## Feedback des parties prenantes

_À collecter :_
1. Le parcours de saisie répond-il à l'objectif d'adoption (`RSQ-1`) ? (à confirmer par un usage réel chronométré)
2. La validation par lot couvre-t-elle le besoin du chef de projet (< 5 min) ?
3. Priorité Sprint 4 : valorisation (US-060 + taux US-011) ?

## Prochaines étapes

1. Rétrospective Sprint 3.
2. PR `feature/sprint-3-planning → main`.
3. Bascule RLS prod (ops) + vérif smoke.
4. Sprint 4 : valorisation automatique après validation.
