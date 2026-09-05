# US-062: Conception UX/UI des écrans du lot 1 (maquettes validées)

## Métadonnées
- **ID**: US-062
- **EPIC**: EPIC-012
- **Sprint**: À planifier (fast-track)
- **Statut**: ✅ Done (livré Sprint 7)
- **Points**: 5
- **Persona**: Tous (P1 à P6) — parcours et écrans couvrant tous les rôles
- **Créé le**: 2026-09-02
- **Mis à jour**: 2026-09-02

## Traçabilité
- **Implémente**: EPIC-012 (D2 — maquettes validées), OBJ-7 (adoption), RSQ-1 (résistance à la saisie)
- **Dépend de**: US-061 (charte & design system), écrans livrés lot 1 (US-050, US-054, US-056, US-058, US-059, US-060, organisation, profils, administration/périodes)
- **Prérequis de**: US-064 (reskin des écrans — la conception précède le dev front, consigne PO)
- **Spec Technique**: `design.md`, parcours utilisateurs

## User Story

**En tant que** utilisateurs de tous les rôles (collaborateur, chef de projet, resource manager, direction),
**je veux** que les parcours et les maquettes UX/UI des écrans déjà livrés (saisie, projets, complétude, absences, relances, valorisation, organisation, profils, administration) soient conçus et validés par l'équipe UX/UI avant toute intégration,
**afin de** figer une ergonomie définitive éprouvée et éviter de reskiner sur une base d'interaction non validée.

## Critères d'Acceptation

### CA-1 (Nominal) : Maquettes des écrans du lot 1 conçues et validées

```gherkin
GIVEN la charte (US-061) est disponible
  AND les écrans du lot 1 sont livrés en walking skeleton
WHEN l'équipe UX/UI (ux-ergonome + ui-designer + accessibility-expert) conçoit les maquettes
THEN chaque écran du lot 1 dispose d'une maquette (états principaux : chargé, vide, erreur, chargement)
  AND les parcours clés sont documentés (saisie J+2, pilotage complétude, valorisation, validation des temps)
  AND les maquettes sont dérivées de la charte (tokens et composants US-061, aucun élément hors design system)
  AND chaque maquette porte une trace de validation (validée / date / par qui) avant passage en intégration
```

### CA-2 (Alternatif) : Prise en compte des personas et de leurs habilitations

```gherkin
GIVEN les 6 personas ont des besoins et habilitations distincts
WHEN les maquettes sont conçues
THEN les vues sensibles (coût/marge, audit trail — HAB) sont maquettées en tenant compte des permissions (ce que chaque rôle voit)
  AND les écrans de saisie (P1 Camille) priorisent la rapidité (objectif 2 minutes, cf. US-051)
  AND les écrans de pilotage (P2/P3/P6) priorisent la lisibilité de l'information clé
```

### CA-3 (Alternatif) : Responsive et parité mobile conçus en amont

```gherkin
GIVEN certains écrans sont utilisés en mobilité (saisie mobile — US-052)
WHEN les maquettes sont produites
THEN chaque écran dispose d'une déclinaison mobile ET desktop
  AND aucune action clé n'est disponible uniquement au survol (parité tactile)
  AND les cibles tactiles respectent 44 × 44 px minimum
```

### CA-4 (Erreur) : Écarts d'ergonomie recette adressés dans les maquettes

```gherkin
GIVEN la recette a relevé des écarts (F-S5-4 collaborateurs indistinguables, F-S5-5 code couleur partiel, rappel F1 identifiants bruts)
WHEN les maquettes sont conçues
THEN chaque écart recensé est adressé par une décision d'ergonomie tracée dans la maquette concernée
  AND aucun identifiant technique brut (UUID, préfixe) n'est présenté à l'utilisateur dans les maquettes
```

### CA-5 (Erreur) : Aucune intégration front avant validation des maquettes

```gherkin
GIVEN la consigne PO impose la conception UX/UI avant tout dev front
WHEN une demande d'intégration (reskin) d'un écran est formulée
THEN elle est refusée tant que la maquette de l'écran n'est pas validée
  AND la traçabilité de validation conditionne le démarrage de US-064 pour l'écran concerné
```

## Critères UI/UX

### Web
- Livrables : maquettes (wireframes ou haute-fidélité) et parcours, cohérents avec le styleguide US-061.
- Chaque écran couvre ses états : nominal, vide, erreur, chargement, sans-permission.

### Mobile
- Déclinaison mobile pour chaque écran utilisé en mobilité, avec disposition adaptée (liste vs grille).

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Maquettes des écrans du lot 1 produites (états + mobile/desktop)
- [ ] Parcours clés documentés
- [ ] Validation UX/UI tracée pour chaque écran
- [ ] Écarts recette (F-S5-4, F-S5-5, F1) adressés

---

## Notes

**Consigne PO (mémoire projet)** : la phase de conception UX/UI (ux-ergonome + ui-designer + accessibility-expert, orchestrés par uiux-orchestrator) précède tout dev front. Cette US (D2) est un prérequis strict de US-064 (D4).

**Commande** : `/workflow:design` et agents UX/UI. Le chantier F-S5-4 (`project-management/chantiers/`) est absorbé ici pour la conception, puis intégré en US-064.

**Périmètre** : concevoir et valider, pas intégrer. L'intégration du layout est US-063, le reskin est US-064.
