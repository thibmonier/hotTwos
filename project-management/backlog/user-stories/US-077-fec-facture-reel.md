# US-077: Export FEC sur le facturé réel

## Métadonnées
- **ID**: US-077
- **EPIC**: EPIC-005 (Finance & rentabilité)
- **Sprint**: Sprint 11 (🟡 Should — si capacité)
- **Statut**: ✅ Done (S11 — ADR-0022)
- **Points**: 5
- **Persona**: P6 (Directeur financier)
- **Créé le**: 2026-09-05

## Traçabilité
- **Implémente**: EF-FIN-22, OBJ-3
- **Dépend de**: US-074 (FecGenerator), US-075 (factures), US-076 (source de revenu)
- **Décision**: ADR-0022 — le FEC reflète le facturé réel quand présent.

## User Story

**En tant que** directeur financier (P6),
**je veux** que l'**export FEC** utilise le **facturé réel** (comme le fait la marge, US-076), avec repli sur le CA reconnu,
**afin de** produire un FEC cohérent avec la comptabilité réelle.

## Critères d'Acceptation

### CA-1 (Nominal) : FEC sur facturé réel
```gherkin
GIVEN une période clôturée avec des factures émises (US-075)
WHEN le FEC de la période est généré (US-074)
THEN les écritures de produit reflètent le **facturé réel** (montant des factures), pas le CA reconnu
  AND le fichier reste conforme (18 champs, débit=crédit) — FecGenerator inchangé, alimenté par la source de revenu US-076
```

### CA-2 (Repli) : CA reconnu si pas de facture
```gherkin
GIVEN une période clôturée sans facture
WHEN le FEC est généré
THEN les écritures de produit reflètent le CA reconnu (repli, cohérent avec la marge US-076)
```

### CA-3 (Cohérence) : FEC et marge alignés
```gherkin
GIVEN une même période
WHEN on compare le revenu du FEC et le revenu utilisé par la marge
THEN ils proviennent de la **même source de revenu** (US-076) — jamais divergents
```

## Definition of Done
- [x] `FecGenerator` alimenté par la source de revenu unique (US-076) — via `ProjectMargin` re-figé, pas de branchement direct sur le CA reconnu
- [x] Tests : FEC sur facturé réel, repli CA reconnu, cohérence FEC/marge (`FecReflectsBilledRevenueTest`)
- [x] `make ci` vert (558 tests, couverture ≥ 80 %) · revue de clôture

## Notes
Réutilise le `FecGenerator` (US-074) sans le réécrire : seule la **source du montant produit** change (ARC-6).

### Constat de réalisation (ARC-6, source unique)
La règle « facturé réel s'il existe, sinon CA reconnu » est appliquée en **un seul point** — le port
`RevenueSource` (US-076) — dont le résultat est **figé** dans `ProjectMargin.revenueCents` par
`ComputeProjectMargins` (et re-figé à l'émission d'une facture, US-076). Or `ExportFec` lit
`ProjectMargin.findForPeriod()` et `FecGenerator` prend `$margin->revenueCents()` : le FEC reflète donc
**automatiquement** le facturé réel, sans divergence possible avec la marge (CA-3 garanti par
construction). Le seul écart corrigé par cette story : le **libellé** de l'écriture produit affirmait
« CA reconnu » même quand le montant était le facturé réel → rendu neutre (« Revenu retenu »).

### Suivi (hors périmètre)
Les libellés « CA reconnu » des vues `/finance` et `/valorisation` (US-073) affichent désormais le
revenu retenu (potentiellement facturé réel) : même incohérence de libellé côté UI → à harmoniser dans
une story de finition dashboard, non incluse ici (YAGNI, périmètre US-077 = export FEC).
