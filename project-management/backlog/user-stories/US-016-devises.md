# US-016: Devises & devise de référence tenant

## Métadonnées
- **ID**: US-016
- **EPIC**: EPIC-001 (Référentiels & paramétrage)
- **Sprint**: Sprint 14 (🟡 Should)
- **Statut**: 🟢 Ready (affinée S14)
- **Points**: 3
- **Persona**: P6 (Direction financière)
- **Créé le**: 2026-09-06

## Traçabilité
- **Implémente**: EF-REF-22
- **Dépend de**: — (transverse ; s'appuie sur les montants en centimes existants)

## User Story

**En tant que** directeur financier (P6),
**je veux** paramétrer une **devise de référence** par tenant et des **taux de change** datés,
**afin de** consolider en une seule devise des montants exprimés dans d'autres devises (EF-REF-22).

## Contexte (Conversation)

Les montants sont aujourd'hui en **centimes**, implicitement en EUR (aucun concept de devise hors le
champ FEC). EF-REF-22 exige une **devise de référence** paramétrable par tenant (défaut EUR) et des
**taux de change** datés permettant la **consolidation** (ex. un montant en CHF affiché en EUR au taux
en vigueur). Portée minimale : référentiel devises + taux + conversion à la consolidation/affichage —
les montants internes restent stockés en centimes (migration douce, pas de reconversion du stock).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : devise de référence tenant
```gherkin
GIVEN un tenant sans configuration de devise
WHEN il consulte sa devise de référence
THEN la devise de référence par défaut est EUR
  AND elle est modifiable (rôle habilité)
```

### CA-2 (Taux de change daté) : conversion
```gherkin
GIVEN une devise de référence EUR et un taux CHF→EUR de 1,05 en vigueur
WHEN on convertit 1 000 CHF vers la devise de référence
THEN le montant consolidé est 1 050 EUR (au taux en vigueur)
```

### CA-3 (Historisation) : taux à date
```gherkin
GIVEN un taux CHF→EUR passé de 1,05 à 1,08 au 01/07
WHEN on convertit un montant à une date de juin
THEN le taux 1,05 (en vigueur à la date) est utilisé
```

### CA-4 (Robustesse) : taux manquant
```gherkin
GIVEN une devise sans taux de change défini vers la référence
WHEN on tente une conversion
THEN la conversion est signalée « taux indisponible » (pas de conversion silencieuse à 0)
```

### CA-5 (Sécurité) : habilitation
```gherkin
GIVEN un utilisateur sans MANAGE_ORGANIZATION
WHEN il tente de modifier la devise de référence ou un taux de change
THEN l'accès est refusé (403)
```

## Definition of Done
- [ ] Devise de référence par tenant (défaut EUR) + référentiel devises + taux de change **datés** (`EffectivePeriod`) + migration RLS
- [ ] Convertisseur (montant, devise source, date → devise de référence) ; cas taux manquant signalé
- [ ] Use case de configuration gated `MANAGE_ORGANIZATION`, tracé
- [ ] UI : configuration devise de référence + taux de change
- [ ] Tests : conversion, taux historisé à date, taux manquant, défaut EUR, gating
- [ ] `make ci` vert · revue de clôture

## Notes
Portée **minimale** : les montants internes restent en centimes (pas de reconversion du stock). La
multi-devise complète des projets/factures (facturation en devise) est une tranche ultérieure. Réutiliser
le pattern `EffectivePeriod` pour l'historisation des taux (cohérent avec Pricing).
