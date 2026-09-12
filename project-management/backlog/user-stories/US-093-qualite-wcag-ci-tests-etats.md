# US-093: Qualité — WCAG axe/pa11y en CI + tests d'états saisie hebdo

## Métadonnées
- **ID**: US-093
- **EPIC**: EPIC-003 (Temps & activité) — dette qualité transversale
- **Sprint**: 22
- **Statut**: 🟢 Ready
- **Points**: 3
- **Persona**: Équipe (qualité produit, au service de P1–P3)
- **Créé le**: 2026-09-12 (dette S21 : rétro action)

## Traçabilité
- **Origine**: rétrospective S21 — « WCAG déclaré mais non attesté en CI » (transversal 6 US) + « tests d'états US-089 manquants »
- **Portée écrans**: parcours P1 reskinné S21 (`/`, `/saisie`, `/saisie/jour`, `/absences`, `/completude`)
- **Réfère**: `sprint-status.yaml` debt `WCAG-CI`, `US-089-tests-etats`

## User Story
**En tant que** équipe de développement,
**je veux** un contrôle d'accessibilité automatisé en CI et les tests des états introduits par le reskin,
**afin de** attester (et non plus seulement déclarer) la conformité WCAG 2.2 AA et empêcher les régressions avant staging.

## Contexte (Conversation)
La rétro S21 a établi que la conformité WCAG 2.2 AA est **affirmée dans les CHANGELOG/templates mais non
vérifiée par outillage** (aucun job axe/pa11y). Par ailleurs, US-089 (saisie hebdo) n'a pas ajouté de tests
pour ses nouveaux états (CA-2 totaux serveur, CA-3 bannière d'erreur, CA-4 lien vue jour). Cette story solde
les deux. Contrainte : le front est buildé **sans Node.js** (ADR-0019) → trancher un runner a11y conteneurisé
en préambule (axe-core CLI ou pa11y dans un conteneur CI dédié).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : job WCAG en CI
```gherkin
GIVEN le pipeline CI
WHEN il s'exécute sur une PR touchant le parcours de saisie
THEN un job d'accessibilité (axe-core ou pa11y) vérifie les routes /, /saisie, /saisie/jour, /absences, /completude
  AND le job échoue si une violation WCAG 2.2 AA de niveau « serious/critical » est détectée
```

### CA-2 (Nominal) : tests d'états saisie hebdo (dette US-089)
```gherkin
GIVEN l'écran de saisie hebdomadaire
WHEN la suite fonctionnelle s'exécute
THEN elle asserte les totaux rendus serveur (grandTotal/dayTotals/objectivePercent),
     la présence du conteneur d'erreur (data-timesheet-target="errorBanner") et du lien « Vue jour (mobile) »
```

### CA-3 (Alternatif) : baseline a11y
```gherkin
GIVEN des violations mineures préexistantes non corrigeables ce sprint
WHEN le job a11y tourne
THEN une baseline documentée les liste explicitement (pas de masquage silencieux) et n'échoue que sur les nouvelles
```

### CA-4 (Erreur) : outillage indisponible
```gherkin
GIVEN un runner a11y qui ne peut démarrer (dépendance manquante)
WHEN le job s'exécute
THEN il échoue de façon explicite (pas de faux vert) avec un message actionnable
```

## Notes sur les livrables
- `.github/workflows/` : job a11y (conteneur dédié avec navigateur headless) sur les 5 routes ; seuil serious/critical.
- Tests : compléter `TimesheetPageTest.php` (3 états) ; option : assertion StatCards dans `CollaboratorDashboardTest`.
- **Hors périmètre** : correction exhaustive de toutes les violations mineures (baseline documentée).

## Definition of Ready
- [x] Portée (5 routes) et dette US-089 identifiées ; contrainte sans-Node connue
- [x] INVEST : Valuable ✓ (atteste WCAG, bloque régressions) / Estimable ✓ (3 pts) / Testable ✓

## Definition of Done
- [ ] Job a11y en CI opérationnel (échec sur serious/critical) + baseline documentée
- [ ] Tests d'états saisie hebdo ajoutés (CA-2/CA-3/CA-4 US-089)
- [ ] Approche runner a11y sans-Node tranchée et documentée (ADR court si besoin)
- [ ] CI verte ; PR mergée
