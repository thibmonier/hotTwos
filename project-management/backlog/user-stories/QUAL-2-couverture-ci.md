# QUAL-2: Instrumentation de la couverture de tests (pcov + seuil CI)

## Métadonnées
- **ID**: QUAL-2 (story technique / dette qualité)
- **EPIC**: — (dette qualité transverse)
- **Sprint**: Sprint 10 (🔴 Must)
- **Statut**: ✅ Done (livré Sprint 10 — PR #48)
- **Points**: — (dette, hors vélocité) · **Estimation**: ~0.5 j
- **Persona**: Équipe dev (garde-fou qualité)
- **Créé le**: 2026-09-05

## Traçabilité
- **Origine**: audit S9 (finding ÉLEVÉ — couverture NON mesurée : aucun driver pcov/xdebug, seuil ≥ 80 % du DoD non vérifié ni enforcé), action rétro S9.

## User Story

**En tant qu'** équipe de développement,
**je veux** que la **couverture de tests soit mesurée et gardée en CI** (seuil ≥ 80 % bloquant),
**afin de** rendre effectif l'engagement DoD (≥ 80 %) au lieu de le supposer.

## Critères d'Acceptation

### CA-1 (Nominal) : Couverture mesurable en local
```gherkin
GIVEN l'image Docker applicative
WHEN on ajoute l'extension pcov et une cible `make coverage`
THEN `make coverage` produit un rapport de couverture (texte + clover/HTML) sans xdebug
  AND la couverture globale est affichée en fin de run
```

### CA-2 (Contrainte) : Seuil bloquant en CI
```gherkin
GIVEN la CI GitHub
WHEN la couverture globale passe sous le seuil (≥ 80 %, à ajuster au niveau réel constaté)
THEN le job échoue (garde-fou), avec le pourcentage affiché
  AND le seuil est documenté et versionné (phpunit.dist.xml / config CI)
```

### CA-3 (Non-régression perf) : Impact CI maîtrisé
```gherkin
GIVEN pcov activé
WHEN la suite tourne en CI
THEN le surcoût de temps reste acceptable (pcov, pas xdebug)
  AND si l'impact est trop fort, la couverture tourne sur un job dédié (non bloquant du feedback rapide)
```

## Definition of Done
- [ ] pcov dans l'image Docker · `make coverage` fonctionnel
- [ ] Seuil de couverture bloquant en CI (valeur documentée, calée sur le niveau réel mesuré)
- [ ] Couverture réelle du projet mesurée et consignée (baseline)
- [ ] `make ci` reste vert ; impact temps CI mesuré

## Notes
Le seuil initial sera **calé sur la couverture réelle mesurée** (baseline) puis relevé progressivement vers 80 %+ si nécessaire — éviter de casser la CI dès l'activation.

## Réalisation (2026-09-05)
- **Baseline mesurée** : **82,78 % de lignes** (3615/4367), 73,35 % méthodes, 81,09 % éléments.
- Le DoD ≥ 80 % (lignes) est **déjà atteint** → seuil CI posé à **80 %** (passe avec marge).
- pcov ajouté à l'image (désactivé par défaut, `pcov.enabled=0`) ; cible `make coverage` ; gate CI
  « lignes ≥ 80 % » (parse `coverage.xml`, échoue sinon).
