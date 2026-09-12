# US-091b: Absences — calendrier de conflits + solde projeté dynamique

## Métadonnées
- **ID**: US-091b
- **EPIC**: EPIC-003 (Temps & activité)
- **Sprint**: 22
- **Statut**: 🟢 Ready
- **Points**: 3
- **Persona**: P1 (Camille)
- **Créé le**: 2026-09-12 (finition de US-091, dette S21)

## Traçabilité
- **Continue**: US-091 (reskin absences, #130) — items de DoD non livrés au S21
- **Maquette**: `design-canvas/lot2-saisie/Absences.dc.html` (calendrier + impact solde)
- **Recos audit**: `audit-ux-existant.md` ABS-02 (solde contextualisé), ABS-03 (calendrier conflits)
- **Écran existant**: `templates/absence/index.html.twig` (route `absence_page`, `data-controller="absence"`)

## User Story
**En tant que** collaboratrice (P1),
**je veux** voir un calendrier qui distingue fériés, fermetures d'entreprise et absences déjà posées, et l'impact de ma demande sur mon solde au moment où je choisis les dates,
**afin de** poser un congé « sans mauvaise surprise » (objectif d'US-091 non atteint au S21).

## Contexte (Conversation)
Au Sprint 21, le reskin d'`/absences` a été livré (tokens, badges, RGPD, solde projeté statique) mais **deux
critères d'acceptation sont restés non livrés** (dette explicite) : le **calendrier des conflits (ABS-03, Must)**
et la **mise à jour dynamique du solde à la sélection de dates (CA-2)**. Cette story les solde. Réutilise
`WorkingDaysCalculator` (fériés/ouvrés), `ClosurePeriod` (fermetures) et `AbsenceRequestRepository` (absences existantes).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : calendrier des conflits
```gherkin
GIVEN des jours fériés, des fermetures d'entreprise et mes absences déjà posées sur le mois affiché
WHEN j'ouvre /absences
THEN le calendrier distingue visuellement ces jours (fériés/fermetures, mes demandes, week-ends)
  AND chaque marquage a une alternative textuelle (aria-label / légende) — pas la couleur seule (WCAG 1.4.1)
```

### CA-2 (Nominal) : impact solde dynamique
```gherkin
GIVEN le formulaire de déclaration
WHEN je choisis une date de début et de fin
THEN l'écran affiche le nombre de jours ouvrés concernés et le solde projeté APRÈS cette demande
  AND ce calcul exclut week-ends, fériés et fermetures
```

### CA-3 (Alternatif) : conflit détecté
```gherkin
GIVEN une période qui chevauche un jour de fermeture ou une absence déjà posée
WHEN je sélectionne ces dates
THEN un avertissement accessible signale le conflit avant soumission
```

### CA-4 (Erreur) : mois sans donnée
```gherkin
GIVEN un mois sans férié/fermeture/absence
WHEN j'affiche le calendrier
THEN il s'affiche normalement (aucun marquage), sans erreur
```

## Notes sur les livrables
- Enrichir `AbsencePageController` (données calendrier : fériés/fermetures/absences du mois) + `absence_controller.js` (calcul solde dynamique à la sélection).
- Composant calendrier : `tsf:Calendar` / `tsf:Form:Datepicker` (bundle) ou grille custom conforme maquette.
- **Accessibilité** : WCAG 2.2 AA (alternatives textuelles, focus, contraste) — vérifiée par le job US-093.

## Definition of Ready
- [x] Maquette validée (Absences.dc.html) ; recos ABS-02/03 disponibles ; services conflits existants
- [x] INVEST : Valuable ✓ (Must non livré S21) / Estimable ✓ (3 pts) / Testable ✓

## Definition of Done
- [ ] Calendrier des conflits (fériés/fermetures/absences) avec alternatives textuelles
- [ ] Solde projeté dynamique à la sélection de dates ; avertissement de conflit
- [ ] Tests fonctionnels (calendrier rendu + impact solde) ; WCAG AA attesté (US-093)
- [ ] CI verte ; PR mergée
