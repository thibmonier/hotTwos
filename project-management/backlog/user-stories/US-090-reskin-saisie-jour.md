# US-090: PG-TMP-02/03 — Reskin saisie du jour (mobile)

## Métadonnées
- **ID**: US-090
- **EPIC**: EPIC-003 (Temps & activité)
- **Sprint**: 21
- **Statut**: 🟢 Ready
- **Points**: 2
- **Persona**: P1 (Camille, en mobilité)
- **Créé le**: 2026-09-12

## Traçabilité
- **Maquette validée**: `lot2-saisie/SaisieJour.dc.html`
- **Recos audit**: `audit-ux-existant.md` §4 (TMP2-01→05)
- **Écran existant**: `templates/timesheet/day.html.twig` (routes `timesheet_day`, `timesheet_day_today`)

## User Story
**En tant que** collaboratrice en mobilité (P1),
**je veux** saisir ma journée depuis mon mobile, même hors-ligne, avec objectif et total du jour,
**afin de** déclarer rapidement mon temps sur le terrain.

## Contexte (Conversation)
Reskin de l'écran existant en préservant ses atouts (bannières hors-ligne + resync, cartes par projet) et en
appliquant les recos d'audit. Logique inchangée.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : conforme maquette
```gherkin
GIVEN la maquette validée SaisieJour.dc.html
WHEN l'écran est reskinné
THEN il affiche total du jour + objectif, cartes projet (durée + commentaire), navigation jour
  AND les cibles sont ≥ 44px et la mise en page est lisible sur ~390px
```

### CA-2 (Alternatif) : hors-ligne préservé
```gherkin
GIVEN une perte de connexion
WHEN je saisis
THEN les bannières hors-ligne et resync restent fonctionnelles (atout TMP2-01)
```

### CA-3 (Alternatif) : total serveur + objectif
```gherkin
GIVEN une journée déjà saisie
WHEN la page se charge
THEN le total du jour est correct au premier rendu et l'objectif du jour est affiché — recos TMP2-02/03
```

### CA-4 (Erreur) : aucun projet actif
```gherkin
GIVEN aucun projet actif
WHEN j'ouvre la saisie du jour
THEN un état vide clair est affiché (comportement existant préservé)
```

## Notes sur les livrables
- Reskin `day.html.twig` sur tokens tailsfadmin ; commentaire en `textarea` (reco TMP2-04).
- **Accessibilité** : WCAG 2.2 AA ; `aria-live` conservés.

## Definition of Ready
- [x] Maquette validée ; écran existant identifié ; recos audit disponibles
- [x] INVEST : Estimable ✓ (2 pts) / Testable ✓

## Definition of Done
- [ ] Écran conforme à la maquette, tokens tailsfadmin ; hors-ligne préservé
- [ ] Total serveur + objectif du jour ; textarea commentaire
- [ ] Tests verts ; WCAG AA ; CI verte ; PR mergée
