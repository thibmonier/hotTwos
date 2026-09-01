# Module Organisation (US-010)

Structure organisationnelle paramétrable et rattachements historisés des collaborateurs.
Socle du référentiel (EPIC-001) et pivot de la valorisation : le rattachement
collaborateur → unité, historisé à date d'effet, alimentera la consolidation par entité.

## Modèle de domaine

| Élément | Rôle |
|---------|------|
| `Domain\Shared\EffectivePeriod` | VO de période de validité, intervalle **semi-ouvert** `[from, to)` (borne haute exclue), `to = null` = « en cours ». `contains` / `overlaps` / `equals`. Partagé avec US-011 (tarifs). |
| `Domain\Organization\OrgLevelConfig` | Niveau hiérarchique nommé et paramétrable (1..N, sans développement). Unicité `(tenant, position)`. |
| `Domain\Organization\OrgUnit` | Nœud de la hiérarchie (parent auto-référencé). **Désactivable, jamais supprimé** (RG-REF-1). Une racine peut représenter une entité juridique (EF-REF-3). |
| `Domain\Organization\OrgMembership` | Rattachement historisé (VO `EffectivePeriod` décomposé en `effective_from` / `effective_to`). |

Toutes les entités sont `TenantOwned` (colonne `tenant_id`, isolation double barrière).

## Règles métier (couche Application)

- **`ConfigureOrgHierarchy`** — création / déplacement / désactivation d'unités.
  - Détection de **cycle** avant écriture (CA-6) : remontée de l'ascendance du parent visé,
    avec garde de profondeur (`MAX_DEPTH`).
  - **Pas de suppression** : la désactivation (RG-REF-1) conserve l'unité dans l'historique.
- **`AttachCollaborator`** — rattachement à une unité **active**, sans **chevauchement** de
  périodes pour un même collaborateur (`EffectivePeriod::overlaps`).
- Habilitation `MANAGE_ORGANIZATION` (rôle Administrateur) vérifiée **côté serveur** (ARC-19),
  jamais déléguée à l'UI (ARC-106). Chaque opération est tracée (journal sécurité, HAB-6).

## API (DTO strict, ADR-4)

| Opération | Effet |
|-----------|-------|
| `GET /api/org-units` | Liste des unités du tenant (admin). |
| `POST /api/org-units` | Création d'une unité. |
| `DELETE /api/org-units/{id}` | **Désactivation** (jamais de suppression dure). |
| `GET /api/org-memberships?userId=…` | Timeline des rattachements d'un collaborateur. |
| `POST /api/org-memberships` | Rattachement d'un collaborateur. |

Refus d'habilitation → **403** (`AccessDeniedException`), erreur métier → **422**
(`OrganizationException`), sans exposer de trace. Écran d'administration : `/organisation`.

## Sécurité des données (RLS)

Les tables `org_level_config`, `org_unit`, `org_membership` naissent avec Row-Level Security
(`ENABLE` + `FORCE` + policy `tenant_isolation`), amorçant l'action transverse DBT-SEC-1.

> **Note de policy** : la comparaison utilise `tenant_id::text = current_setting('app.current_tenant', true)`
> (et non `::uuid`). Sans contexte de tenant, `current_setting(..., true)` peut renvoyer une chaîne
> vide ; le cast `''::uuid` lèverait une erreur au lieu de masquer les lignes. La comparaison en
> texte échoue proprement (0 ligne). Le test d'intrusion `OrgRlsRuntimeTest` le vérifie sous rôle
> non-superutilisateur. *Les migrations socle antérieures (protected_record, faits analytiques)
> utilisent encore le cast `::uuid` : à harmoniser hors périmètre US-010.*

## Revue (T-010-08) — findings traités

Revues croisées `security-auditor` (OWASP 2025) et `symfony-reviewer` : **aucun finding
Critique/Élevé**, défense-en-profondeur confirmée. Corrections appliquées :

- **Existence tenant-locale du collaborateur** (MOYEN) : `AttachCollaborator` valide `userId`
  via `UserRepository::existsInTenant` (deny by default) → 422 si inconnu.
- **Validation des identifiants** (FAIBLE) : format UUID vérifié avant toute requête (évite un
  500 sur id mal formé) ; longueur des noms bornée à 255 dans le domaine → 422.
- **Habilitation de l'item provider** (FAIBLE) : `OrgUnitItemProvider` vérifie `MANAGE_ORGANIZATION`
  (le 403 précède le 404 sur `DELETE`).
- *Rejeté* : suggestion `equals()` en `===` — incorrecte pour des `DateTimeImmutable` (`===`
  compare l'identité d'instance, pas la valeur) ; `==` est le bon choix.

## Limites connues / suite

- **RLS `WITH CHECK` explicite** (INFO) : le `USING` sert déjà de contrôle à l'écriture ;
  ajouter un `WITH CHECK` explicite reste un durcissement lisible — à harmoniser avec le socle.
- **CSRF / CORS** (FAIBLE, hors périmètre) : confirmer `SameSite`/`Secure` du cookie de session
  et la config CORS pour les mutations cookie-authentifiées.
- **i18n** des messages d'exception (codes d'erreur structurés) — cohérent avec l'existant, à
  traiter globalement.
- **Index `(tenant_id, name)`** sur `org_unit` si les hiérarchies deviennent volumineuses.
- Le libellé de niveau (`OrgLevelConfig`) n'est pas encore exposé par l'API/l'écran (édition des
  niveaux à compléter selon les besoins).
