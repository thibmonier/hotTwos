# Module Valorisation (US-060)

Valorisation **automatique et figée** des temps validés (≤ 15 min), déclenchée **par événement**
(jamais par appel direct) et traitée de façon **asynchrone**. Produit le coût et le chiffre
d'affaires réels par imputation, alimente le modèle analytique (`fact_project_revenue`) et garantit
l'intégrité historique face aux révisions tarifaires (INV-2/INV-3, `EF-TMP-29`, `ENF-PERF-5`).

## Flux de bout en bout

```
ValidateTimeEntries (US-055)                        DefineProfileRate (US-011)
        │ publie                                            │ publie
        ▼                                                   ▼
  TimeEntriesValidated ──(async)──┐             ProfileRateDefined ──(async)──┐
                                  ▼                                            ▼
                     ValueValidatedTimeHandler            RevalueOnRateDefinedHandler
                     · résout profil + tarif (RateResolver)   · retrouve les `missing_rate`
                     · fige le snapshot → TimeEntryValuation   · ré-émet TimeEntriesValidated
                     · append RevenueRecognized (si valued)      (re-déclenchement CA-4)
                                  │
                                  ▼
                          EventStore (append-only)
                                  │ rebuild (RecognizedRevenue)
                                  ▼
                        fact_project_revenue (jamais écrit directement — ADR-9)
```

Recalcul manuel : `POST /api/valorisation/recompute?period=YYYY-MM` → `RecomputeValuation`
(re-émet la validation des imputations validées du mois, sauf période clôturée → **423**).

## Modèle de domaine

| Élément | Rôle |
|---------|------|
| `Domain\Valuation\TimeEntryValuation` | Valorisation **figée** d'une imputation : coût/revenu + **snapshot** du taux (coût, vente, date d'effet) copiés à la validation. Entité **immuable** (factories `valued` / `missingRate`, aucun mutateur). Unicité (tenant, imputation). |
| `Domain\Valuation\ValuationStatus` | `valued` / `missing_rate` (taux absent → valorisation partielle, CA-4). |
| `Domain\Valuation\TimeValuationCalculator` | `entryCents(tauxJournalier, minutes)` — pro-rata en **arithmétique entière** (420 min = 1 j). |
| `Domain\Valuation\PeriodClosureStatus` | Port du statut de clôture d'une période (CA-5) — préfigure US-057. |
| `Domain\Analytics\RevenueRecognized` | Événement de CA reconnu, porteur d'une **imputation source** (`source_time_entry_id`). |
| `Domain\Analytics\RecognizedRevenue` | **Réducteur unique** du flux → CA par (période, projet) ; sémantique « dernière reconnaissance par imputation gagne ». |

Toutes les entités sont `TenantOwned`. Montants **toujours en centimes entiers** (INV-2).

## Snapshot figé (INV-2 / INV-3) — invariant non négociable

Le taux appliqué est **copié** dans `TimeEntryValuation` (`snapshotCostRateCents`,
`snapshotSellingRateCents`, `snapshotRateDate`), pas seulement lu dans la table des taux. Une
révision tarifaire ultérieure **ne réécrit jamais** une valorisation passée : seules les imputations
`missing_rate` sont re-valorisées (CA-4), les `valued` restent intactes — **aucun recalcul
rétroactif** (couvert par `FrozenSnapshotTest`, CA-2).

## Reconnaissance du CA & non-divergence (ADR-9 / ARC-113)

La valorisation `valued` produit un `RevenueRecognized` réel appended à l'`EventStore` ; le
`DoctrineAnalyticsProjector` en dérive `fact_project_revenue` par **clear + replay**. Le projecteur
**et** le `SqlDivergenceChecker` consomment le **même** réducteur `RecognizedRevenue` → la
non-divergence est garantie *par construction*. Le `source_time_entry_id` permet le **supersede** :
une re-valorisation d'une imputation **remplace** sa reconnaissance précédente (pas de double
comptage) ; les reconnaissances sonde (US-005, sans source) restent additionnées.

