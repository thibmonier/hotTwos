# US-089: PG-TMP-01 — Reskin saisie hebdomadaire

## Métadonnées
- **ID**: US-089
- **EPIC**: EPIC-003 (Temps & activité)
- **Sprint**: 21
- **Statut**: 🟢 Ready
- **Points**: 3
- **Persona**: P1 (Camille)
- **Créé le**: 2026-09-12

## Traçabilité
- **Maquettes validées**: `lot2-saisie/SaisieHebdo.dc.html` (+ `SaisieHebdoEtats.dc.html`, `SaisieHebdoMobile.dc.html`)
- **Recos audit**: `audit-ux-existant.md` §3 (TMP1-01→07) et §8
- **Écran existant**: `templates/timesheet/week.html.twig` (route `timesheet_week`)

## User Story
**En tant que** collaboratrice (P1),
**je veux** saisir ma semaine sur un écran clair et rapide (≤ 2 min), avec totaux, objectif et gestion des erreurs,
**afin de** déclarer mon temps sans friction (rejet si > 2 min — RSQ-1).

## Contexte (Conversation)
Reskin de l'écran existant sur le socle tailsfadmin, en appliquant les recommandations d'audit. La **logique**
de saisie ne change pas (autosave, duplication semaine, raccourcis clavier) ; c'est la **présentation** et
quelques ajouts UX qui évoluent. Les tests fonctionnels existants doivent rester verts.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : grille conforme maquette
```gherkin
GIVEN la maquette validée SaisieHebdo.dc.html
WHEN l'écran /saisie est reskinné
THEN la grille projet × jours ouvrés affiche les totaux ligne/jour/semaine
  AND l'objectif de la semaine est visible hors dialog (barre de progression)
  AND le code projet est lisible en libellé secondaire (pas seulement en title) — reco TMP1-07
```

### CA-2 (Alternatif) : totaux rendus serveur
```gherkin
GIVEN une semaine déjà saisie
WHEN la page se charge
THEN les totaux sont corrects au premier rendu (pas de « — » en attendant le JS) — reco TMP1-01
```

### CA-3 (Alternatif) : état erreur de validation
```gherkin
GIVEN une cellule dépassant 24 h/jour
WHEN la validation se déclenche
THEN la cellule est en erreur (bordure + message + focus), une alerte accessible s'affiche
  AND l'enregistrement est bloqué tant que l'erreur persiste — reco TMP1-05 (maquette SaisieHebdoEtats)
```

### CA-4 (Alternatif) : mobile
```gherkin
GIVEN un écran ~390px
WHEN j'ouvre la saisie
THEN une variante mobile lisible est proposée (sélecteur de jour + cartes projet) — maquette SaisieHebdoMobile
```

### CA-5 (Erreur) : aucun projet actif
```gherkin
GIVEN aucun projet actif
WHEN j'ouvre /saisie
THEN un état vide clair m'invite à contacter un administrateur (comportement existant préservé)
```

## Notes sur les livrables
- Reskin `week.html.twig` (+ éventuel contrôleur pour totaux serveur) sur tokens tailsfadmin.
- **Accessibilité** : cibles 44px, focus, `aria-live` conservés ; contraste AA.
- **Hors périmètre** : pré-remplissage IA (EPIC-010).

## Definition of Ready
- [x] Maquettes validées (rempli + états + mobile)
- [x] Recos audit disponibles ; écran existant identifié
- [x] INVEST : Estimable ✓ (3 pts) / Testable ✓ (tests existants + nouveaux états)

## Definition of Done
- [ ] Écran conforme aux maquettes validées, tokens tailsfadmin
- [ ] Totaux serveur · objectif visible · état erreur · variante mobile
- [ ] Tests existants verts + tests des nouveaux états ; WCAG AA ; CI verte ; PR mergée
