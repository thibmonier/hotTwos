# Tâches Techniques Transverses — Sprint 7

## Dette technique (action rétro S4)

### T-TECH-01 : `sprintf → set_config('app.current_tenant', ?, ...)` avec paramètre lié
- **Type** : [BE]
- **Estimation** : 3h
- **Dépend de** : —

**Contexte** : action de rétrospective S4 (suivi [Faible]). Le contexte tenant (RLS) est aujourd'hui posé
via `sprintf` dans 3 sites, au lieu d'un paramètre lié `set_config('app.current_tenant', ?, ...)`.

**Fichiers** :
- `DoctrineAnalyticsProjector`
- `TenantSessionConfigurator`
- `TenantContextMiddleware`

**Critères** :
- [ ] Même motif (paramètre lié) sur les 3 sites
- [ ] Tests d'intrusion RLS existants toujours verts (`*RlsRuntimeTest`, `ValuationWorkerRlsTest`)
- [ ] `make ci` vert

> À traiter **en amont** du gros du reskin (hygiène, indépendant du design).

## Assets / performance

### T-TECH-02 : Budget d'assets — poids du CSS Bootstrap/Skote
- **Type** : [OPS]
- **Estimation** : 2h
- **Dépend de** : T-061-02

**Critères** :
- [ ] Poids CSS mesuré (avant/après intégration)
- [ ] Sous-ensemble / purge si nécessaire (cf. ADR-0018 — point de vigilance budget assets)
- [ ] Pas de dégradation notable du temps de chargement (NFR EPIC-012)