## Traitement asynchrone (ENF-PERF-5, ADR-0007)

Couplage **par événement** via Symfony Messenger (transport Doctrine, `ADR-0007`). Les messages
sont **`TenantAwareMessage`** : le `TenantContextMiddleware` pose le tenant depuis le message puis
l'efface autour du handler (parité worker FrankenPHP, ARC-47/RSQ-15). Cible : ≥ 67 imputations/s
pour tenir 1 000 imputations ≤ 15 min.

> ⚠️ **Tout message `TenantAwareMessage` doit être routé en `async`** (`messenger.yaml`). Traité de
> façon *synchrone* dans une requête HTTP, le middleware effacerait le contexte tenant de la requête
> en cours → 500. `TimeEntriesValidated` et `ProfileRateDefined` sont donc routés async.

## Règles métier (couche Application)

- **`ValueValidatedTimeHandler`** : pour chaque imputation validée, résout le profil du
  collaborateur (à `workDate`) puis le tarif (`RateResolver`, ARC-6), fige le snapshot ; sans
  profil ou sans tarif → `missing_rate` (aucun CA reconnu).
- **`RevalueOnRateDefinedHandler`** (CA-4) : à la définition d'un tarif, ré-émet la validation des
  imputations `missing_rate` du tenant → re-valorisation avec le tarif désormais disponible.
- **`RecomputeValuation`** (CA-5) : recalcul manuel d'une période — habilitation
  `RECOMPUTE_VALUATION` (403), **verrou de période clôturée** (`PeriodClosedException` → 423),
  sinon re-émet la validation des imputations **validées** du mois + trace d'audit
  `valuation_recomputed` (HAB-6).

## API (DTO strict, ADR-4)

| Opération | Effet |
|-----------|-------|
| `POST /api/valorisation/recompute?period=YYYY-MM` | Recalcul manuel d'une période (rôle habilité). |

Réponses : **401** (anonyme, via `access_control ^/api/valorisation`), **403** (habilitation
manquante), **422** (`ValuationException` — période invalide), **423** (`PeriodClosedException` —
période clôturée), **200** (`{period, recomputed}`). Erreurs traduites par `ValuationExceptionListener`,
sans trace.

## Clôture de période (CA-5) — stub en attendant US-057

Le port `PeriodClosureStatus` est implémenté par `ConfiguredPeriodClosure`, piloté par le paramètre
`valuation.closed_periods` (mois `YYYY-MM`, vide par défaut). **Temporaire** : US-057 introduira une
clôture **par tenant** persistée en base. Le stub est volontairement tenant-agnostique.

## Sécurité des données (RLS)

La table `time_entry_valuation` naît avec Row-Level Security (`ENABLE` + `FORCE` + policy
`tenant_isolation` en comparaison **texte** `tenant_id::text = current_setting('app.current_tenant', true)`,
robuste au contexte absent — migration `Version20260901170000`). Le CA figure dans
`fact_project_revenue` (également RLS + trigger anti-écriture directe — US-005).

## Tests

- `FrozenSnapshotTest` — snapshot figé + aucun recalcul rétroactif (CA-2) + boucle CA-4.
- `ValuedRevenueNonDivergenceTest` — supersede + non-divergence de l'indicateur valorisé (ARC-113).
- `ValuationThroughputTest` — smoke de débit 1 000 imputations (ENF-PERF-5 ; charge réelle sur staging).
- `ValuationRecomputeApiTest` — fonctionnel recompute (401/403/422/423/200).
- `WorkerTenantSessionTest` / `ValuationWorkerRlsTest` — parité RLS HTTP/worker (SET/RESET du
  contexte + intrusion RLS via le chemin de consommation, rôle non-superutilisateur).
