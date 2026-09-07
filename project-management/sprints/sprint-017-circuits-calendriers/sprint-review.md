# Sprint Review — Sprint 17 (Circuits, calendriers différenciés & fermeture)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-07 |
| Sprint Goal | « Achever EPIC-001 : circuits de validation paramétrables, calendriers de travail différenciés (temps partiel), fermeture entreprise. » |
| EPIC | EPIC-001 (Référentiels & paramétrage) — **bouclage** |

## 🎯 Atteinte du Sprint Goal

**Sprint Goal atteint : ✅ OUI. 21/21 pts livrés.** Les 3 dernières capacités structurantes d'EPIC-001
sont livrées. **EPIC-001 est fonctionnellement bouclé** (restent EF-REF-24 transitions de statut
paramétrables et les suites de Review — fériés mobiles, audit étendu — planifiées S18).

- **US-022 (fermeture, EF-REF-9)** — `ClosurePeriod` exclue des jours ouvrés via `WorkingDaysCalculator`.
- **US-021 (calendriers différenciés, EF-REF-7)** — régime de travail par collaborateur (temps partiel) ; variantes *par utilisateur* du calculateur, occupation/complétude/activité alignées, méthode tenant inchangée.
- **US-017 (circuit de validation, EF-REF-25)** — circuit d'absence paramétrable (1 ou 2 étapes, validateur par rôle) ; comportement historique préservé sans configuration.

## 📦 Livré

| ID | Titre | Points | PR |
|----|-------|--------|-----|
| US-022 | Périodes de fermeture entreprise | 5 | #97 |
| US-021 | Calendriers de travail différenciés (temps partiel) | 8 | #98 |
| US-017 | Circuit de validation des absences paramétrable | 8 | #99 |

**Points livrés : 21 / 21 engagés** (capacité 22).

## 📈 Métriques

| Métrique | Valeur |
|----------|--------|
| Tests | 672 → **685** |
| Couverture | ≥ 80 % gardée en CI |
| `make ci` | vert à chaque merge (PHPStan max, Deptrac, cs, rector, gitleaks) |
| Migrations | `closure_period`, `work_schedule`, `absence_validation_circuit` (+ colonne `current_step`) + RLS |

## 🎬 Démonstration
1. **Fermetures** — `/parametrage/fermetures` : plage exclue des jours ouvrés (occupation).
2. **Régimes de travail** — `/parametrage/regimes-travail` : temps partiel → capacité par régime.
3. **Circuit de validation** — `/parametrage/circuits-validation` : absence validée après les 2 étapes configurées.

## 💬 Feedback à collecter
1. EF-REF-24 (transitions de statut paramétrables) : prioriser S18 ou laisser en enum ?
2. Étendre le moteur de circuit (US-017) aux **temps** et **réouvertures** ? Résolution **N+1 hiérarchique** ?
3. Calendriers différenciés : besoin réel du **forfait-jours** / par **entité** (EF-REF-7 complet) ?

## Impact backlog
- **EPIC-001** : EF-REF-6/7(partiel)/9/10/11/25/29/33 livrés. **Reste** : EF-REF-24 (transitions statut), EF-REF-7 complet (entité/pays/forfait), suites Review (fériés mobiles, audit étendu).

## Prochaines étapes
1. Rétrospective S17 (`sprint-retro.md`).
2. Sprint 18 : suites Review (fériés mobiles onboarding, audit étendu) + arbitrage EF-REF-24 / autre EPIC.
