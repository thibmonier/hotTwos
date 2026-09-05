# US-075: Émission manuelle de factures par projet/période

## Métadonnées
- **ID**: US-075
- **EPIC**: EPIC-005 (Finance & rentabilité)
- **Sprint**: Sprint 11
- **Statut**: 🟢 Ready (affinée S11 — ADR-0022)
- **Points**: 8
- **Persona**: P6 (Directeur financier), P4 (Commercial)
- **Créé le**: 2026-09-05

## Traçabilité
- **Implémente**: EF-FIN-22, OBJ-3 (facturé réel opposable)
- **Dépend de**: US-014 (client structuré, tranche minimale), US-071 (marge/CA reconnu figés), US-057 (clôture)
- **Décision**: ADR-0022 — facturation **minimale, émission manuelle** ; échéances/encaissement hors scope.

## User Story

**En tant que** directeur financier (P6),
**je veux** **émettre manuellement** une facture par **projet et période clôturée** (montant pré-rempli depuis le CA reconnu, ajustable),
**afin de** disposer d'un **facturé réel opposable**, base de la rentabilité.

## Critères d'Acceptation

### CA-1 (Nominal) : Émission d'une facture
```gherkin
GIVEN la période "2026-11" est clôturée pour un projet rattaché à un client (US-014)
WHEN un utilisateur habilité émet une facture pour (projet, période)
  AND le montant est pré-rempli avec le CA reconnu de la période, qu'il peut ajuster
THEN une facture est créée (tenant, client, projet, période, montant en centimes, statut "émise", date d'émission)
  AND elle est figée (INV-2) et traçable (auteur, date)
  AND le total facturé du projet/période est la somme de ses factures émises
```

### CA-2 (Contrainte) : Période clôturée + montant valide
```gherkin
GIVEN une période non clôturée OU un montant ≤ 0
WHEN un utilisateur tente d'émettre une facture
THEN l'émission est refusée avec un message explicite (période non clôturée / montant invalide)
  AND aucune facture n'est créée
```

### CA-3 (Habilitation) : Réservé finance/direction (HAB-1)
```gherkin
GIVEN un utilisateur sans habilitation finance (VIEW_PROJECT_FINANCIALS)
WHEN il tente d'émettre ou de consulter les factures
THEN l'accès est refusé (deny-by-default, 403)
  AND toute émission est tracée (HAB-6)
```

### CA-4 (Isolation) : Cloisonnement tenant
```gherkin
GIVEN deux tenants distincts
WHEN chacun consulte ses factures
THEN chaque tenant ne voit que ses propres factures (RLS)
```

## Critères UI/UX
- **Web** : depuis la fiche projet ou `/finance`, action « Émettre une facture » (sélection période clôturée, montant pré-rempli ajustable) ; liste des factures émises par projet.
- **Mobile** : hors périmètre.

## Definition of Done
- [ ] Entité `Invoice` (tenant, client, projet, période, montant, statut, date) + migration + RLS
- [ ] Use case d'émission gated (HAB-1/HAB-6), période clôturée requise, montant > 0, figée (INV-2)
- [ ] UI d'émission + liste ; tests (nominal, refus, gating, isolation)
- [ ] `make ci` vert (couverture ≥ 80 %) · revue de clôture

## Notes
Émission **manuelle** (montant validé, pas un miroir automatique du CA). Échéances/encaissement/relances/avoirs = tranche ultérieure (ADR-0022).
