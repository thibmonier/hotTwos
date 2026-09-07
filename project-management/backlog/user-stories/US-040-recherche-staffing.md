# US-040: Recherche de staffing par compétence & disponibilité

## Métadonnées
- **ID**: US-040
- **EPIC**: EPIC-004 (Planification & Staffing)
- **Sprint**: 18
- **Statut**: 🟢 Ready
- **Points**: 8
- **Persona**: P3 (Resource Manager)
- **Créé le**: 2026-09-07
- **Mis à jour**: 2026-09-07 (affinage S18)

## Traçabilité
- **Implémente**: EPIC-004 (module RES — recherche de staffing), OBJ-3
- **Dépend de**: US-013 (compétences/niveaux), US-041 (disponibilité = capacité − charge ferme), US-037 (affectations)
- **Réutilise / ne re-spécifie pas** : `Skill`/`SkillAssignment` (US-013), le calcul de disponibilité d'US-041.

## User Story

**En tant que** resource manager,
**je veux** rechercher les collaborateurs **maîtrisant une compétence à un niveau minimal** et
**disponibles** sur une période,
**afin de** staffer les projets sur des critères objectifs, sans tableur.

## Contexte (Conversation)
Le référentiel de compétences (US-013) et le calcul de disponibilité (US-041 : capacité − charge ferme)
existent. Cette US les **croise** : recherche par (compétence, niveau min) filtrée par disponibilité sur
une période. Recherche simple (ET des critères) ; pas de scoring/ranking avancé (reporté).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : recherche par compétence + niveau minimal
```gherkin
GIVEN des collaborateurs avec la compétence « Java » à divers niveaux
WHEN le resource manager recherche « Java, niveau ≥ Avancé »
THEN seuls les collaborateurs possédant Java à un niveau ≥ Avancé sont retournés
  AND chaque résultat indique le niveau du collaborateur pour cette compétence
```

### CA-2 (Nominal) : filtrage par disponibilité sur la période
```gherkin
GIVEN deux collaborateurs possédant la compétence recherchée
  AND l'un a des jours disponibles sur la période, l'autre est en surcharge
WHEN la recherche est lancée avec un filtre « disponible sur la période »
THEN le collaborateur disponible apparaît en premier (ou seul, selon le filtre)
  AND la disponibilité (jours) est affichée pour chaque résultat
```

### CA-3 (Alternatif) : aucune correspondance
```gherkin
GIVEN aucune personne ne possède la compétence au niveau demandé
WHEN la recherche est lancée
THEN une liste vide est retournée avec un message explicite
```

### CA-4 (Alternatif) : compétence désactivée exclue par défaut
```gherkin
GIVEN une compétence désactivée (US-013)
WHEN le resource manager lance une recherche
THEN cette compétence n'est pas proposée comme critère de recherche
```

### CA-5 (Erreur) : coûts non visibles dans les résultats (HAB-1)
```gherkin
GIVEN un resource manager sans VIEW_COLLABORATOR_COST
WHEN il consulte les résultats de recherche
THEN aucun coût unitaire n'est affiché
```

### CA-6 (Erreur) : accès réservé
```gherkin
GIVEN un utilisateur sans habilitation de planification
WHEN il tente d'accéder à la recherche de staffing
THEN l'accès est refusé (403)
```

## Notes techniques (pour la décomposition)
- **Recherche compétence** : ajouter au port `SkillAssignmentRepository` une méthode
  `findUserIdsBySkillAtLeast(tenant, skillId, minLevel): list<string>` (Doctrine + fake).
- **Disponibilité** : réutiliser le read model d'US-041 (`ViewWorkloadPlan`/dispo par user sur la période).
- **Read model** : `Application\Staffing\SearchStaffing` → `StaffingCandidateView` (userId, nom, niveau, jours disponibles). Gating HAB-1.
- **Gating** : introduire une permission de planification (`VIEW_STAFFING` ou réutiliser un rôle Resource Manager) — **décision en décomposition** ; par défaut réserver aux rôles Resource Manager / Administrateur / Dirigeant.
- **UI** : page `/planification/recherche` (Twig) : sélecteur compétence + niveau + période, liste des candidats.
- **Hors périmètre** : scoring/ranking multi-critères avancé, matching automatique.
- **Tests** : unit (filtrage compétence+niveau, croisement dispo) ; fonctionnels (recherche, vide, 403, HAB-1) ; SchemaTool.

## Definition of Ready
- [x] Description INVEST (recherche simple ; scoring avancé reporté)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Réutilise US-013 (compétences) + US-041 (disponibilité) ; méthodes repo identifiées
- [x] Estimation 8 pts ; RLS/gating/HAB-1 explicités ; **dépend d'US-041 (ordre : US-041 avant US-040)**

## Definition of Done
- [ ] CA validés (unit + fonctionnels) ; `make ci` vert ; code review
