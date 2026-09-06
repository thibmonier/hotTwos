# Sprint Review — Sprint 13 (Gestion budgétaire, EPIC-002)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-06 |
| Sprint Goal | « La gestion budgétaire des projets est complète : chaque budget se pilote par avenants datés (initial → courant) et se ventile par profil, reliant charge et montant aux taux en vigueur (EF-PRJ-8/9). » |
| EPIC | EPIC-002 (Projets & delivery) — finition budget |

## 🎯 Atteinte du Sprint Goal

**Sprint Goal atteint : ✅ OUI (100 %)** + stretch livrée.

- **Avenants (US-033, EF-PRJ-8)** — budget initial (`Project.budgetCents`) → budget courant = initial + Σ avenants datés (motif obligatoire RG-PRJ-4). Débloque **INV-8** et « un avenant modifie le budget sans altérer les imputations » (INV-2/3). Le budget courant est désormais la référence du suivi (US-072), de l'atterrissage (US-036) et du dashboard finance.
- **Budget par profil (US-078, EF-PRJ-9)** — ventilation charge par profil → équivalents € vente/coût via les taux **historisés** (`RateResolver`), profil sans taux signalé.
- **Projets internes (US-032, EF-PRJ-5, stretch)** — flag `internal` : exclusion de la marge, occupation **facturable** (internes exclus du numérateur, capacité inchangée, RG-PRJ-6).

## 📦 Livré

| ID | Titre | Points | Priorité | PR |
|----|-------|--------|----------|-----|
| US-033 | Budget initial/avenants/courant | 8 | Must | #70 |
| US-078 | Budget charge par profil | 8 | Must | #71 |
| US-032 | Projets internes non facturables | 5 | Should (stretch) | #72 |

**Points livrés : 21 (16 Must + 5 stretch).** US-079 (raffinements) → S14.

## 📈 Métriques

| Métrique | Valeur |
|----------|--------|
| Points planifiés / livrés | 16 Must → **21** (stretch incluse) |
| PR mergées | #69 (setup) → #72 |
| Tests | 580 → **605** |
| Couverture | ≥ 80 % gardée en CI |
| `make ci` | vert à chaque merge |
| Migrations | `budget_amendment`, `lot_profile_budget`, `project.internal` (+ RLS) |

Vélocité S1→S13 : … / 22 / 21 / 11 / 23 / 16 / **21**.

## 🎬 Démonstration

1. **Avenants** — fiche projet, onglet Suivi budgétaire : ajout d'avenant (Δ charge/CA + motif) → budget courant recalculé, historique daté.
2. **Budget par profil** — onglet Structure : saisie {profil, jours} par lot → équivalents € vente/coût aux taux en vigueur.
3. **Projets internes** — toggle « interne » sur la fiche (Cycle de vie) ; exclusion de la marge ; colonne « Facturable » sur `/valorisation`.

## 💬 Feedback à collecter
1. Faut-il un **circuit de validation** des avenants (RG-PRJ-4 mentionne « paramétrable ») au-delà du motif ?
2. La ventilation budget par profil doit-elle **alimenter** le budget projet (aujourd'hui elle coexiste, informative) ?
3. Priorités S14 : US-079 (raffinements pilotage) vs autre EPIC ?

## Impact backlog
- **EPIC-002 quasi terminé** : toutes capacités Must+Should livrées ; reste US-079 (export/courbe/seuil, EF-PRJ-14/15/16).
- Pistes : circuit de validation d'avenant, budget par profil → budget projet.

## Prochaines étapes
1. Rétrospective S13 (`sprint-retro.md`).
2. Planifier Sprint 14 (`/workflow:start 014`).
