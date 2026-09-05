# Sprint Review — Sprint 10 (Export comptable FEC & consolidation qualité)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-05 |
| Sprint Goal | « Le tenant peut exporter ses données comptables (export FEC configurable) sur une base qualité assainie : recette sur données peuplées enfin réalisée et couverture de tests instrumentée et gardée en CI. » |
| EPIC | EPIC-005 (Finance, dernière tranche fonctionnelle) + dette qualité |

## 🎯 Atteinte du Sprint Goal

**Sprint Goal atteint : ✅ OUI (100 %)**

- **Export FEC (US-074)** — fichier FEC conforme (18 champs, écritures équilibrées) d'une période clôturée, via mapping de comptes tenant (ADR-0021), gated finance/direction + trace HAB-6.
- **Base qualité assainie** — **couverture instrumentée** (pcov) et **gardée en CI** (seuil ≥ 80 %, baseline 82,78 %) ; **recette sur données peuplées enfin réalisée** (dette S7→S9 soldée).
- **Bonus** — seuil de dérive **paramétrable par tenant** (US-018, override US-072).

## 📦 User Stories & dette livrées

| ID | Titre | Points | PR | Statut |
|----|-------|--------|-----|--------|
| US-074 | Export comptable FEC | 8 | #49 | ✅ Livré |
| US-018 | Seuil de dérive paramétrable par tenant | 3 | #51 | ✅ Livré |
| QUAL-2 | Couverture pcov + gate CI ≥ 80 % | dette | #48 | ✅ Livré |
| QUAL-1 | Seed finance peuplé + recette navigateur | dette | #52 | ✅ Livré |

**Points livrés : 11/11 (US-074 + US-018).** US-036 (Could) non pris.

## 📈 Métriques

| Métrique | Valeur |
|----------|--------|
| Points planifiés / livrés | 11 / 11 (100 %) |
| PR mergées | #48 → #52 (5, dont 1 doc) |
| Tests | 515 → 535 |
| Couverture (baseline) | 82,78 % lignes — **désormais gardée en CI** |
| `make ci` | vert à chaque merge |
| Dette rétro soldée | recette données peuplées (report S7→S9) |

Vélocité S1→S10 : 29 / 20 / 23 / 21 / 22 / 21 / 33 / 22 / 21 / **11** (sprint court, fêtes, orienté qualité).

## 🎬 Démonstration
1. **Export FEC (US-074)** — `/finance` (période clôturée) → bouton « Export FEC » → fichier `<SIREN>FEC<AAAAMMJJ>.txt` (débit=crédit) ; écran de config comptable `/finance/config-fec`.
2. **Suivi budgétaire & dérive paramétrable (US-018)** — `/finance/config-derive` change le seuil ; la dérive se recalcule (fiche projet / dashboard).
3. **Qualité** — `make coverage` + gate CI ; recette peuplée (`.recette/sprint-010/`).

## 💬 Feedback à collecter
1. Le format FEC dérivé (écritures produit/charge via mapping) répond-il au besoin de l'expert-comptable ?
2. Faut-il un module de facturation (facturé réel) pour la prochaine tranche EPIC-005 ?
3. Priorités Sprint 11 ?

## Impact backlog
- **R-01** (recette) : 1er clic onglet « Suivi budgétaire » ne bascule pas → backlog (contrôleur `tabs`).
- **US-036** (Could non pris), module de facturation, autres seuils US-018 (occupation, retard) — tranches ultérieures.

## Prochaines étapes
1. Rétrospective S10 (`sprint-retro.md`).
2. Planifier Sprint 11.
