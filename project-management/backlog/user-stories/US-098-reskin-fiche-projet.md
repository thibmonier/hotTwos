# US-098: PG-PRJ-02 — Reskin fiche projet (onglets + cycle de vie)

## Métadonnées
- **ID**: US-098
- **EPIC**: EPIC-002 (Projets & delivery)
- **Sprint**: 23
- **Statut**: ✅ Done
- **Points**: 3
- **Persona**: P2 (chef de projet) — pilotage de la dérive (OBJ-2)
- **Créé le**: 2026-10-23 (kickoff S23)

## Traçabilité
- **Backlog reskin**: `backlog-reskin-priorise.md` — Must, PG-PRJ-02 (EPIC-002), 3 pts
- **Écran existant**: `templates/project/show.html.twig` (route `project_show`, GET `/projets/{id}`, `ProjectPageController`) — **déjà sur `Tabs`** (référence tailsfadmin)
- **Gaps liés**: G4 `Layout:PageHeader` (bandeau titre + KPI contextuels, déjà au bundle) ; G5 `Ui:Timeline`/`Stepper` (cycle de vie Client→Devis→Projet→Facture)
- **Socle**: tokens tailsfadmin + `tsf:Layout:PageHeader` (US-087) + `tsf:Ui:Button` (US-096)

## User Story
**En tant que** chef de projet (P2),
**je veux** la fiche projet portée sur le socle tailsfadmin, avec un en-tête contextuel (KPI) et un repère de cycle de vie,
**afin de** piloter le projet (statut, budget, dérive) dans une interface homogène, lisible et accessible.

## Contexte (Conversation)
La fiche projet utilise déjà le composant `Tabs` (navigation thématique). Le reskin porte le reste de l'écran
sur les tokens tailsfadmin, adopte `PageHeader` (G4) pour l'en-tête contextuel (titre + statut + KPI :
budget courant, dérive), et ajoute un repère de **cycle de vie** (G5, `Timeline`/`Stepper` : Client→Projet→Facture)
avec l'étape courante annoncée (`aria-current`). Logique de pilotage inchangée (onglets, avenants, atterrissage,
statut). Boutons via `tsf:Ui:Button`. Pas de maquette HF dédiée (charte directe, cf. US-094/095).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : reskin conforme au design system
```gherkin
GIVEN la fiche projet (onglets)
WHEN elle est reskinnée
THEN elle n'utilise que des composants/tokens tailsfadmin (Tabs, PageHeader G4, cartes, badges, tsf:Ui:Button)
  AND la logique de pilotage (onglets, statut, avenants, atterrissage) est préservée (tests existants verts)
```

### CA-2 (Alternatif) : cycle de vie (G5)
```gherkin
GIVEN un projet à une étape de son cycle de vie (Client → Projet → Facture)
WHEN j'affiche la fiche
THEN un repère de cycle de vie (Timeline/Stepper) indique l'étape courante
  AND l'étape courante est annoncée de façon accessible (aria-current), pas par la couleur seule
```

### CA-3 (Erreur) : projet clôturé / lecture seule
```gherkin
GIVEN un projet clôturé (lecture seule) ou un onglet sans donnée
WHEN j'affiche la fiche
THEN les actions non disponibles sont désactivées/masquées conformément aux règles existantes, sans erreur
```

## Notes sur les livrables
- Reskin `templates/project/show.html.twig` (+ partials, ex. `_charge_landing_curve.html.twig`) sur tokens tailsfadmin ; `PageHeader` (G4) + repère cycle de vie (G5) ; hooks/onglets/routes préservés ; `tsf:Ui:Button`.
- **Accessibilité** : WCAG 2.2 AA (onglets clavier, `aria-current` étape, contraste, focus) — vérifiée par le job US-093.

## Definition of Ready
- [x] Écran existant identifié (déjà sur Tabs) ; PageHeader (G4) au bundle ; US-096 (bouton) en tête
- [x] INVEST : Estimable ✓ (3 pts) / Testable ✓

## Definition of Done
- [ ] Fiche reskinnée (Tabs + PageHeader G4 + cycle de vie G5) ; logique de pilotage inchangée (tests verts)
- [ ] Étape de cycle de vie accessible (aria-current) ; états (clôturé/onglet vide) couverts ; WCAG AA attesté (US-093)
- [ ] CI verte ; PR mergée
