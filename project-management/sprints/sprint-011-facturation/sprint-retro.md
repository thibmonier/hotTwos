# Rétrospective — Sprint 11 (Module de facturation, capstone EPIC-005)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-06 |
| Format | Starfish |
| Facilitateur | Scrum Master |

## Directive Fondamentale

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des ressources disponibles, et de la situation. » — Norman Kerth

## Rappel du Sprint

- **Sprint Goal atteint** (✅ 100 %) : le facturé réel devient la base de la rentabilité (marge/budget/FEC), avec repli CA reconnu.
- **23/23 pts** (US-014, US-075, US-076 Must + US-077 Should). PR #55 → #61. 535 → 558 tests.
- **Capstone EPIC-005 bouclé** : la chaîne temps → valorisation → marge → budget → dashboard → FEC → **facturation** est complète.

## ⭐ Observations (Starfish)

### 🟢 Continuer
- **Port « source de revenu » anticipé** : le moteur financier (marge, FEC) a basculé du proxy au facturé réel **sans réécriture** (DIP/ARC-6). Le point d'extension prévu dès S9 a payé plein pot en S11.
- **Source unique de la règle métier** : « facturé réel sinon CA reconnu » vit en **un seul endroit** (`RevenueSource`) → US-077 (FEC) était acquis par construction, réduit à des tests de verrouillage + un libellé. Zéro divergence FEC ↔ marge possible.
- **Re-figeage à l'émission** : figer la marge au moment d'émettre la facture garde dashboard/FEC cohérents sans recalcul rétroactif (INV-2 respecté).
- **PR par story + CI verte avant merge**, gate couverture actif.

### 🟡 Commencer
- **Vérifier le périmètre réel d'une story « dérivée » avant de l'estimer** : US-077 (5 pts) était en grande partie déjà acquise via US-076. Bon réflexe de l'avoir constaté et borné (tests + libellé) au lieu de reconstruire — à généraliser pour les stories qui consomment un port existant.

### 🔴 Arrêter
- **Laisser les libellés UI diverger de la sémantique** : en rebranchant la marge sur le facturé réel (US-076), les libellés « CA reconnu » de `/finance`, `/valorisation` (et l'écriture FEC) sont devenus mensongers. Corrigé pour le FEC ; l'UI reste à harmoniser. Réflexe : quand une **source** change, auditer tous les **libellés** qui la nomment.

### ⬆️ Plus de
- **Tests de bout en bout de la chaîne** (`ComputeProjectMargins → ExportFec`) plutôt que par unité isolée : ils prouvent la cohérence inter-services (même source) que des tests unitaires séparés ne captent pas.

### ⬇️ Moins de
- **Dépendance à la stabilité de l'environnement conteneur** : OrbStack s'est arrêté en plein commit (hook pre-commit conteneurisé KO). Perte de temps faible (`orb start`), mais à garder en tête : le hook exige Docker up.

## 📚 Learnings clés

- **Un port anticipé transforme une story en changement de câblage** : la valeur d'un point d'extension (DIP) se mesure au sprint où on branche la 2ᵉ implémentation, pas à sa création.
- **Une règle métier mono-source propage sa correction gratuitement** : figer le résultat de `RevenueSource` dans `ProjectMargin` fait que tout consommateur (dashboard, FEC) hérite du facturé réel sans y toucher.
- **Changer une source impose un audit des libellés** qui la désignent (dette de cohérence sémantique, ici sur l'UI).
- **Rappel outillage (reconduit)** : tout en Docker ; `make cache-dev` après un `cache:clear` ; UI ne référence jamais l'Infra (constante sur le port domaine) ; schémas des tests fonctionnels de la fiche projet doivent inclure toute nouvelle entité requêtée par `show()`.

## 🎯 Actions Sprint 12

| # | Action | Priorité |
|---|--------|----------|
| 1 | Harmoniser les libellés « CA reconnu » → « Revenu retenu » sur `/finance` et `/valorisation` (cohérence post-US-076) | Moyenne |
| 2 | Corriger T-R01 (1er clic onglet « Suivi budgétaire ») — contrôleur Stimulus `tabs` | Moyenne |
| 3 | `MAILER_DSN` staging + e2e reset (report S8→S11) | Moyenne |
| 4 | Décision PO : prochaine tranche EPIC-005 (échéances/encaissement) vs nouvel EPIC | À trancher (PO) |

## Suivi des actions Sprint 10

| Action S10 | Statut |
|-----------|--------|
| Corriger R-01 (onglet « Suivi budgétaire ») | ❌ Reconduit (Action 2) |
| `MAILER_DSN` staging + e2e reset | ❌ Reconduit (Action 3) |
| Reset de session fiable pour la recette live | 🟡 Non traité (recette live non requise ce sprint) |
| Décision module de facturation | ✅ **Fait** — ADR-0022, module livré ce sprint |

## Check-out

ROTI (auto-évaluation solo) : **5/5** — capstone EPIC-005 bouclé à 100 %, Should embarquée, la conception anticipée (port source de revenu) a rendu le rebranchement propre et US-077 quasi gratuite.
À emporter : « Un bon point d'extension ne se paie pas quand on l'écrit, il rapporte quand on branche la deuxième source. »
