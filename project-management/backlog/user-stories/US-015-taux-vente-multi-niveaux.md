# US-015: Taux de vente multi-niveaux (profil / client / projet) + priorité

## Métadonnées
- **ID**: US-015
- **EPIC**: EPIC-001 (Référentiels & paramétrage)
- **Sprint**: Sprint 14 (🔴 Must)
- **Statut**: 🟢 Ready (affinée S14)
- **Points**: 5
- **Persona**: P4 (Yann — commercial) / Direction
- **Créé le**: 2026-09-06

## Traçabilité
- **Implémente**: EF-REF-19, OBJ-1
- **Dépend de**: US-011 (`App\Domain\Pricing` : `Profile`, `ProfileRate`, `EffectivePeriod`, `RateResolver`)
- **Réutilise**: le socle Pricing historisé (INV-2) et le mécanisme de résolution à date

## User Story

**En tant que** commercial (P4),
**je veux** définir des **taux de vente par profil, par client et par projet** avec une **règle de priorité explicite (projet > client > profil)**,
**afin d'**appliquer automatiquement le bon tarif au chiffrage et à la valorisation, tout en le rendant lisible.

## Contexte (Conversation)

Aujourd'hui le taux de vente est porté uniquement par le **profil** (`ProfileRate.sellingPriceCents`,
historisé à date d'effet). EF-REF-19 exige des surcharges **par client** et **par projet**, avec une
**priorité** : le taux **projet** l'emporte sur le taux **client**, qui l'emporte sur le taux **profil**
(repli). La règle appliquée doit être **affichée** à l'utilisateur (traçabilité du chiffrage). Historisation
à date d'effet obligatoire (INV-2) : une valorisation passée n'est jamais recalculée.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : repli sur le taux profil
```gherkin
GIVEN un profil « Senior » avec un taux de vente de 800 €/j en vigueur
  AND aucun taux spécifique client ni projet
WHEN on résout le taux de vente d'un projet pour ce profil à une date donnée
THEN le taux retenu est 800 €/j (niveau profil)
  AND la règle affichée indique « taux profil »
```

### CA-2 (Priorité) : projet > client > profil
```gherkin
GIVEN un taux profil 800 €/j, un taux client 850 €/j et un taux projet 900 €/j (tous en vigueur)
WHEN on résout le taux de vente pour ce profil sur ce projet/client
THEN le taux retenu est 900 €/j (niveau projet, le plus spécifique)
  AND la règle affichée indique « taux projet »
```

### CA-3 (Priorité partielle) : client sans projet
```gherkin
GIVEN un taux profil 800 €/j et un taux client 850 €/j (pas de taux projet)
WHEN on résout le taux pour ce client
THEN le taux retenu est 850 €/j (niveau client)
```

### CA-4 (Historisation) : INV-2
```gherkin
GIVEN un taux projet passé de 900 à 950 €/j au 01/07
WHEN on résout le taux à une date de juin
THEN le taux retenu est 900 €/j (valeur en vigueur à la date), inchangé par la révision du 01/07
```

### CA-5 (Sécurité) : habilitation
```gherkin
GIVEN un utilisateur sans MANAGE_PRICING
WHEN il tente de définir un taux de vente (client ou projet)
THEN l'accès est refusé (403)
```

## Definition of Done
- [ ] Modèle de taux de vente **scopé** (profil | client | projet) historisé (`EffectivePeriod`) + port + migration RLS
- [ ] Résolveur unique appliquant la priorité **projet > client > profil** (ARC-6), avec repli, à une date donnée
- [ ] Règle appliquée exposée (niveau retenu) pour l'affichage
- [ ] Use case de définition gated `MANAGE_PRICING`, anti-chevauchement (`EffectivePeriod::overlaps`), tracé
- [ ] UI : gestion des surcharges de taux (client/projet) + affichage de la règle appliquée
- [ ] Tests : priorité (les 3 niveaux), repli, historisation à date (INV-2), gating
- [ ] `make ci` vert · revue de clôture

## Notes
Réutiliser `ProfileRate`/`RateResolver` (profil) ; ajouter les niveaux client/projet et un résolveur de
priorité **unique** (ne pas dupliquer la logique). Ne pas casser la valorisation existante (le niveau
profil reste le repli par défaut). Le volet **grille tarifaire client** (EF-REF-17) reste hors périmètre.
