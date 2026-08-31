# US-003: Rôles et habilitations (RBAC + périmètre de données)

## Métadonnées
- **ID**: US-003
- **EPIC**: EPIC-000
- **Sprint**: Sprint 1
- **Statut**: 🔴 To Do
- **Points**: 8
- **Persona**: ADMIN
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-REF-31, ENF-SEC-5, HAB-1, HAB-2, HAB-3, HAB-4, HAB-5, HAB-6, ARC-19, ARC-106
- **Dépend de**: US-001, US-002
- **Spec Technique**: ARC-19 (habilitation dans la couche applicative), ARC-106 (périmètre de sécurité non délégué à l'UI)

## User Story

**En tant qu'** administrateur tenant,
**je veux** configurer des rôles combinant des permissions fonctionnelles et un périmètre de données (de "ses données personnelles" à "toutes les données du tenant"), et que la matrice des rôles standard définie dans le cahier des charges soit reproductible par paramétrage,
**afin de** garantir que chaque collaborateur ne voit et n'agit que sur ce qui le concerne, sans que l'interface utilisateur ne soit le seul garde-fou (la vérification est faite à l'accès à la donnée, côté serveur).

## Critères d'Acceptation

### CA-1 (Nominal — HAB-1) : Un chef de projet ne voit jamais le coût journalier d'un collaborateur
```gherkin
GIVEN l'utilisateur Marc (rôle "Chef de projet", périmètre "ses projets") est connecté
  AND le collaborateur Camille (coût journalier = 450 €/j) est affecté à un projet dont Marc est responsable
WHEN Marc consulte la fiche de Camille, le planning ou tout rapport de rentabilité du projet
THEN le coût journalier de Camille (450 €/j) n'apparaît dans aucune vue, aucun export et aucune réponse API
  AND si Marc appelle directement GET /api/collaborators/{id}/cost, la réponse HTTP est 403 Forbidden
  AND aucune donnée de coût n'est retournée même partiellement dans la réponse 403
```

### CA-2 (Nominal) : Vérification de l'habilitation côté serveur, pas uniquement côté UI
```gherkin
GIVEN l'utilisateur Camille (rôle "Collaborateur", périmètre "ses données") est connectée
  AND le bouton "Supprimer projet" est masqué dans l'interface (absence de permission delete:project)
WHEN Camille forge une requête HTTP DELETE /api/projects/{id-projet-autre-collaborateur} directement via curl
THEN la réponse HTTP est 403 Forbidden avec le corps {"error": "Permission refusée : delete:project"}
  AND aucune suppression n'a lieu en base de données
  AND un événement "unauthorized_action_attempt" est tracé dans les logs de sécurité
```

### CA-3 (Alternatif — HAB-6) : Lecture d'une donnée sensible tracée
```gherkin
GIVEN l'utilisateur Sophie (rôle "Resource Manager", périmètre "tous les collaborateurs") est connectée
  AND la consultation du coût journalier d'un collaborateur est marquée comme action sensible (HAB-6)
WHEN Sophie consulte la fiche de Camille contenant son coût journalier
THEN l'accès est accordé (Sophie a la permission view:collaborator_cost)
  AND un événement d'audit "sensitive_data_read" est enregistré avec : identifiant de l'acteur, identifiant de la ressource consultée, horodatage précis et tenant_id
  AND cet événement est consultable par l'administrateur tenant dans la console d'audit dans les 30 secondes
```

### CA-4 (Alternatif) : La matrice de rôles par défaut est reproductible par paramétrage
```gherkin
GIVEN un nouveau tenant "AgenceGamma" est créé sans aucune configuration RBAC manuelle
WHEN l'administrateur applique la commande "Initialiser la matrice de rôles par défaut"
THEN les rôles "Collaborateur", "Chef de projet", "Resource Manager", "Dirigeant" et "Administrateur" sont créés avec leurs permissions et périmètres documentés dans le CDC
  AND un test automatisé vérifie que chaque rôle créé correspond exactement à la matrice de référence (assertion sur permissions et périmètre)
  AND l'opération est idempotente : relancer la commande ne crée pas de doublons
```

### CA-5 (Erreur) : Accès à une ressource hors périmètre de données
```gherkin
GIVEN l'utilisateur Marc (rôle "Chef de projet", périmètre "ses projets") est connecté
  AND le projet "Projet X" est assigné à un autre chef de projet
WHEN Marc appelle GET /api/projects/{id-projet-X}
THEN la réponse HTTP est 403 Forbidden avec le corps {"error": "Ressource hors périmètre autorisé"}
  AND aucune donnée du Projet X n'est retournée
  AND l'événement "out_of_scope_access_attempt" est tracé avec l'identifiant de la ressource tentée
```

### CA-6 (Erreur — HAB-6) : Tentative d'attribution d'un rôle au périmètre supérieur à celui de l'auteur → refus et trace
```gherkin
GIVEN l'utilisateur Jordan (rôle "Administrateur tenant", périmètre "son agence uniquement") est connecté
  AND le rôle "Resource Manager" avec le périmètre "tous les collaborateurs du tenant" est défini dans la matrice de rôles
WHEN Jordan tente d'attribuer ce rôle "Resource Manager, périmètre tous les collaborateurs" à l'utilisateur Noé via POST /api/tenants/{id}/users/{id}/roles
THEN la réponse HTTP est 403 Forbidden avec le corps {"error": "Attribution impossible : le périmètre du rôle accordé (tous les collaborateurs) excède le périmètre autorisé de l'auteur (son agence)"}
  AND aucune modification du rôle de Noé n'est persistée en base de données
  AND un événement "privilege_escalation_attempt" est tracé dans les logs de sécurité avec : identifiant de l'auteur, identifiant du bénéficiaire, rôle tenté, périmètre tenté et horodatage précis
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

Conformément à ARC-106, la vérification des habilitations n'est jamais déléguée à l'UI. Masquer un bouton est une aide à l'ergonomie, pas un contrôle de sécurité. Chaque action sensible déclenche une vérification côté serveur, indépendamment de l'état de l'interface.

Conformément à ARC-19, la couche applicative (use cases / command handlers) est responsable de vérifier l'habilitation avant toute opération métier. Les vérifications ne sont pas dans les controllers ni dans la couche domaine.

Le périmètre de données est un axe orthogonal aux permissions : un "Chef de projet" peut avoir la permission "view:project" mais son périmètre restreint "ses projets" filtre les instances auxquelles cette permission s'applique.

HAB-1 est le critère de conformité le plus sensible de la matrice : le coût journalier est considéré comme une donnée RH confidentielle. Son exposition par quelque chemin que ce soit (API, export, websocket, log d'erreur) constitue un défaut critique.
