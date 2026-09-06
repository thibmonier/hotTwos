# Sprint Review — Sprint 14 (Référentiels de valorisation)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-06 |
| Sprint Goal | « Boucler le pilotage projet (raffinements EPIC-002) et enrichir les référentiels de valorisation : taux de vente multi-niveaux (profil/client/projet) et multi-devises. » |
| EPIC | EPIC-002 (finition) + EPIC-001 (référentiels de valorisation) |

## 🎯 Atteinte du Sprint Goal

**Sprint Goal atteint : ✅ OUI** (partie EPIC-001 complète + export ; courbe/seuil reportés).

- **Taux de vente multi-niveaux (US-015, EF-REF-19)** — surcharges par client/projet, priorité projet > client > profil (repli profil), historisées (INV-2).
- **Devises (US-016, EF-REF-22)** — devise de référence tenant + taux de change datés + conversion (montants internes inchangés).
- **Export pilotage CSV (US-079a, EF-PRJ-14)** — 5 valeurs par projet/lot, colonnes coût gated HAB-1.

## 📦 Livré

| ID | Titre | Points | PR |
|----|-------|--------|-----|
| US-015 | Taux de vente multi-niveaux + priorité | 5 | #76 |
| US-016 | Devises & devise de référence | 3 | #77 |
| US-079a | Export CSV du tableau de pilotage | ~3 | #78 |

**Points livrés : ~11.** Reportés S15 : **US-079c** (courbe d'atterrissage) et **US-079b** (seuil par type).

## 📈 Métriques

| Métrique | Valeur |
|----------|--------|
| PR mergées | #74 (affinage) → #78 |
| Tests | 605 → **628** |
| Couverture | ≥ 80 % gardée en CI |
| `make ci` | vert à chaque merge |
| Migrations | `selling_rate`, `reference_currency`, `exchange_rate` (+ RLS) |

Vélocité S1→S14 : … / 23 / 16 / 21 / **11** (sprint plus léger — US-079 partiellement reportée).

## 🎬 Démonstration
1. **Taux multi-niveaux** — `/profils` : surcharge de taux client/projet ; la résolution applique projet > client > profil.
2. **Devises** — `/finance/config-devises` : devise de référence + taux de change datés.
3. **Export** — fiche projet, onglet Suivi budgétaire → « Exporter (CSV) » (colonnes coût masquées pour un CP sans coût).

## 💬 Feedback à collecter
1. La courbe d'atterrissage (US-079c) justifie-t-elle une historisation dans le figeage de marge, ou une capture plus légère (périodique) ?
2. Faut-il exposer la « règle appliquée » (US-015) dans un écran de chiffrage dédié ?
3. Priorités S15 : finir US-079 (courbe + seuil) vs poursuivre EPIC-001 (calendrier, compétences, onboarding).

## Impact backlog
- **EPIC-001** : EF-REF-19/22 livrés. Reste calendrier fériés (EF-REF-6), compétences (EF-REF-10/11), statuts/circuits (EF-REF-24/25), onboarding (EF-REF-29).
- **EPIC-002** : EF-PRJ-14 (export) livré ; reste US-079c (courbe EF-PRJ-16) + US-079b (seuil EF-PRJ-15).

## Prochaines étapes
1. Rétrospective S14 (`sprint-retro.md`).
2. Planifier Sprint 15 (`/workflow:start 015`).
