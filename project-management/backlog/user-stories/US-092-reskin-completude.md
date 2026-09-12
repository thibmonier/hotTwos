# US-092: PG-CPL-01 — Reskin Complétude

## Métadonnées
- **ID**: US-092
- **EPIC**: EPIC-003 (Temps & activité)
- **Sprint**: 21
- **Statut**: 🟢 Ready
- **Points**: 3
- **Persona**: P2/P3 (manager / responsable équipe)
- **Créé le**: 2026-09-12

## Traçabilité
- **Maquette validée**: `lot2-saisie/Completude.dc.html`
- **Recos audit**: `audit-ux-existant.md` §6 (CPL-01→06), findings **F-S5-4** (🔴 bloquant) et **F-S5-5**
- **Dépend de**: US-087 (G1 `StatCard`)
- **Écran existant**: `templates/completeness/index.html.twig` (route `completeness_page`)

## User Story
**En tant que** manager (P2/P3),
**je veux** repérer en < 5 s les collaborateurs en retard de saisie et les relancer en ≤ 3 clics,
**afin de** piloter la complétude (OBJ-1) efficacement.

## Contexte (Conversation)
Reskin de la grille existante avec **corrections d'audit prioritaires** : afficher les collaborateurs par
**e-mail** (jamais `userId[:8]` — F-S5-4, bloquant), **rétablir les icônes** des badges d'état (F-S5-5),
ajouter des **StatCards de synthèse** (G1), une **relance inline** et une colonne collaborateur figée.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : conforme maquette
```gherkin
GIVEN la maquette validée Completude.dc.html
WHEN l'écran est reskinné
THEN une rangée de StatCards synthétise retard / partiel / soumis (G1)
  AND la grille collaborateurs × semaines affiche des badges d'état texte + icône + couleur (F-S5-5)
  AND la colonne « Collaborateur » reste visible au défilement horizontal (reco CPL-06)
```

### CA-2 (Nominal) : identification par e-mail (F-S5-4, bloquant)
```gherkin
GIVEN un collaborateur sans nom d'affichage
WHEN la grille s'affiche
THEN il est identifié par son e-mail (résolu tenant-aware)
  AND aucun identifiant technique tronqué (userId[:8]) n'est jamais affiché
```

### CA-3 (Alternatif) : relance inline
```gherkin
GIVEN des collaborateurs en retard
WHEN j'en sélectionne et clique « Relancer la sélection »
THEN la relance est déclenchée en ≤ 3 clics depuis cet écran — reco CPL-04
```

### CA-4 (Alternatif) : filtre / recherche
```gherkin
GIVEN une équipe nombreuse
WHEN j'utilise la recherche/filtre par statut
THEN je retrouve un collaborateur en retard rapidement (reco CPL-05)
```

### CA-5 (Erreur) : aucune donnée
```gherkin
GIVEN aucun périmètre de données
WHEN j'ouvre /completude
THEN un état vide clair est affiché (comportement existant préservé)
```

## Notes sur les livrables
- Reskin `completeness/index.html.twig` sur tokens tailsfadmin ; `StatCard` (US-087) ; résolveur e-mail ; relance inline.
- **Accessibilité** : WCAG 2.2 AA (badges texte+icône+couleur, contraste, colonne sticky annoncée).

## Definition of Ready
- [x] Maquette validée ; findings F-S5-4/F-S5-5 documentés ; dépendance US-087
- [x] INVEST : Valuable ✓ (OBJ-1, corrige un bloquant) / Estimable ✓ (3 pts) / Testable ✓

## Definition of Done
- [ ] Écran conforme à la maquette, tokens tailsfadmin (StatCards via US-087)
- [ ] **F-S5-4 résolu** (e-mail, jamais userId[:8]) · **F-S5-5** (icônes rétablies)
- [ ] Relance inline (≤ 3 clics) + filtre ; colonne collaborateur figée
- [ ] Tests verts ; WCAG AA ; CI verte ; PR mergée
