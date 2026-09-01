# US-010: Structure organisationnelle et rattachements historisés

## Métadonnées
- **ID**: US-010
- **EPIC**: EPIC-001
- **Sprint**: Sprint 4
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: ADMIN
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-09-01
<!-- last_sync: 2026-09-01 (source: workflow-status.yaml current_sprint id:4) -->

## Traçabilité
- **Implémente**: EF-REF-1, EF-REF-2, EF-REF-3, RG-REF-1
- **Dépend de**: US-001 (fondation multi-tenant)
- **Spec Technique**: EF-REF-1 (hiérarchie paramétrable ≥3 niveaux), EF-REF-2 (rattachement historisé), EF-REF-3 (multi-entités juridiques)

## User Story

**En tant qu'** administrateur tenant,
**je veux** paramétrer librement la hiérarchie organisationnelle (de 1 à N niveaux, sans développement) et historiser à date d'effet les rattachements des collaborateurs à leurs unités,
**afin de** refléter fidèlement l'organisation réelle de l'agence à chaque instant passé, présent et futur, et permettre une consolidation fiable par entité juridique.

## Critères d'Acceptation

### CA-1 (Nominal) : Hiérarchie à niveau unique pour un tenant de 12 personnes
```gherkin
GIVEN un tenant "PetiteAgence" ayant 12 collaborateurs sans besoin de subdivisions internes
  AND aucun niveau intermédiaire n'est configuré
WHEN l'ADMIN crée l'unité racine "PetiteAgence" et y rattache les 12 collaborateurs
THEN l'arbre organisationnel affiche un seul niveau avec les 12 collaborateurs
  AND aucune rubrique "sous-unité" n'est imposée dans l'interface
  AND la consolidation des activités porte sur le tenant entier sans configuration supplémentaire
```

### CA-2 (Nominal) : Hiérarchie à 3 niveaux pour un tenant de 140 personnes
```gherkin
GIVEN un tenant "ESN140" ayant 140 collaborateurs répartis en Pôles > Départements > Équipes
  AND l'ADMIN crée 3 niveaux hiérarchiques librement nommés dans les paramètres tenant
WHEN l'ADMIN crée les unités niveau 1 (Pôles), niveau 2 (Départements) et niveau 3 (Équipes)
  AND rattache chaque collaborateur à son équipe de niveau 3
THEN la navigation dans l'arbre organisationnel respecte les 3 niveaux configurés
  AND aucun développement spécifique n'a été nécessaire pour passer de 1 à 3 niveaux
  AND un rapport de consolidation agrège les données Pôle > Département > Équipe correctement
```

### CA-3 (Alternatif) : Changement d'équipe au 01/03 — les données antérieures restent rattachées à l'ancienne équipe
```gherkin
GIVEN le collaborateur "Alice Dupont" est rattachée à "Équipe Frontend" depuis le 01/01
  AND des saisies de temps valident son rattachement en janvier et février
WHEN l'ADMIN enregistre un changement de rattachement vers "Équipe Backend" avec date d'effet 01/03
THEN les saisies de temps de janvier et février restent historiquement associées à "Équipe Frontend"
  AND à partir du 01/03, les nouvelles saisies d'Alice sont associées à "Équipe Backend"
  AND la timeline du collaborateur affiche les deux périodes avec leurs unités respectives
  AND aucune donnée historique n'est modifiée ou perdue
```

### CA-4 (Alternatif) : Consolidation multi-entités juridiques
```gherkin
GIVEN un tenant possède deux entités juridiques distinctes "SAS Alpha" et "SARL Beta"
  AND chaque entité contient ses propres unités et collaborateurs
WHEN un utilisateur ayant le rôle consolidateur accède à la vue multi-entités
THEN les KPI (effectifs, jours ouvrés, jours saisis) s'affichent cumulés pour les deux entités
  AND un filtre permet d'isoler la vue par entité juridique
  AND les données restent isolées par tenant (aucune donnée d'un autre tenant n'apparaît)
```

### CA-5 (Erreur) : Suppression d'une unité utilisée → refus, proposition de désactivation
```gherkin
GIVEN l'unité "Équipe Mobile" est référencée dans 5 saisies de temps et 2 projets actifs
WHEN l'ADMIN tente de supprimer "Équipe Mobile" via l'interface
THEN la suppression est refusée avec le message "Impossible de supprimer une unité référencée (5 saisies, 2 projets). Utilisez la désactivation."
  AND le bouton "Désactiver" est proposé comme alternative
  AND l'unité reste visible dans l'historique des données passées après désactivation
  AND aucune donnée existante n'est altérée (conformément à RG-REF-1)
```

### CA-6 (Erreur) : Rattachement cyclique d'unités → refus
```gherkin
GIVEN l'arbre organisationnel contient "Pôle Digital" (niveau 1) > "Département Web" (niveau 2) > "Équipe Frontend" (niveau 3)
WHEN l'ADMIN tente de définir "Pôle Digital" comme unité parente de "Équipe Frontend" (créant le cycle Équipe Frontend → Pôle Digital → Département Web → Équipe Frontend)
THEN le système refuse l'opération avec le message "Rattachement impossible : cette relation crée une boucle hiérarchique (Équipe Frontend → Pôle Digital → Département Web → Équipe Frontend)."
  AND l'arbre organisationnel reste inchangé
  AND aucune modification n'est persistée en base de données
  AND la détection de cycle est effectuée avant toute écriture (protection anti-boucle côté serveur)
```

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Code reviewé
- [ ] Tests unitaires passent
- [ ] Tests d'intégration passent
- [ ] Documentation mise à jour

---

## Notes

Le nombre de niveaux hiérarchiques doit être entièrement piloté par la configuration (table `org_level_config` tenant-scoped) sans aucun code spécifique. La modification du nombre de niveaux doit être possible sans migration de données si les rattachements existants restent cohérents.

L'historisation du rattachement collaborateur/unité (EF-REF-2) repose sur une table `org_membership` avec colonnes `effective_from` / `effective_to` (NULL = en cours). Le filtre temporel doit être appliqué automatiquement dans les requêtes de reporting.

RG-REF-1 : aucune suppression de référentiel utilisé — uniquement désactivation avec date d'effet.
