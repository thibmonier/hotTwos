# US-092b: Complétude — relance inline + filtre/recherche

## Métadonnées
- **ID**: US-092b
- **EPIC**: EPIC-003 (Temps & activité)
- **Sprint**: 22
- **Statut**: 🟢 Ready
- **Points**: 2
- **Persona**: P2/P3 (manager / responsable équipe)
- **Créé le**: 2026-09-12 (finition de US-092, dette S21)

## Traçabilité
- **Continue**: US-092 (reskin complétude, #133) — CPL-04/CPL-05 non livrés au S21
- **Recos audit**: `audit-ux-existant.md` CPL-04 (relance inline ≤ 3 clics), CPL-05 (filtre/tri)
- **Écran existant**: `templates/completeness/index.html.twig` (route `completeness_page`)
- **Réutilise**: endpoint relance `reminders_update` (POST `/relances`, US-056)

## User Story
**En tant que** manager (P2/P3),
**je veux** sélectionner des collaborateurs en retard directement dans la grille de complétude et les relancer en un clic, et filtrer la grille par statut,
**afin de** piloter la complétude en ≤ 3 clics (objectif CPL-04 non atteint au S21 : le bouton actuel ne fait que rediriger vers `/relances`).

## Contexte (Conversation)
Au Sprint 21, la complétude a été reskinnée (F-S5-4, F-S5-5, StatCards, sticky) mais la **relance inline
(CPL-04)** et le **filtre (CPL-05)** ont été différés (le bouton « Relancer les retards » redirige vers
`/relances`). Cette story livre la sélection + POST inline et le filtre par statut.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : relance inline
```gherkin
GIVEN la grille de complétude en périmètre équipe avec des collaborateurs en retard
WHEN je coche des collaborateurs et clique « Relancer la sélection »
THEN une relance est déclenchée pour ces collaborateurs sans quitter l'écran (POST inline)
  AND un retour accessible confirme l'envoi ; le tout en ≤ 3 clics
```

### CA-2 (Alternatif) : filtre par statut
```gherkin
GIVEN une équipe nombreuse
WHEN je filtre par statut (en retard / partiel / soumis) ou recherche un collaborateur
THEN la grille n'affiche que les lignes correspondantes
```

### CA-3 (Erreur) : aucune sélection
```gherkin
GIVEN aucune ligne cochée
WHEN je clique « Relancer la sélection »
THEN l'action est inopérante et un message invite à sélectionner au moins un collaborateur
```

## Notes sur les livrables
- `completeness/index.html.twig` : cases de sélection + barre de filtre ; contrôleur Stimulus (sélection + POST).
- Réutiliser `reminders_update` (POST `/relances`) ou exposer une action de relance multi ; pas de nouveau canal.
- **Accessibilité** : cases avec labels, retour `aria-live`, filtre au clavier — vérifié par US-093.

## Definition of Ready
- [x] Recos CPL-04/05 disponibles ; endpoint relance existant ; écran reskinné (US-092)
- [x] INVEST : Estimable ✓ (2 pts) / Testable ✓

## Definition of Done
- [ ] Relance inline (sélection + POST, ≤ 3 clics) + filtre/recherche par statut
- [ ] Tests fonctionnels (relance multi + filtre) ; WCAG AA attesté (US-093)
- [ ] CI verte ; PR mergée
