# US-091: PG-ABS-01 — Reskin Mes absences

## Métadonnées
- **ID**: US-091
- **EPIC**: EPIC-003 (Temps & activité)
- **Sprint**: 21
- **Statut**: 🟢 Ready
- **Points**: 2
- **Persona**: P1 (Camille)
- **Créé le**: 2026-09-12

## Traçabilité
- **Maquette validée**: `lot2-saisie/Absences.dc.html`
- **Recos audit**: `audit-ux-existant.md` §5 (ABS-01→06)
- **Écran existant**: `templates/absence/index.html.twig` (route `absence_page`)

## User Story
**En tant que** collaboratrice (P1),
**je veux** poser un congé en libre-service en voyant l'impact sur mon solde et les conflits (fériés/fermetures),
**afin de** déclarer une absence en < 1 min sans mauvaise surprise.

## Contexte (Conversation)
Reskin de l'écran existant sur le socle. Conserver l'atout badges statut **texte + icône + couleur** (ABS-01) et
l'avertissement RGPD (ABS-06) ; ajouter la contextualisation du solde et la vue calendrier des conflits.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : conforme maquette
```gherkin
GIVEN la maquette validée Absences.dc.html
WHEN l'écran est reskinné
THEN il affiche les compteurs (StatCards), le formulaire de déclaration et la liste des demandes
  AND les statuts de demande sont rendus en texte + icône + couleur (ABS-01)
```

### CA-2 (Alternatif) : impact solde contextualisé
```gherkin
GIVEN une demande en cours de saisie
WHEN je choisis des dates
THEN l'écran montre les jours ouvrés concernés et le solde projeté après validation — reco ABS-02
```

### CA-3 (Alternatif) : calendrier des conflits
```gherkin
GIVEN des fériés/fermetures/absences existantes
WHEN je consulte le calendrier
THEN ils sont visuellement distingués (avec alternative textuelle, pas la couleur seule) — reco ABS-03
```

### CA-4 (Erreur) : champs requis
```gherkin
GIVEN un formulaire incomplet (type ou dates manquants)
WHEN je soumets
THEN les champs requis sont signalés accessiblement (aria-required) et la soumission est empêchée
```

## Notes sur les livrables
- Reskin `absence/index.html.twig` sur tokens tailsfadmin ; `Form:Datepicker`/`Calendar` du bundle ; RGPD conservé.
- **Accessibilité** : WCAG 2.2 AA (badges texte+icône+couleur, champs requis, contraste).

## Definition of Ready
- [x] Maquette validée ; écran existant identifié ; recos audit disponibles
- [x] INVEST : Estimable ✓ (2 pts) / Testable ✓

## Definition of Done
- [ ] Écran conforme à la maquette, tokens tailsfadmin ; RGPD + badges texte+icône+couleur préservés
- [ ] Impact solde contextualisé ; calendrier des conflits avec alternative textuelle
- [ ] Tests verts ; WCAG AA ; CI verte ; PR mergée
