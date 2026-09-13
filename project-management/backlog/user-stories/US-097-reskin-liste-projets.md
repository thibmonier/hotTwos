# US-097: PG-PRJ-01 — Reskin liste des projets

## Métadonnées
- **ID**: US-097
- **EPIC**: EPIC-002 (Projets & delivery)
- **Sprint**: 23
- **Statut**: 🟢 Ready
- **Points**: 2
- **Persona**: P2 (chef de projet / manager)
- **Créé le**: 2026-10-23 (kickoff S23)

## Traçabilité
- **Backlog reskin**: `backlog-reskin-priorise.md` — Must, PG-PRJ-01 (EPIC-002), 2 pts
- **Écran existant**: `templates/project/index.html.twig` (route `project_index`, GET `/projets`, `ProjectPageController`)
- **Gap lié**: G3 `Ui:FilterBar` (barre recherche + filtres au-dessus de la liste)
- **Socle**: tokens tailsfadmin + `tsf:Ui:Button` (US-096, développée en tête)
- **Cohérence**: mêmes patterns que le reskin complétude (US-092/092b) — table + badges statut + filtre client

## User Story
**En tant que** chef de projet (P2),
**je veux** la liste des projets portée sur le socle tailsfadmin, avec badges de statut et filtre/recherche,
**afin de** retrouver et ouvrir rapidement un projet dans une interface homogène et accessible (accès quotidien).

## Contexte (Conversation)
Écran d'accès quotidien P2 à porter sur les tokens tailsfadmin, dans la continuité du reskin du parcours.
Logique inchangée (liste, statuts, création). Ajout d'une barre de filtre/recherche (G3, filtrage client
sans rechargement — même approche que CPL-05 sur la complétude) ; badges de statut texte+icône+couleur
(jamais la couleur seule) ; bouton « Nouveau projet » via `tsf:Ui:Button`. Pas de maquette HF dédiée
(charte appliquée directement, process assoupli — cf. US-094/095).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : reskin conforme au design system
```gherkin
GIVEN la liste des projets
WHEN elle est reskinnée
THEN elle n'utilise que des composants/tokens tailsfadmin (table/carte, badges statut texte+icône+couleur, tsf:Ui:Button)
  AND la logique existante (liste, tri, création) est préservée (tests existants verts)
```

### CA-2 (Alternatif) : filtre / recherche (G3)
```gherkin
GIVEN une liste de projets nombreuse
WHEN je filtre par statut ou recherche par nom/code
THEN la liste n'affiche que les projets correspondants (filtrage client, sans rechargement)
  AND les contrôles de filtre sont utilisables au clavier avec labels explicites
```

### CA-3 (Erreur) : liste vide
```gherkin
GIVEN aucun projet (ou aucun résultat de filtre)
WHEN j'affiche la liste
THEN un état vide clair est affiché, sans erreur
```

## Notes sur les livrables
- Reskin `templates/project/index.html.twig` sur tokens tailsfadmin ; hooks/routes préservés ; `tsf:Ui:Button` pour la création.
- Barre de filtre/recherche client (Stimulus) — G3 (FilterBar custom, réutilise le pattern CPL-05).
- **Accessibilité** : WCAG 2.2 AA (contraste, focus, labels de filtre) — vérifiée par le job US-093.

## Definition of Ready
- [x] Écran existant identifié ; charte + patterns du parcours disponibles ; US-096 (bouton) en tête
- [x] INVEST : Estimable ✓ (2 pts) / Testable ✓

## Definition of Done
- [ ] Liste reskinnée sur tokens tailsfadmin (badges statut, tsf:Ui:Button) ; logique inchangée (tests verts)
- [ ] Filtre/recherche (G3) + état vide couverts ; WCAG AA attesté (US-093)
- [ ] CI verte ; PR mergée
