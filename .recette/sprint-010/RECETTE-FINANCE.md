# Recette navigateur — écrans finance (QUAL-1, Sprint 10)

> Action rétrospective escaladée (report S7→S9). Recette réalisée sur **données peuplées** via le seed
> de démo enrichi (`app:demo:seed`, T-QUAL-1-01) et pilotage navigateur (Claude in Chrome).

## Contexte

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-05 |
| Environnement | local (`make up`, `make db-reset`), http://localhost:8080 |
| Jeu de données | tenant démo : période **2026-08 clôturée**, marges figées, budgets/clients, config FEC |
| Comptes | `camille@demo.test` (Collaborateur) · `marc@demo.test` (Chef de projet) · `dg@demo.test` (Dirigeant) · `admin@demo.test` (Administrateur) — mdp `demo-1234-solide` |

## Écrans couverts

### 1. Tableau de bord finance consolidé `/finance` — Dirigeant (coût visible) ✅
`01-finance-dashboard-dirigeant.jpg`
- KPI : CA reconnu **3 600,00 €**, Coût valorisé **2 400,00 €**, Marge **1 200,00 € (33,3 %)**, Projets en dérive **0/1**.
- Ventilation **par client** (ACME Tourisme) et **par projet** (Refonte site Alpha) avec CA/coût/marge.
- Sélecteur de période (2026-08, « Données figées »), bouton **Export FEC** présent.
- Conforme US-073 (consolidation) + gating coût visible (HAB-1) pour le Dirigeant.

### 2. Suivi budgétaire — fiche projet ALPHA — Dirigeant ✅
`03-suivi-budgetaire-ok.jpg`
- Budget vs réalisé : CA (cible 60 000 / réel 3 600 → **écart défavorable −56 400**, rouge), Coût
  (cible 40 000 / réel 2 400 → **favorable −37 600**, vert), Marge (cible/réel 33,3 %).
- Consommation budgétaire (coût) : **6,0 %**.
- Écarts **libellés ET colorés** (favorable/défavorable) — a11y conforme (pas de couleur seule). US-072 OK.

### 3. Gating HAB-1 (coût/marge) — Chef de projet & Collaborateur
- **Couvert par les tests fonctionnels automatisés** (`tests/Functional/Web/FinanceDashboardTest.php`,
  `ProjectBudgetTrackingTest.php`) : le Chef de projet voit le CA sans coût/marge ; le Collaborateur
  reçoit **403** sur `/finance`.
- ⚠️ **Non rejoué manuellement en navigateur** : la déconnexion automatisée (bouton + GET `/logout`)
  n'a pas basculé la session dans l'outil de pilotage → bascule de rôle non testée en live. Le gating
  reste garanti par les tests automatisés.

## Findings

| # | Sévérité | Écran | Constat | Action |
|---|----------|-------|---------|--------|
| R-01 | Mineur (UX) | Fiche projet / onglet « Suivi budgétaire » | Le **1er clic** sur l'onglet n'a pas basculé le panneau (contenu « Cycle de vie » resté affiché — cf. `02-...-1er-clic-non-bascule.jpg`) ; un 2e clic bascule correctement. Intermittence (init Stimulus ?). | À reproduire/corriger (contrôleur `tabs`) — backlog |
| R-02 | Info | Recette live | Bascule de rôle impossible via l'outil (logout non pris) → gating chef/collab validé par tests auto seulement. | Prévoir un reset de session fiable pour la prochaine recette live |

## Verdict

**Écrans finance conformes sur données réelles** (dashboard consolidé, suivi budgétaire, marge/coût
gated). Chiffres cohérents avec la chaîne temps → valorisation → marge. 1 finding UX mineur (R-01) et
1 limite d'outillage recette (R-02). Dette de recette peuplée **enfin soldée** (report S7→S9).
