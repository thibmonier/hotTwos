# Rétrospective — Sprint 9 (Finance & rentabilité, EPIC-005)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-04 |
| Format | Starfish (Continuer / Commencer / Arrêter / Plus de / Moins de) |
| Facilitateur | Scrum Master |

## Directive Fondamentale

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des ressources disponibles, et de la situation. » — Norman Kerth

## Rappel du Sprint

- **Sprint Goal atteint** (✅) : marge à la clôture + budget vs réalisé (dérive) + dashboard consolidé.
- **21/21 points livrés** (100 %) ; 3 PR mergées (#41→#43) ; `make ci` vert à chaque merge (515 tests, +45) ; 0 dette ajoutée.
- Trois revues de clôture `symfony-reviewer` (US-071 approuvé + réserves traitées — test RLS d'intégration ajouté ; US-072 approuvé ; US-073 approuvé — gating HAB-1 impeccable).

## ⭐ Observations (Starfish)

### 🟢 Continuer
- **Cartographie du code réutilisable par sous-agent `Explore` avant de coder** — a fixé les bons namespaces/patterns (snapshot figé, ports, Messenger, RLS, gating) dès le premier jet, quasi zéro rework d'architecture.
- **PR par story + CI verte avant merge** (squash), branche suivante depuis `main` — traçabilité et réversibilité fortes, dépendances (073 ⇐ 071+072) débloquées proprement.
- **Réutilisation stricte du moteur unique** (`MarginCalculator`) pour marge, taux, dérive et consolidation (ARC-6) — cohérence garantie, aucune formule dupliquée.
- **Décisions PO ciblées en amont** (facturable=CA reconnu, modéliser le CA cible, politique de merge) — ont évité du rework et des critères d'acceptation bâtis sur des données inexistantes.

### 🟡 Commencer
- **Recette navigateur sur données peuplées** (T-TECH-01) — **report chronique S7 → S8 → S9**, toujours non faite. À traiter en tout début de S10 (ou à sortir explicitement du périmètre).
- **Profilage perf réel** du dashboard `/finance` sur un historique conséquent (ENF-PERF-3, < 3 s P95) — la conception est O(projets) mais non mesurée sur volume.

### 🔴 Arrêter
- **Se faire surprendre par le cache dev périmé** : après un `cache:clear` dev, `doctrine:schema:validate` a signalé un **faux désync** (métadonnées de mapping en cache) — réflexe `cache:clear` + `cache:warmup APP_DEBUG=1` à systématiser (déjà noté S8, re-rencontré).

### ⬆️ Plus de
- **Consolidation depuis des snapshots figés** plutôt que rejeu d'événements — la non-rétroactivité et la perf viennent « gratuitement » (US-073 lit `ProjectMargin` déjà agrégé).

### ⬇️ Moins de
- **Allers-retours outillage en clôture de lot** : rector (flip `null ===` → `!instanceof`), cs-fixer (alignement phpdoc), PHPStan max (cast `mixed`→string, nullsafe sur type non-nullable). Anticiper ces conventions à l'écriture plutôt que les corriger après `make ci`.

## 📚 Learnings clés

- **Snapshot figé = source de vérité idéale pour la consolidation** : `ProjectMargin` (US-071) sert directement US-073 sans projection supplémentaire ni recalcul (ARC-6, perf, non-rétro).
- **Le modèle de données prime sur les critères d'acceptation** : US-072 supposait un CA/marge cible inexistants → décision PO d'ajouter `revenue_budget_cents` (nullable, param trailing pour préserver la compat) plutôt que d'inventer une cible.
- **RLS testée au niveau DB** : test d'intégration cross-tenant sous rôle NOSUPERUSER (modèle `ValuationWorkerRlsTest`) — prouve la policy, pas seulement le `WHERE tenant`.
- **Outillage de revue** : le sous-agent `symfony-reviewer` a un budget de tours bas et l'épuise en lecture ; le faire converger en lui demandant d'emblée « verdict direct, sans nouvel appel d'outil ».

## 🎯 Actions Sprint 10

### Action 1 : Recette navigateur sur données peuplées (escalade)
| Attribut | Valeur |
|----------|--------|
| Description | Rejouer la recette sur les écrans finance (marge, suivi budgétaire, `/finance`) sur un seed peuplé, tracée dans `.recette/`. Report S7→S8→S9 : **à faire en tout premier ou à dé-scoper explicitement**. |
| Deadline | Sprint 10 (jour 1) |
| DoD | Rapport `.recette/` couvrant les écrans finance, findings backlogués |
| Priorité | **Haute** |

### Action 2 : Profilage perf `/finance` (ENF-PERF-3)
| Attribut | Valeur |
|----------|--------|
| Description | Mesurer `/finance` sur un historique volumineux ; décider si une projection `fact_project_margin` est nécessaire (ADR léger) |
| Deadline | Sprint 10 |
| DoD | Mesure P95 documentée ; décision projection tranchée |
| Priorité | Moyenne |

### Action 3 : MAILER_DSN staging + e2e reset (reconduit)
| Attribut | Valeur |
|----------|--------|
| Description | Transport SMTP réel/catch-all en staging + parcours mot de passe oublié de bout en bout |
| Deadline | Sprint 10 |
| DoD | E-mail de reset réel reçu en staging + lien fonctionnel |
| Priorité | Moyenne |

## Suivi des actions Sprint 8

| Action S8 | Statut |
|-----------|--------|
| Recette navigateur sur données peuplées | ❌ Toujours non fait → **reconduit (Action 1), escaladé** |
| Fiabiliser outillage cache dev / PHPStan (warmup) | 🟡 Documenté (mémoire projet) ; automatisation `make` non faite |
| MAILER_DSN staging + e2e reset | ❌ Non fait → **reconduit (Action 3)** |

## Check-out

- ROTI (auto-évaluation solo) : **4/5** — sprint entièrement livré (21/21), 3 revues, 0 dette ; irritants persistants = recette peuplée jamais rejouée et frictions outillage en clôture.
- À emporter : « Cartographier avant de coder et réutiliser le moteur unique ont payé ; la dette de recette peuplée doit cesser d'être reportée. »
