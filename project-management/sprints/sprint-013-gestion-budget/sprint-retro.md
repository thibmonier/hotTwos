# Rétrospective — Sprint 13 (Gestion budgétaire, EPIC-002)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-06 |
| Format | Starfish |
| Facilitateur | Scrum Master |

## Directive Fondamentale

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des ressources disponibles, et de la situation. » — Norman Kerth

## Rappel du Sprint

- **Sprint Goal atteint** (✅ 100 %) + stretch : avenants/budget courant, budget par profil, projets internes.
- **21/16 pts** (les 2 Must + la Should stretch). PR #69 → #72. 580 → 605 tests.
- **EPIC-002 quasi terminé** : toutes capacités Must+Should livrées ; reste US-079 (raffinements).

## ⭐ Observations (Starfish)

### 🟢 Continuer
- **Réutilisation du référentiel Pricing** : US-078 s'est branchée sur `RateResolver`/`ProfileRate` (taux historisés) sans dupliquer un seul taux — le port anticipé d'EPIC-001 a de nouveau payé.
- **Budget courant dérivé, pas dupliqué** : `Project.budgetCents` reste le budget initial ; le courant se calcule à la lecture (`CurrentProjectBudget`). Aucune donnée figée touchée (INV-2/3), rebranchement propre des 3 lecteurs.
- **Extension rétro-compatible** : `OccupationLine.billableDays` optionnel → l'occupation existante n'a pas bougé, une mesure « facturable » s'ajoute (RG-PRJ-6).
- **Backlog Ready dès le départ** (affiné en #68) → décomposition et exécution directes, sans blocage de gate.

### 🟡 Commencer
- **Créer la branche AVANT de coder, systématiquement** : le commit US-078 a d'abord atterri sur `main` local (branche oubliée après le merge précédent). Rattrapé (branche créée depuis HEAD + `reset --hard origin/main`), mais à éviter. Réflexe : `git switch -c feat/...` juste après chaque merge.

### 🔴 Arrêter
- **Placer un `<form>` avec `_token` avant un formulaire lu en `.first()`** : le toggle « interne » dans l'en-tête de la fiche a cassé `testLifecycleTransition` (jeton CSRF du mauvais formulaire). Piège **déjà connu** (mémoire S11) — le reproduire coûte un aller-retour. Toujours placer les nouveaux formulaires après le formulaire de statut.

### ⬆️ Plus de
- **Tests au bon niveau** : intégration pour l'exclusion DQL (marge), functional real-DB pour l'occupation facturable, unit pour les calculateurs — chaque comportement prouvé là où il vit.

### ⬇️ Moins de
- **Frictions outillage récurrentes** (cs-fixer multi-lignes, rector `new X()->` / cast redondant) : lancer `cs`+`rector` en préventif avant `make ci` a réduit les allers-retours — à systématiser.

## 📚 Learnings clés

- **Un budget dérivé (initial + Σ avenants) plutôt que muté** préserve l'historique et l'auditabilité sans toucher aux agrégats figés.
- **Un port bien conçu se paie plusieurs sprints plus tard** : `RateResolver` (EPIC-001) a resservi tel quel pour le budget par profil.
- **Rétro-compatibilité par champ optionnel** : ajouter `billableDays` à `OccupationLine` sans casser les appelants existants.
- **Rappels (reconduits)** : créer la branche avant de coder ; ne pas placer un form/token avant un `.first()` ; `make cache-dev` après `cache:clear` ; ajouter toute nouvelle entité aux schémas des tests fonctionnels de la fiche projet.

## 🎯 Actions Sprint 14

| # | Action | Priorité |
|---|--------|----------|
| 1 | Décision PO : US-079 (raffinements pilotage) pour finir EPIC-002, vs ouvrir un nouvel EPIC | À trancher (PO) |
| 2 | Circuit de validation d'avenant (RG-PRJ-4 « paramétrable ») — story à cadrer si besoin | Basse |
| 3 | `MAILER_DSN` staging à exécuter réellement (doc posée S12) | Moyenne |
| 4 | Systématiser `git switch -c` après merge + `cs`/`rector` préventifs | Process |

## Suivi des actions Sprint 12

| Action S12 | Statut |
|-----------|--------|
| Compléter EPIC-002 (US-033/078/032) | ✅ **Fait** (S13) |
| MAILER staging à exécuter | ❌ Reconduit (Action 3) |
| Seuil charge paramétrable / compteur /finance | 🟡 Regroupés dans US-079 (backlog) |

## Check-out

ROTI (auto-évaluation solo) : **5/5** — EPIC-002 amené à ~100 % (capacités Must+Should), 2 Must + stretch livrés, réutilisation maximale (Pricing). Deux irritants process (branche oubliée, piège CSRF connu) rattrapés sans casse.
À emporter : « Le budget courant se dérive, il ne se mute pas — et un port bien pensé resert tout seul. »
