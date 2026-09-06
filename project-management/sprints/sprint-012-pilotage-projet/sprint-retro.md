# Rétrospective — Sprint 12 (Pilotage projet, EPIC-002)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-06 |
| Format | Starfish |
| Facilitateur | Scrum Master |

## Directive Fondamentale

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des ressources disponibles, et de la situation. » — Norman Kerth

## Rappel du Sprint

- **Sprint Goal atteint** (✅ 100 %) : pilotage charge — avancement/RAF + atterrissage + dérive précoce (OBJ-2).
- **16/16 pts Must** (US-035, US-036) + dette Should soldée (libellés, T-R01, MAILER). PR #63 → #66. 558 → 580 tests.
- Sprint **entièrement affiné en cours de route** : les stories n'existaient pas au démarrage.

## ⭐ Observations (Starfish)

### 🟢 Continuer
- **Refuser d'exécuter un sprint non Ready** : au démarrage, `run-sprint` a été bloqué par le gate (0 story Ready). Plutôt que générer du code contre des stories vides, on a affiné d'abord (réconciliation EPIC + Gherkin + décomposition), puis exécuté. Le gate a joué son rôle.
- **Réutilisation du moteur de suivi budgétaire** : l'atterrissage charge s'est branché sur `ViewProjectBudgetTracking` / `projectBreakdownFor` / le pattern seuil existant, sans dupliquer de moteur (ARC-6).
- **Calculateur pur + DTO testés en matrice** : `ChargeLandingCalculator` couvert par la matrice OBJ-2 (dépassement × consommation) — lisible et exhaustif.
- **1 PR par story, CI verte avant merge**, gate couverture actif.

### 🟡 Commencer
- **Vérifier la sémantique d'un libellé avant de le « corriger »** : la dette T-DET-01 visait `/finance` **et** `/valorisation`. En vérifiant la source de données, `/valorisation` affiche la valorisation brute (CA reconnu réel) → le renommer aurait été **faux**. On n'a corrigé que `/finance` (qui lit `ProjectMargin` retenu). Toujours tracer la donnée jusqu'à sa source avant de toucher au libellé.

### 🔴 Arrêter
- **Se fier au frontmatter/docs d'EPIC comme source de vérité** : la table de stories d'EPIC-002 était périmée (numérotation divergente de `sprint-status.yaml`). Reconduit du constat S9 : `sprint-status.yaml` est la seule source fiable ; réconcilier les docs EPIC au fil de l'eau.

### ⬆️ Plus de
- **Décisions de périmètre explicites et documentées** : compteur `/finance` différé (incohérence marge figée vs avancement courant), seuils OBJ-2 en constantes (pas d'entité configurable) — tracées dans la story, pas décidées en silence.

### ⬇️ Moins de
- **Frictions outillage résiduelles** : cs-fixer a exigé le multi-lignes d'un appel à N arguments dans un test ; PHPStan `cast.string` sur un `mixed` de `Request`. Anticiper : arguments un par ligne, garder `is_scalar` avant tout cast d'un `mixed`.

## 📚 Learnings clés

- **Un gate qui bloque tôt vaut mieux qu'un sprint bâclé** : l'échec de `run-sprint` sur un backlog non Ready a évité de coder à l'aveugle ; l'affinage préalable a produit un scope net.
- **Tracer la donnée jusqu'à sa source avant de renommer** : deux vues affichant « CA reconnu » ne portent pas la même donnée (valorisation brute vs marge retenue).
- **INV-4 par construction** : avancement, RAF et consommation stockés/calculés séparément ; l'atterrissage n'utilise que l'avancement + la consommation, jamais l'un déduit de l'autre.
- **Rappels outillage (reconduits)** : tout en Docker ; `make cache-dev` après `cache:clear` ; UI ne référence jamais l'Infra ; toute nouvelle entité requêtée par `ProjectPageController::show()` doit être dans le schéma des tests fonctionnels.

## 🎯 Actions Sprint 13

| # | Action | Priorité |
|---|--------|----------|
| 1 | Décision PO : compléter EPIC-002 (US-033 budget charge, US-032 projets internes, US-037 avenants) vs autre EPIC | À trancher (PO) |
| 2 | Évaluer un seuil de dérive charge **paramétrable par tenant** (façon US-018) | Basse |
| 3 | Cadrer un éventuel indicateur consolidé de dérive charge sur `/finance` | Basse |
| 4 | Vérifier effectivement `MAILER_DSN` en staging + recette e-mail (doc posée, exécution restante) | Moyenne |

## Suivi des actions Sprint 11

| Action S11 | Statut |
|-----------|--------|
| Harmoniser libellés « CA reconnu » → « Revenu retenu » | ✅ **Fait** (T-DET-01, `/finance` uniquement — `/valorisation` correct en l'état) |
| Corriger T-R01 (onglet « Suivi budgétaire ») | ✅ **Fait** (`connect()` de synchro initiale) |
| `MAILER_DSN` staging + reset e2e | 🟡 **Doc posée** (runbook) ; exécution/recette e-mail en staging restante (Action 4) |
| Décision prochaine tranche EPIC-005 vs nouvel EPIC | ✅ **Fait** — bascule sur EPIC-002 (pilotage charge) |

## Check-out

ROTI (auto-évaluation solo) : **5/5** — sprint affiné et livré à 100 %, dette chronique (T-R01, MAILER) enfin soldée, deux décisions de périmètre pièges évitées (libellé /valorisation, compteur /finance).
À emporter : « Avant de corriger un libellé, remonte à la donnée : deux écrans identiques ne disent pas forcément la même chose. »
