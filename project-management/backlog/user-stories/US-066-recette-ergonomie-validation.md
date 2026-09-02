# US-066: Recette d'ergonomie et validation utilisateurs

## Métadonnées
- **ID**: US-066
- **EPIC**: EPIC-012
- **Sprint**: À planifier (fast-track)
- **Statut**: 🔴 To Do
- **Points**: 3
- **Persona**: Tous (P1 à P6) — validation de l'ergonomie définitive par les utilisateurs cibles
- **Créé le**: 2026-09-02
- **Mis à jour**: 2026-09-02

## Traçabilité
- **Implémente**: EPIC-012 (D6 — ergonomie définitive validée), OBJ-7 (adoption), RSQ-1 (résistance à la saisie)
- **Dépend de**: US-064 (écrans reskinnés), US-065 (accessibilité AA)
- **Spec Technique**: personas, parcours clés

## User Story

**En tant que** organisation qui déploie l'outil,
**je veux** valider l'ergonomie définitive des écrans par des tests d'utilisabilité sur les personas cibles,
**afin de** confirmer que l'interface est facile à utiliser pour l'ensemble des utilisateurs et lever le risque d'adoption avant d'étendre le développement front aux lots suivants.

## Critères d'Acceptation

### CA-1 (Nominal) : Tests d'utilisabilité menés et ergonomie validée

```gherkin
GIVEN les écrans du lot 1 sont reskinnés (US-064) et accessibles AA (US-065)
WHEN une recette d'ergonomie est menée sur les parcours clés (saisie J+2, pilotage complétude, valorisation, validation des temps)
THEN chaque parcours est testé du point de vue des personas concernés (P1 saisie, P2/P3/P6 pilotage)
  AND les tâches clés sont réalisables sans blocage majeur (taux de réussite documenté)
  AND l'ergonomie définitive est validée (procès-verbal de validation) ou assortie d'un plan de correction priorisé
```

### CA-2 (Alternatif) : Objectif de rapidité de saisie vérifié

```gherkin
GIVEN l'objectif de saisie rapide (US-051 — saisie d'une journée en ~2 minutes)
WHEN un collaborateur (P1) réalise une saisie complète sur l'écran reskinné
THEN le temps de réalisation est mesuré et comparé à l'objectif
  AND tout écart significatif fait l'objet d'une recommandation d'ergonomie tracée
```

### CA-3 (Alternatif) : Retours utilisateurs collectés et priorisés

```gherkin
GIVEN des utilisateurs représentatifs participent à la recette
WHEN ils réalisent les parcours et donnent leur retour
THEN les retours sont collectés de façon structurée (irritants, confusions, satisfactions)
  AND ils sont priorisés (bloquant / majeur / mineur) et rattachés à l'écran concerné
  AND les points bloquants sont traités avant clôture d'EPIC-012 ; les mineurs sont versés au backlog
```

### CA-4 (Erreur) : Écart critique d'ergonomie détecté

```gherkin
GIVEN un parcours révèle un blocage critique (tâche non réalisable, confusion majeure)
WHEN l'écart est identifié en recette
THEN il est renvoyé en conception (US-062) puis correction (US-064) avant validation
  AND l'ergonomie définitive n'est pas déclarée validée tant qu'un blocage critique subsiste
```

### CA-5 (Erreur) : Périmètre d'extension front conditionné à la validation

```gherkin
GIVEN l'objectif d'EPIC-012 est de figer l'ergonomie avant d'étendre le dev front
WHEN l'extension du front à un lot suivant est envisagée
THEN elle n'est ouverte qu'une fois l'ergonomie du lot 1 validée (US-066 clôturée)
  AND le design system validé sert de base réutilisable pour les lots suivants
```

## Critères UI/UX

### Web
- Recette conduite sur les parcours réels, avec captures/enregistrements à l'appui des retours.
- Restitution : synthèse des retours priorisés et décision de validation.

### Mobile
- Les parcours mobiles clés (saisie mobile) sont inclus dans la recette d'ergonomie.

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Recette d'ergonomie menée sur les parcours clés (multi-personas)
- [ ] Retours collectés, priorisés, points bloquants traités
- [ ] Ergonomie définitive validée (procès-verbal) ou plan de correction acté
- [ ] Design system confirmé comme base réutilisable pour les lots suivants

---

## Notes

**Clôture d'EPIC-012** : cette US (D6) matérialise le critère de succès « ergonomie définitive validée ». Elle conditionne l'ouverture du dev front des lots suivants.

**Outillage** : la recette peut s'appuyer sur `qa:recette` (Claude in Chrome) pour les parcours et sur des sessions d'utilisabilité avec des utilisateurs représentatifs des 6 personas.

**Périmètre** : valider l'ergonomie, pas ajouter de fonctionnalité. Les retours mineurs alimentent le backlog des lots suivants.
