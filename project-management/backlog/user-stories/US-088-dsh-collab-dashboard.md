# US-088: DSH-COLLAB — Dashboard collaborateur (build)

## Métadonnées
- **ID**: US-088
- **EPIC**: EPIC-003 (Temps & activité)
- **Sprint**: 21
- **Statut**: 🟢 Ready
- **Points**: 5
- **Persona**: P1 (Camille, collaboratrice — 80 % des utilisateurs)
- **Créé le**: 2026-09-12

## Traçabilité
- **Maquette validée**: `design-canvas/lot2-saisie/Main.dc.html` (`lot2-saisie/VALIDATION.md` ✅)
- **Dépend de**: US-087 (G1 `StatCard` + G4 `PageHeader`)
- **Parcours**: `parcours-personas.md` §1 (P1) ; `backlog-reskin-priorise.md` (Must, build, 5 pts)

## User Story
**En tant que** collaboratrice (P1),
**je veux** un tableau de bord d'accueil qui me montre ma contrepartie (complétude, solde de congés, avancement
de mon projet) et un accès direct à la saisie,
**afin de** savoir en un coup d'œil où j'en suis et saisir mon temps en un clic (adoption, OBJ-7).

## Contexte (Conversation)
Écran **neuf** (build) : le dashboard collaborateur n'existe pas dans le produit. Conçu depuis le parcours P1
(US-081), il privilégie la **contrepartie visible** plutôt que des compteurs de volume (cf. audit §2, faiblesses
de la home hotones à ne pas reproduire). Point d'entrée `/` routé par profil vers ce dashboard pour P1.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : dashboard de contrepartie
```gherkin
GIVEN une collaboratrice connectée (P1)
WHEN elle arrive sur son tableau de bord
THEN elle voit un PageHeader (bonjour + date/semaine) et des StatCards : heures saisies vs objectif,
     complétude perso, solde de congés, avancement de son projet
  AND un accès « Saisir mes temps » proéminent (1 clic)
  AND ses imputations récentes et un accès « poser un congé »
```

### CA-2 (Alternatif) : semaine incomplète
```gherkin
GIVEN une semaine partiellement saisie
WHEN le dashboard s'affiche
THEN la complétude de la semaine est montrée avec les jours restants à compléter
```

### CA-3 (Alternatif) : conformité maquette + composants socle
```gherkin
GIVEN la maquette validée Main.dc.html
WHEN l'écran est développé
THEN il utilise `Ui:StatCard` (US-087) et `Layout:PageHeader` (US-087) et les tokens tailsfadmin
  AND aucun compteur de volume non actionnable n'est ajouté (écart audit justifié sinon)
```

### CA-4 (Erreur) : données absentes
```gherkin
GIVEN un collaborateur sans imputation ni projet actif
WHEN le dashboard s'affiche
THEN un état vide clair invite à saisir (pas d'erreur, pas d'identifiant technique brut — F1)
```

## Notes sur les livrables
- Contrôleur + route (routing d'accueil par profil) + template sur `layout admin` + `StatCard`/`PageHeader`.
- **Accessibilité** : WCAG 2.2 AA (contraste, cibles 44px, focus).
- **Hors périmètre** : pré-remplissage IA (EPIC-010, rupture R1) ; planning d'affectation (module non actif).

## Definition of Ready
- [x] Maquette validée PO (Main.dc.html)
- [x] Dépendance identifiée (US-087)
- [x] INVEST : Valuable ✓ (adoption P1) / Estimable ✓ (5 pts) / Testable ✓

## Definition of Done
- [ ] Écran conforme à la maquette validée, sur composants/tokens tailsfadmin (G1/G4 via US-087)
- [ ] Contrepartie (complétude, solde, avancement) + accès saisie 1 clic
- [ ] État vide géré ; aucun identifiant technique affiché (F1)
- [ ] WCAG 2.2 AA vérifié ; tests + CI verts ; PR mergée
