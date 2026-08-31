# ADR-0017 — Les migrations Doctrine sont la source du schéma de production

- **Statut :** Adopté (2026-08-31)
- **Réf. CDC :** précise ADR-0006 (isolation RLS), ADR-0009/`ARC-104` (invariants garantis en base)
- **Sprint :** 2 (TECH-1) — dette identifiée en rétrospective Sprint 1

## Contexte

Au Walking Skeleton (Sprint 1), le schéma était construit en test par `SchemaTool` et le durcissement analytique (RLS + trigger anti-écriture) appliqué à la volée par `AnalyticsSchemaHardener` (commande d'ops + tests). Sans chaîne de migration versionnée, le schéma de production n'était ni reproductible ni traçable, et le durcissement risquait de diverger entre environnements. La rétrospective Sprint 1 a fait de la correction de cette dette la première action.

## Décision

1. **Les migrations Doctrine (`doctrine/doctrine-migrations-bundle`) sont l'unique source du schéma de production et de staging.** Plus aucune construction de schéma par `SchemaTool` hors tests.
2. **Le durcissement de sécurité en base est versionné** : les politiques RLS (`ENABLE`+`FORCE`+`tenant_isolation`) et le trigger anti-écriture directe des faits sont portés par des migrations idempotentes, au même titre que les tables.
3. **Les tests d'intégration conservent `SchemaTool`** pour l'isolation et la rapidité (création/suppression ciblée par test). La cohérence migrations ↔ mapping est garantie séparément.
4. **La CI vérifie l'absence de dérive** : une étape dédiée applique toutes les migrations sur une base jetable puis exécute `doctrine:schema:validate` (mapping ORM en phase avec le schéma migré). Toute divergence rend le build rouge.

## Conséquences

### Positives
- Schéma reproductible et auditable (chaque changement = une migration relue).
- Durcissement RLS/trigger tracé et rejouable, identique en dev/CI/staging/prod.
- `schema:validate` en CI empêche la dérive entre le mapping et les migrations.

### Négatives / points de vigilance
- **Double source du DDL de durcissement** : `AnalyticsSchemaHardener` (tests + commande) et la migration `Version20260831161807` portent le même SQL. Acceptable au Walking Skeleton (les deux sont testés) ; à converger si le DDL évolue (faire appeler le hardener par la migration, ou retirer le hardener une fois la RLS runtime généralisée — TECH-2).
- Les migrations ne gèrent pas nativement RLS/policies/trigger : elles sont écrites en SQL brut, hors du diff automatique. Elles doivent être relues à la main (`ARC-106`).

## Migrations concernées

| Migration | Objet |
|-----------|-------|
| `Version20260831120000` | Socle US-001 : `tenant`, `protected_record`, pgvector, RLS sur `protected_record` |
| `Version20260831161806` | Schéma US-002/003/005 : `app_user`, `auth_role`, `event_stream`, `dim_period`, `fact_project_revenue` |
| `Version20260831161807` | Durcissement analytique : RLS (FORCE) + trigger anti-écriture directe des faits |

## Suite

- **TECH-2** étend la RLS au runtime (rôle applicatif non-superutilisateur, `SET app.current_tenant` par requête) et couvre toutes les tables `TenantOwned`.
