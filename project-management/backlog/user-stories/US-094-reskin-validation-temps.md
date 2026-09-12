# US-094: PG-VLD-01 — Reskin validation des temps

## Métadonnées
- **ID**: US-094
- **EPIC**: EPIC-003 (Temps & activité)
- **Sprint**: 22
- **Statut**: 🟢 Ready
- **Points**: 2
- **Persona**: P2 (manager / valideur)
- **Créé le**: 2026-09-12

## Traçabilité
- **Backlog reskin**: `backlog-reskin-priorise.md` — Should, PG-VLD-01 (EPIC-003), 2 pts
- **Écran existant**: `templates/timesheet/validation.html.twig` (validation des temps par lot, US-055)
- **Socle**: composants/tokens tailsfadmin v1.6.0 (US-087)

## User Story
**En tant que** manager (P2),
**je veux** l'écran de validation des temps porté sur le socle tailsfadmin, cohérent avec le reste du parcours,
**afin de** valider les imputations de mon équipe dans une interface homogène et accessible.

## Contexte (Conversation)
Écran existant (validation par lot, US-055) à porter sur les tokens tailsfadmin, dans la continuité du reskin
du parcours de saisie (S21). Logique de validation inchangée ; présentation alignée sur le design system.
Pas de maquette HF dédiée S20 (hors lot 2) → appliquer directement la charte + patterns du parcours (badges
statut texte+icône+couleur, tables tailsfadmin), conformément au process assoupli (VALIDATION lot 1 §5).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : reskin conforme au design system
```gherkin
GIVEN l'écran de validation des temps
WHEN il est reskinné
THEN il n'utilise que des composants/tokens tailsfadmin (brand/gray/theme, badges statut texte+icône+couleur)
  AND la logique de validation par lot est préservée (tests existants verts)
```

### CA-2 (Alternatif) : états
```gherkin
GIVEN aucun temps à valider
WHEN j'ouvre l'écran
THEN un état vide clair est affiché
```

### CA-3 (Erreur) : sans permission
```gherkin
GIVEN un utilisateur non habilité à valider
WHEN il accède à l'écran
THEN l'accès est refusé/masqué conformément aux voters existants
```

## Notes sur les livrables
- Reskin `templates/timesheet/validation.html.twig` sur tokens tailsfadmin ; hooks Stimulus préservés.
- **Accessibilité** : WCAG 2.2 AA (contraste, cibles, focus) — vérifiée par le job US-093.

## Definition of Ready
- [x] Écran existant identifié ; charte + patterns du parcours disponibles
- [x] INVEST : Estimable ✓ (2 pts) / Testable ✓

## Definition of Done
- [ ] Écran reskinné sur tokens tailsfadmin ; logique inchangée (tests existants verts)
- [ ] États (vide, sans-permission) couverts ; WCAG AA attesté (US-093)
- [ ] CI verte ; PR mergée
