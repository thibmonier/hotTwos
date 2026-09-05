# US-076: Le facturé réel comme source de la marge (supersede le proxy)

## Métadonnées
- **ID**: US-076
- **EPIC**: EPIC-005 (Finance & rentabilité)
- **Sprint**: Sprint 11
- **Statut**: 🟢 Ready (affinée S11 — ADR-0022)
- **Points**: 5
- **Persona**: P6 (Directeur financier)
- **Créé le**: 2026-09-05

## Traçabilité
- **Implémente**: EF-FIN, OBJ-3, ARC-6 (moteur unique), INV-2
- **Dépend de**: US-075 (factures émises), US-071 (moteur de marge)
- **Décision**: ADR-0022 — le facturé réel supersède le CA reconnu comme source de revenu, avec repli.

## User Story

**En tant que** directeur financier (P6),
**je veux** que la **marge** s'appuie sur le **facturé réel** dès qu'une facture existe pour (projet, période), et **retombe sur le CA reconnu** sinon,
**afin de** piloter sur des chiffres **opposables** sans réécrire le moteur de marge.

## Critères d'Acceptation

### CA-1 (Nominal) : Marge sur facturé réel quand facture présente
```gherkin
GIVEN un projet/période clôturé avec une facture émise de 42 000 € (US-075)
  AND un coût valorisé de 30 000 €
WHEN la marge de la période est calculée
THEN le revenu retenu est le facturé réel (42 000 €), pas le CA reconnu
  AND la marge = facturé réel − coût valorisé (moteur MarginCalculator inchangé, ARC-6)
```

### CA-2 (Repli) : CA reconnu si aucune facture
```gherkin
GIVEN un projet/période sans facture émise
WHEN la marge est calculée
THEN le revenu retenu est le CA reconnu (repli, comportement ADR-0020 conservé)
  AND aucune double source (jamais facturé + CA reconnu simultanément)
```

### CA-3 (Contrainte) : Port unique « source de revenu » (ARC-6)
```gherkin
GIVEN le moteur de marge
WHEN il obtient le revenu d'un (projet, période)
THEN il passe par un port unique « source de revenu » (DIP) qui applique la règle facturé > CA reconnu
  AND ni MarginCalculator ni le figeage de marge ne sont dupliqués/réécrits
```

### CA-4 (Non-rétroactivité) : Marge figée inchangée
```gherkin
GIVEN une marge déjà figée à la clôture
WHEN une facture est émise/modifiée ultérieurement
THEN la marge figée passée n'est pas réécrite (INV-2) ; seule une réouverture recalcule
```

## Definition of Done
- [ ] Port « source de revenu » (Domain) + implémentation (facturé réel avec repli CA reconnu)
- [ ] Branchement au figeage de marge (US-071) sans réécrire le moteur (ARC-6)
- [ ] Tests : marge sur facturé, repli CA reconnu, non-rétroactivité, pas de double comptage
- [ ] `make ci` vert (couverture ≥ 80 %) · revue de clôture

## Notes
Le seam « source de revenu » était anticipé (ADR-0020/0021). Cette US le matérialise et bascule la
règle par défaut vers le facturé réel.
