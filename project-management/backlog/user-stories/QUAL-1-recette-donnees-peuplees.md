# QUAL-1: Recette navigateur sur données peuplées (écrans finance)

## Métadonnées
- **ID**: QUAL-1 (story technique / dette qualité)
- **EPIC**: — (action rétrospective transverse)
- **Sprint**: Sprint 10 (🔴 Must — **jour 1**)
- **Statut**: 🟢 Ready (affinée S10)
- **Points**: — (dette, hors vélocité) · **Estimation**: ~1 j
- **Persona**: P6 (Directeur financier), P2 (Chef de projet) — via recette
- **Créé le**: 2026-09-05

## Traçabilité
- **Origine**: action rétrospective **reportée S7 → S8 → S9** (escaladée en S10). Réf. audit S9 (recette sur données peuplées non tenue).
- **Couvre**: écrans finance livrés au S9 — `/valorisation`, suivi budgétaire (fiche projet), `/finance` (consolidé).

## User Story

**En tant que** Product Owner / contrôleur de gestion,
**je veux** une **recette navigateur sur un jeu de données peuplé** des écrans finance,
**afin de** valider le comportement réel (gating, chiffres, dérive) au-delà des tests automatisés, et solder une dette de recette chronique.

## Critères d'Acceptation

### CA-1 (Nominal) : Seed peuplé finance
```gherkin
GIVEN le seed de démo (app:demo:seed / make db-reset)
WHEN on l'étend avec des projets budgétés (coût + CA cible), des imputations valorisées et
     au moins une période clôturée (marges figées)
THEN /valorisation, la fiche projet (onglet Suivi budgétaire) et /finance affichent des données
     non triviales (CA, coût, marge, dérive, ventilation client/projet)
```

### CA-2 (Gating) : Recette du gating HAB-1 sur données réelles
```gherkin
GIVEN les comptes de démo (chef de projet, dirigeant, collaborateur)
WHEN chacun ouvre les écrans finance
THEN le chef de projet voit le CA mais pas le coût/marge/dérive ; le dirigeant voit tout ;
     le collaborateur est refusé (403) sur /finance
  AND chaque constat est capturé (capture d'écran) et tracé
```

### CA-3 (Traçabilité) : Rapport de recette
```gherkin
GIVEN la recette exécutée sur le seed peuplé
WHEN elle est terminée
THEN un rapport est produit dans .recette/ (écrans couverts, comptes testés, captures, findings)
  AND chaque finding est backlogué (US/ticket)
```

## Definition of Done
- [ ] Seed finance peuplé (projets budgétés + valorisation + période clôturée) reproductible
- [ ] Recette navigateur exécutée (Claude in Chrome ou manuelle) sur les 3 écrans finance, 3 rôles
- [ ] Rapport `.recette/` committé, findings backlogués
- [ ] Gating HAB-1 vérifié sur données réelles

## Notes
Prérequis d'une **Sprint Review sur environnement réel** (cf. rétro S9). À traiter **avant** le dev de US-074 pour dé-risquer.
