# Tâches techniques transverses — Sprint 005

## Dette / hardening (issu de la rétro Sprint 4)

### T-TECH-03 : Lot de hardening `sprintf` → `set_config` (paramètre lié)
- **Type** : [BE] · **Estimation** : 3h · **Priorité** : Moyenne · **Dépend de** : —
- **Raison** : action rétro S4 (finding sécurité [Faible]). Éliminer l'interpolation de chaîne du tenant dans les `SET app.current_tenant`.
- **Portée** (3 sites) :
  - `src/Infrastructure/Messaging/TenantContextMiddleware.php`
  - `src/Infrastructure/Tenant/TenantSessionConfigurator.php`
  - `src/Infrastructure/Analytics/DoctrineAnalyticsProjector.php`
- **Cible** : `SELECT set_config('app.current_tenant', ?, <is_local>)` avec paramètre lié (`false` pour le HTTP/worker session-level, `true` pour le `SET LOCAL` du projecteur).
- **DoD** : `grep -rn "SET app.current_tenant = '"` sur `src/` ne renvoie plus rien ; `TenantSessionConfiguratorTest`/`TenantContextMiddlewareTest` adaptés ; `WorkerTenantSessionTest` + RLS runtime verts ; `make ci` vert.
- **Ordre** : à traiter **avant US-057** (qui touche la clôture et de nouveaux handlers), pour partir d'un socle tenant-session propre.

## Fixtures / démo

### T-TECH-04 : Fixtures de démonstration EPIC-003 (optionnel — 🟢 Could)
- **Type** : [OPS] · **Estimation** : 2h · **Priorité** : Basse · **Dépend de** : entités US-054/057
- **Raison** : reliquat des réserves S1-S3 (jeux de données démo). Un tenant de démo avec projets, imputations, absences, une période clôturée et des retards, pour la Review et le smoke staging.
- **DoD** : commande/fixture idempotente, isolée par tenant démo, non exécutée en prod.

## Rappel process (DoD sprint — pas une tâche)

- **[Action rétro S4]** tout nouveau `#[AsMessageHandler]` écrivant en base (T-057-06 `PeriodClosed`, T-056-03 envoi de relances, notifications T-054-02) **doit** avoir un test d'intrusion **RLS via consume** (rôle NOSUPERUSER, pattern `ValuationWorkerRlsTest`). Porté dans les tâches `[TEST]` des US concernées.
