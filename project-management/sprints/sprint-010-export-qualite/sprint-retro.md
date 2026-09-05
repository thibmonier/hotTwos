# Rétrospective — Sprint 10 (Export FEC & consolidation qualité)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-05 |
| Format | Starfish |
| Facilitateur | Scrum Master |

## Directive Fondamentale

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des ressources disponibles, et de la situation. » — Norman Kerth

## Rappel du Sprint
- **Sprint Goal atteint** (✅ 100 %) : export FEC + base qualité assainie (couverture CI + recette peuplée).
- 11/11 pts (US-074, US-018) + 2 items de dette (QUAL-1/2). 5 PR (#48→#52). 515 → 535 tests.
- **Dette de recette peuplée soldée** après 3 sprints de report.

## ⭐ Observations (Starfish)

### 🟢 Continuer
- **Réutilisation du moteur unique** partout (marge/FEC via `ProjectMargin` + `MarginCalculator`, seuil via le port existant) — ARC-6/OCP, zéro duplication.
- **PR par item + CI verte avant merge** ; gate couverture désormais **actif** (protège la régression).
- **Sous-agents Explore** pour cartographier avant de coder — patterns corrects du 1er coup.

### 🟡 Commencer
- **Traiter la dette qualité tôt** a payé (QUAL-2 en premier a instrumenté la couverture avant d'ajouter du code) — à garder comme réflexe.

### 🔴 Arrêter
- **Commiter sur `main` par distraction** : un commit du seed a atterri sur `main` (non poussé) → dû le déplacer sur une branche. Toujours créer la branche AVANT de coder.
- **`git reset --hard` après un hook pre-commit échoué** : a discardé des changements non commités (cache dev manquant → hook PHPStan KO). Réflexe : `make cache-dev` avant tout commit qui suit un `cache:clear`.

### ⬆️ Plus de
- **Recette sur données peuplées** : à intégrer à chaque fin de sprint touchant l'UI (le seed enrichi la rend rejouable).

### ⬇️ Moins de
- **Allers-retours outillage** persistants (rector `instanceof`, cs-fixer imports/phpdoc, PHPStan cast/nullsafe, deptrac UI→Infra) — anticiper à l'écriture ; la couche UI ne référence jamais l'Infra (utiliser une constante de port domaine).

## 📚 Learnings clés
- **UI ne dépend jamais de l'Infrastructure** (Deptrac) : une constante partagée va sur le **port domaine**, pas sur l'impl.
- **pcov `enabled=0` par défaut** dans l'image → zéro surcoût, activé à la demande pour la couverture.
- **Rebrancher un provider** (Default → Tenant) impose d'ajouter la table aux schémas des tests fonctionnels qui l'exercent indirectement.
- **Recette live** : la bascule de rôle (logout) n'est pas fiable via l'outil de pilotage → prévoir un reset de session, ou s'appuyer sur les tests fonctionnels pour le gating multi-rôles.

## 🎯 Actions Sprint 11
| # | Action | Priorité |
|---|--------|----------|
| 1 | Corriger R-01 (1er clic onglet « Suivi budgétaire ») — contrôleur Stimulus `tabs` | Moyenne |
| 2 | `MAILER_DSN` staging + e2e reset (report S8→S10) | Moyenne |
| 3 | Reset de session fiable pour la recette live (bascule de rôle) | Basse |
| 4 | Décision : module de facturation (facturé réel) vs autres tranches EPIC-005 | À trancher (PO) |

## Suivi des actions Sprint 9
| Action S9 | Statut |
|-----------|--------|
| Recette navigateur données peuplées (escaladé) | ✅ **Fait** (QUAL-1, `.recette/sprint-010/`) |
| Couverture pcov + seuil CI | ✅ **Fait** (QUAL-2) |
| Profilage perf `/finance` | 🟡 Partiel (validé fonctionnellement sur données démo ; charge 5 ans non mesurée) |
| MAILER_DSN staging | ❌ Reconduit (Action 2) |

## Check-out
ROTI (auto-évaluation solo) : **4/5** — sprint court mais 100 % livré, 2 dettes chroniques soldées (couverture, recette) ; irritants = commit sur main par erreur et frictions outillage.
À emporter : « Assainir la qualité tôt et solder les dettes chroniques débloque la sérénité du reste. »