- `ValueValidatedTimeHandlerTest`, `RevalueOnRateDefinedHandlerTest`, `RecomputeValuationTest`,
  `RecognizedRevenueTest`, `RevenueRecognizedTest`.

## Limites connues / suite

- **Coût/marge dans l'étoile** : le fait ne porte aujourd'hui que le CA (`amount_cents`) ; le coût
  est porté par `TimeEntryValuation`. Un fait coût (ou des colonnes) est une extension à arbitrer.
- **Dashboard financier** (T-060-06) : fraîcheur, progression, audit trail du taux, bandeau alerte.
- **US-057** : remplacer le stub `ConfiguredPeriodClosure` par une clôture par tenant + réouverture
  formelle tracée.
- **Colonnes de montants** en `INT` (32 bits) : migration `BIGINT` si des montants > ~21 M€ (borne
  centimes) deviennent plausibles à l'échelle d'un projet.
- **Optimisations de débit** (si les volumes le justifient, mesurer avant) : `save()` fait
  `persist` + `flush` par imputation (pattern cohérent du projet) — un `flush` unique par lot
  réduirait les transactions, mais sans faire dépendre le handler applicatif de l'`EntityManager`
  (frontière hexagonale) ; et `RevalueOnRateDefinedHandler` re-valorise **toutes** les `missing_rate`
  du tenant (une valorisation `missing_rate` ne porte pas de `profileId`, donc pas de filtrage
  direct par profil) — acceptable car état exceptionnel, à cibler si les gaps deviennent nombreux.

## Revue (T-060-08) — findings traités

Revues croisées `security-auditor` (OWASP 2025) et `symfony-reviewer`.

**[Élevé — corrigé] Barrière RLS absente dans le chemin worker asynchrone** (A01, defense-in-depth).
La variable de session PostgreSQL `app.current_tenant` — pivot de la RLS — n'était posée que par
`TenantSessionConfigurator` (listener `RequestEvent`, HTTP). Or l'écriture réelle des valorisations
a lieu dans le **worker Messenger**, où aucun `RequestEvent` ne se déclenche : sous rôle
non-superutilisateur, l'`INSERT` aurait été rejeté (rupture silencieuse), ou vulnérable à une
écriture cross-tenant sur une connexion résiduelle. Les tests le masquaient (transport `in-memory`
consommé dans le contexte de la requête). **Correctif** : `TenantContextMiddleware` pose désormais
`SET app.current_tenant` sur la connexion à la consommation et `RESET` en `finally` (parité HTTP) ;
couvert par `WorkerTenantSessionTest` (le contexte est bien posé/relâché) **et**
`ValuationWorkerRlsTest` (intrusion RLS sous rôle non-superutilisateur : l'écriture du worker n'est
acceptée qu'avec le contexte, rejetée sans — `WITH CHECK`).

**[Faible — accepté] Interpolation `sprintf` du tenant dans `SET`** (`DoctrineAnalyticsProjector`,
`TenantContextMiddleware`) : non exploitable — `TenantId` valide un UUID strict, et `SET` n'accepte
pas de paramètre lié (même motif que `TenantSessionConfigurator`). Conservé, documenté.

**[Moyen — corrigé] Paramètre `period` du recompute** : source unique = query string (le repli sur
le corps, spéculatif, est supprimé — CA-5 spécifie `?period=`).

**[Faible — corrigé] Stub de clôture tenant-agnostique** : commentaire `TODO US-057` explicite.

**[Moyen — accepté, documenté]** : `save()` fait `flush` par imputation (pattern cohérent du
projet ; budget ENF-PERF-5 tenu) et `RevalueOnRateDefinedHandler` re-valorise toutes les
`missing_rate` du tenant (une valorisation `missing_rate` ne porte pas de `profileId`) — optimisations
de débit différées (voir *Limites connues*). Conception (hexagonale, Messenger, API Platform,
Doctrine) jugée **production-ready** par les deux revues.
