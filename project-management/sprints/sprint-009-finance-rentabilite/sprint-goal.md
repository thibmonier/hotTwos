# Sprint 9 : Finance & rentabilité — marge réelle & pilotage (EPIC-005)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 9 |
| Planifié le | 2026-09-04 |
| Début | 2026-12-08 |
| Fin | 2026-12-19 |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~22 points (1 dev — vélocité S1-S8 : 29/20/23/21/22/21/33/22 → moy. récente ~24, facteur sécurité 10 %) |
| Base git | `main` (après clôture S8 : review + rétro) |
| EPIC | EPIC-005 (Finance & rentabilité, Lot 2) |

## Sprint Goal

> « La **marge réelle par projet** (produit facturable − charge valorisée) est **calculée à la clôture**,
> **comparée au budget avec alerte de dérive**, et **consolidée dans un tableau de bord finance**
> réservé à la direction (coûts gated HAB-1). »

> **Positionnement** : premier incrément d'EPIC-005 (Lot 2), en aval direct de la valorisation livrée
> au Sprint 8 (`fact_project_revenue`, ventilation par projet, coût/marge). Non-rétroactivité garantie
> (INV-2 — les marges passées ne sont jamais recalculées).

## Definition of Done (rappel projet)

- [ ] Revue de clôture approuvée (`symfony-reviewer` / `php-reviewer` par lot)
- [ ] Tests (couverture ≥ 80 %), `make ci` vert (PHPStan max, Deptrac, gitleaks)
- [ ] Moteur de calcul **unique et testé** (ARC-6 — aucun calcul financier dupliqué back/front)
- [ ] Non-rétroactivité des marges (INV-2) ; traçabilité opposable de chaque ligne
- [ ] Coûts unitaires gated par habilitation (HAB-1)
- [ ] **Recette navigateur sur données peuplées** (action rétro S7/S8, reconduite) tracée dans `.recette/`
- [ ] Documentation & ADR si décision structurante ; déployable

## Sprint Backlog (candidat — à détailler via `/project:add-story` puis `/project:decompose-tasks 009`)

| Priorité | ID | Titre | Points | Statut |
|----------|-----|-------|--------|--------|
| 🔴 Must | US-071 | Moteur de marge réelle par projet à la clôture (produit facturable − charge valorisée, INV-2) | 8 | 🔵 À affiner |
| 🔴 Must | US-072 | Budget vs réalisé + **alerte de dérive financière** (OBJ-2 / OBJ-6, s'appuie sur les budgets US-033) | 5 | 🔵 À affiner |
| 🟡 Should | US-073 | Tableau de bord finance **consolidé** multi-projets/clients (direction, HAB-1, perf < 3 s P95) | 8 | 🔵 À affiner |
| 🟢 Could | US-074 | Export comptable configurable (EF-FIN-22) — **réserve** si capacité | 5 | 🔵 À affiner |

**Total engagé (Must + Should) : 21 points** (capacité ~22). US-074 en réserve.

### Dette / actions rétro à intégrer (tâches transverses, hors points)
- **Recette navigateur sur données peuplées** des écrans valorisation enrichie + auth (action rétro reconduite depuis S7).
- **`MAILER_DSN` staging** + test e2e du parcours « mot de passe oublié ».
- Fiabiliser l'outillage cache dev/PHPStan (warmup après `cache:clear`).

## Dépendances

| US | Dépend de | Note |
|----|-----------|------|
| US-071 | S8 valorisation (`fact_project_revenue`, ventilation par projet) | Réutiliser le moteur/DTO existants ; côté « produit facturable » à modéliser |
| US-072 | US-033 (budget/charge projet, EPIC-002) | Comparer budget prévisionnel vs réalisé valorisé |
| US-073 | US-071, US-072 | Consolidation ; gating coût HAB-1 déjà en place (dashboard valorisation) |
| US-071 | Circuit de facturation / « facturable » | ⚠️ **À clarifier avec le PO** : source du « produit facturable » (contrat/taux de vente vs facturation réelle) |

## Risques identifiés

| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| Notion de « produit facturable » non modélisée (pas de module facturation) | **Forte** | Fort | **Spike/décision PO en début de sprint** : s'appuyer sur le CA reconnu (taux de vente × temps validé, déjà calculé) comme proxy de facturable — à acter en ADR léger |
| EPIC-005 non affiné en US | Moyenne | Moyen | Affinage (`add-story`) avant dev ; backlog candidat ci-dessus comme point de départ |
| Perf tableau consolidé sur historique | Moyenne | Moyen | S'appuyer sur `fact_project_revenue` (déjà agrégé) + index projet (T-060-09) |

## Cérémonies

| Cérémonie | Timing |
|-----------|--------|
| Planning P1 (QUOI) / P2 (COMMENT) | Début S9 |
| Daily | Quotidien |
| Affinage | Mi-sprint (détailler EPIC-005 restant) |
| **Review + Rétro** | **Fin de sprint** (ne plus les mettre en dette — action rétro S7 tenue en S8) |

## Notes

Premier incrément d'EPIC-005, en aval de la chaîne de valeur (temps → valorisation → **marge**). La
valorisation du Sprint 8 fournit déjà le **coût** et le **CA reconnu** par projet : le Sprint 9 ajoute
la **marge consolidée**, la **comparaison au budget** et le **pilotage direction**. Le point ouvert
principal — la source du « produit **facturable** » (absence de module facturation) — est à trancher en
tout début de sprint (proxy CA reconnu recommandé).

Prochaine étape : `/project:add-story EPIC-005 "…"` pour figer US-071..074, puis `/project:decompose-tasks 009`.
