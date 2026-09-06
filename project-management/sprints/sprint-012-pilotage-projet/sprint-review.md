# Sprint Review — Sprint 12 (Pilotage projet, EPIC-002)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-06 |
| Sprint Goal | « Le chef de projet pilote l'atterrissage de ses projets : il saisit l'avancement physique et le RAF (distincts de la consommation, INV-4) et le système calcule l'atterrissage en charge, déclenchant une alerte de dérive dès qu'un dépassement > 10 % est projeté avant 50 % de consommation (OBJ-2). » |
| EPIC | EPIC-002 (Projets & delivery) — pilotage côté charge |

## 🎯 Atteinte du Sprint Goal

**Sprint Goal atteint : ✅ OUI (100 %)**

- **Avancement physique & RAF (US-035)** — saisis par lot, distincts de la consommation valorisée (INV-4), gated `EDIT_PROJECT`, refusés sur projet clôturé.
- **Atterrissage charge & dérive précoce (US-036)** — EAC = coût consommé / avancement physique ; alerte déclenchée si dépassement projeté > 10 % **et** consommation < 50 % (OBJ-2, pain point n°1 : détecter la dérive avant la moitié du budget consommé). Gating HAB-1 (jamais de coût unitaire).
- **Dette de finition soldée** — libellés `/finance`, correctif onglet « Suivi budgétaire » (T-R01, reconduit depuis S10), `MAILER_DSN` staging documenté (reconduit depuis S8).

## 📦 Livré

| ID | Titre | Points | Priorité | PR | Statut |
|----|-------|--------|----------|-----|--------|
| US-035 | Avancement physique & RAF par lot | 8 | Must | #64 | ✅ Livré |
| US-036 | Atterrissage charge & alerte de dérive précoce | 8 | Must | #65 | ✅ Livré |
| T-DET-01 | Libellés « Revenu retenu » (/finance) | dette | #66 | ✅ Livré |
| T-R01 | Onglet « Suivi budgétaire » (Stimulus `tabs`) | dette | #66 | ✅ Livré |
| T-OPS-01 | `MAILER_DSN` staging (doc runbook) | dette | #66 | ✅ Livré |

**Points livrés : 16/16 Must + dette Should (~4).** Could (US-033 budget charge, US-032 projets internes) non pris.

## 📈 Métriques

| Métrique | Valeur |
|----------|--------|
| Points planifiés / livrés | 16 (Must) planifiés → 16 livrés + dette |
| PR mergées | #63 (setup) → #66 |
| Tests | 558 → **580** |
| Couverture | ≥ 80 % gardée en CI — verte à chaque merge |
| `make ci` | vert à chaque merge (PHPStan max, Deptrac 0, cs/rector, gitleaks) |
| Migration | `project_lot` (avancement %/RAF, colonnes nullable ; US-036 sans migration) |

Vélocité S1→S12 : 29 / 20 / 23 / 21 / 22 / 21 / 33 / 22 / 21 / 11 / 23 / **16** (Must ; dette Should en sus).

## 🎬 Démonstration

1. **Avancement/RAF (US-035)** — fiche projet, onglet Structure : saisie avancement % + RAF par lot.
2. **Atterrissage & dérive (US-036)** — onglet « Suivi budgétaire » : atterrissage charge projeté + badge d'alerte de dérive précoce quand > 10 % projeté avant 50 % de consommation. Un CP « financials sans coût » voit l'alerte, pas le montant € (HAB-1).
3. **Finition** — libellé « Revenu retenu » sur `/finance` ; onglet « Suivi budgétaire » actif dès le 1er clic.

## 💬 Feedback à collecter

1. Le seuil de dérive charge (10 % / 50 %) doit-il devenir paramétrable par tenant (façon US-018) ?
2. Faut-il un indicateur consolidé de dérive charge sur `/finance` (différé ce sprint pour incohérence sémantique marge figée vs avancement courant) ?
3. Priorités Sprint 13 : compléter EPIC-002 (US-033 budget charge, US-032 projets internes, US-037 avenants) ou autre EPIC ?

## Impact backlog

- **Seuil de dérive charge paramétrable** — story de suivi possible (constantes OBJ-2 en dur aujourd'hui).
- **Compteur dérive charge `/finance`** — nécessiterait de croiser avancement courant et données consolidées (à cadrer).
- **US-033 / US-032** (Could non pris) → réserve S13.

## Prochaines étapes
1. Rétrospective S12 (`sprint-retro.md`).
2. Planifier Sprint 13 (`/workflow:start 013`).
