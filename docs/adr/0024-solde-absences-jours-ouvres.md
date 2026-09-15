# ADR-0024 — Base de décompte unique du solde d'absences : jours ouvrés

- **Statut :** Accepté (2026-09-14) — décision PO, Sprint 25 (US-107)
- **Réf. :** clôt la dette `US-091-CA2-solde` (ouverte au Sprint 21, reportée S22→S24) ; complète US-091b (`/api/absences/impact`) et US-012/021 (`WorkingDaysCalculator`)
- **Portée :** domaine Absences (EPIC-003)

## Contexte

Deux bases de décompte du solde d'absences coexistaient :

- le **compteur de solde** (`AbsenceBalance`) accumulait `AbsenceRequest::days()`, soit le **span calendaire**
  (`startDate → endDate`, week-ends et jours fériés **inclus**) ;
- la **projection d'impact** (US-091b, `AbsenceImpactProvider` sur `/api/absences/impact`) calculait le coût
  d'une demande en **jours ouvrés** via `WorkingDaysCalculator::workingDaysForUser` (week-ends, fériés,
  fermetures et régime du collaborateur exclus).

L'endpoint d'impact combinait même les deux bases (`projectedBalance()` calendaire − `businessDays` ouvrés),
produisant un solde projeté incohérent. L'écart d'affichage a été relevé en S21 (finding CA-2) mais laissé
ouvert faute d'arbitrage.

## Décision

**Le solde d'absences se décompte en jours ouvrés, base unique.**

`AbsenceBalance` accumule désormais les jours **ouvrés** de chaque demande (validée / en attente) via
`WorkingDaysCalculator::workingDaysForUser` (régime du collaborateur inclus), exactement comme la projection
d'impact. Un week-end, un jour férié ou une fermeture d'entreprise ne consomme plus de solde.

Le solde est **dérivé** (recalculé à la lecture depuis les demandes) et **non persisté** : aucune migration
ni backfill n'est nécessaire.

## Alternatives considérées

- **Documenter l'écart (span calendaire affiché, impact en ouvrés)** — rejeté : incohérence durable, source
  de confusion pour le collaborateur (P1).
- **Aligner l'impact sur le calendaire** — rejeté : le calendaire compte les week-ends comme des congés, ce
  qui est faux métier ; les jours ouvrés respectent fériés, fermetures et temps partiel (US-021).

## Conséquences

### Positives
- Une seule source de vérité du décompte ; solde et projection d'impact cohérents partout.
- Respecte fériés, fermetures et régime (temps partiel) sans logique dupliquée (`WorkingDaysCalculator`).
- Aucun changement de schéma (solde dérivé).

### Négatives / points d'attention
- Le solde consommé par une demande donnée peut **diminuer** par rapport à l'ancien calcul (les week-ends
  ne comptent plus) — comportement voulu, mais visible pour les utilisateurs habitués à l'ancien affichage.
- `AbsenceRequest::days()` (span calendaire) reste utilisé comme garde de validité (durée > 0) ; il n'est
  plus la base du solde.
