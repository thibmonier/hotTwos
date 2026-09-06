# US-033: Budget — initial, avenants & budget courant

## Métadonnées
- **ID**: US-033
- **EPIC**: EPIC-002 (Projets & delivery)
- **Sprint**: — (backlog affiné, Ready)
- **Statut**: 🟢 Ready
- **Points**: 8
- **Persona**: P2 (Marc — Chef de projet) / P6 (Direction)
- **Créé le**: 2026-09-06

## Traçabilité
- **Implémente**: EF-PRJ-8, RG-PRJ-4 ; contribue à INV-8 (volet avenants)
- **Dépend de**: US-030 (Project + `budgetCents`/`revenueBudgetCents`)
- **Critère EPIC débloqué**: « un avenant modifie le budget sans altérer les imputations historiques » (INV-2/INV-3)

## User Story

**En tant que** chef de projet (P2),
**je veux** distinguer le **budget initial**, les **avenants** datés et le **budget courant** (= initial + avenants),
**afin de** reconstituer l'évolution du budget dans le temps et justifier chaque révision (RG-PRJ-4).

## Contexte (Conversation)

Aujourd'hui `Project` porte un `budgetCents` (coût) et un `revenueBudgetCents` (CA cible) modifiables par
simples mutateurs, **sans historique ni motif**. EF-PRJ-8 (Must) exige : budget **initial** figé, une
suite d'**avenants** (montant en charge et/ou en €, daté, avec motif et auteur), et un **budget courant**
dérivé (initial + Σ avenants). La modification d'un projet actif exige un **motif** et relève d'un circuit
de validation paramétrable (RG-PRJ-4). Les **imputations de temps historiques ne sont jamais altérées**
par un avenant (INV-2/INV-3) : l'avenant ne fait qu'ajuster la cible budgétaire.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : ajouter un avenant
```gherkin
GIVEN un projet actif de budget initial 100 000 € / 60 j
  AND je suis chef de projet habilité (EDIT_PROJECT)
WHEN j'ajoute un avenant de +20 000 € / +10 j avec le motif « périmètre étendu — lot 3 »
THEN le budget courant devient 120 000 € / 70 j
  AND l'avenant est daté, tracé (auteur, motif) et visible dans l'historique
  AND le budget initial (100 000 € / 60 j) reste inchangé
```

### CA-2 (Historique) : reconstitution dans le temps
```gherkin
GIVEN un projet avec 2 avenants (+20 000 € le 10/01, −5 000 € le 02/02)
WHEN je consulte l'historique de budget
THEN je vois budget initial + chaque avenant daté + le budget courant résultant
```

### CA-3 (Motif obligatoire) : RG-PRJ-4
```gherkin
GIVEN un projet actif
WHEN j'ajoute un avenant sans motif
THEN l'avenant est refusé (motif obligatoire, RG-PRJ-4)
```

### CA-4 (Non-altération) : INV-2/INV-3
```gherkin
GIVEN un projet avec des temps validés et valorisés (imputations historiques)
WHEN un avenant modifie le budget courant
THEN les imputations et valorisations figées restent inchangées (seule la cible budgétaire évolue)
```

### CA-5 (Sécurité) : habilitation
```gherkin
GIVEN un utilisateur sans EDIT_PROJECT
WHEN il tente d'ajouter un avenant
THEN l'accès est refusé (403)
```

### CA-6 (Clôture) : projet clôturé
```gherkin
GIVEN un projet clôturé
WHEN je tente d'ajouter un avenant
THEN la modification est refusée (RG-PRJ-5, lecture seule structurelle)
```

## Definition of Done
- [ ] Entité `BudgetAmendment` (tenant, projet, deltaCents, deltaDays, motif, auteur, date) + port repo + RLS
- [ ] Budget **initial** figé à la création ; **budget courant** = initial + Σ avenants (dérivé, jamais recalculé côté imputations)
- [ ] Use case `AmendBudget` gated `EDIT_PROJECT`, motif obligatoire (RG-PRJ-4), refus projet clôturé
- [ ] Migration + RLS ; historique affiché sur la fiche projet
- [ ] Tests : ajout, historique/reconstitution, motif obligatoire, non-altération des imputations (INV-2/3), gating
- [ ] `make ci` vert · revue de clôture

## Notes
Le **budget courant** doit devenir la référence du suivi budgétaire (US-072) et de l'atterrissage (US-036)
à la place du `budgetCents` brut — à cadrer à l'implémentation (migration douce : initial = `budgetCents` actuel).
Le volet **devis** d'INV-8 relève d'EPIC-006 (CRM), hors périmètre de cette story.
