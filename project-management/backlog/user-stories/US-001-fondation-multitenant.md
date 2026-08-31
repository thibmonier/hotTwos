# US-001: Fondation multi-tenant et isolation des données

## Métadonnées
- **ID**: US-001
- **EPIC**: EPIC-000
- **Sprint**: Sprint 1
- **Statut**: 🔴 To Do
- **Points**: 8
- **Persona**: ADMIN / RSSI
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: INV-1, ENF-SEC-4, ENF-SEC-5, ARC-2, ARC-33, ARC-34
- **Dépend de**: — (story fondatrice)
- **Spec Technique**: ARC-2 (modèle de données multi-tenant), ARC-33 (filtre ORM automatique), ARC-34 (Row-Level Security PostgreSQL)

## User Story

**En tant qu'** administrateur tenant et RSSI,
**je veux** que chaque entité applicative soit rattachée à un identifiant de tenant, protégée par un filtre ORM automatique et par le Row-Level Security PostgreSQL,
**afin de** garantir une isolation totale des données entre tenants et rendre toute fuite cross-tenant techniquement impossible, même en cas de bug applicatif.

## Critères d'Acceptation

### CA-1 (Nominal) : Données d'un tenant A invisibles depuis un tenant B
```gherkin
GIVEN deux tenants distincts "AgenceAlpha" (ID: T-001) et "AgenceBeta" (ID: T-002) existent en base
  AND chacun possède au minimum un utilisateur et un projet rattachés à son tenant_id
WHEN un utilisateur authentifié du tenant T-002 appelle n'importe quel endpoint de liste (projects, users, timesheets…)
THEN la réponse ne contient aucune entité appartenant à T-001
  AND le discriminant tenant_id = 'T-002' est appliqué dans la requête SQL effective (visible dans le query log)
  AND le code HTTP retourné est 200 avec une liste ne contenant que les entités de T-002
```

### CA-2 (Alternatif) : Le filtre RLS bloque une requête SQL forgée directement en base
```gherkin
GIVEN une connexion directe à PostgreSQL avec le rôle applicatif du tenant T-002 est établie (hors ORM)
  AND le paramètre de session app.current_tenant n'est pas positionné sur T-001
WHEN une requête SELECT * FROM projects WHERE tenant_id = 'T-001' est exécutée
THEN PostgreSQL retourne 0 lignes (RLS masque les lignes d'un autre tenant)
  AND aucune erreur 500 n'est levée côté applicatif (comportement silencieux attendu pour ne pas révéler l'existence du tenant)
```

### CA-3 (Alternatif) : Le filtre ORM s'applique sans code explicite dans les repositories
```gherkin
GIVEN le filtre ORM global de tenant est activé dans la configuration Doctrine
  AND un nouveau repository ProjectRepository est créé sans aucune clause WHERE tenant_id explicite
WHEN ProjectRepository::findAll() est appelé dans le contexte du tenant T-001
THEN la requête SQL générée contient automatiquement AND p0_.tenant_id = 'T-001'
  AND le test d'intégration vérifiant l'absence de clause WHERE tenant_id explicite dans le repository est vert
```

### CA-4 (Erreur) : Tentative d'accès par identifiant de tenant forgé dans un JWT → refus et trace
```gherkin
GIVEN un utilisateur authentifié du tenant T-002 possède un JWT valide contenant claim tenant_id = 'T-002'
WHEN il forge manuellement un JWT avec tenant_id = 'T-001' et l'envoie dans l'en-tête Authorization
THEN la réponse HTTP est 401 Unauthorized (signature JWT invalide car re-signé) ou 403 Forbidden si le claim est extrait d'un token valide par un autre moyen
  AND un événement de sécurité "tenant_forgery_attempt" est enregistré dans les logs avec l'IP source, l'heure et l'identifiant utilisateur
  AND aucune donnée du tenant T-001 n'est retournée
```

### CA-5 (Erreur) : Export cross-tenant tenté via l'API → refus
```gherkin
GIVEN un utilisateur administrateur du tenant T-002 appelle l'endpoint d'export GET /api/exports?tenant_id=T-001
WHEN la requête est traitée par l'application
THEN la réponse HTTP est 403 Forbidden avec le message "Accès refusé : périmètre tenant non autorisé"
  AND l'export ne contient aucune donnée du tenant T-001
  AND un événement "unauthorized_cross_tenant_export_attempt" est tracé avec les métadonnées de la requête
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

**Critère bloquant MEP** : un test d'intrusion dédié à l'isolation multi-tenant doit être réalisé et son rapport doit conclure à l'absence de chemin de fuite cross-tenant avant toute mise en production (ENF-SEC-4).

La double barrière (ARC-33 filtre ORM + ARC-34 RLS PostgreSQL) est la garantie technique de base de toute la plateforme. Cette US doit être livrée et validée avant US-003 (RBAC) et toute story métier.

Vérifier que le filtre ORM est bien activé en mode EAGER (appliqué même sur les requêtes natives Doctrine DQL) et que le paramètre de session PostgreSQL `app.current_tenant` est positionné systématiquement en début de requête applicative.
