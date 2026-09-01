# Module Clôture de période (US-057)

Verrouillage des saisies après clôture d'une période comptable, avec **réouverture formelle
tracée** et **traçabilité** des modifications. Garantit l'intégrité des données historiques
(`INV-7`, `RG-TMP-6`, `EF-TMP-22`, `EF-TMP-23`) et alimente le verrou de recalcul de la
valorisation (US-060).

## Flux de bout en bout

```
Administration                          Saisie / API
     │ ClosePeriod (MANAGE_PERIODS)          │ RecordTimeEntry
     ▼                                       ▼
 AccountingPeriod.close() ──► PeriodClosed ──(async)──► calculs aval (valorisation)
     │  (statut CLOSED)                              (TriggerDownstreamOnPeriodClosed)
     │
     ▼  le mois est verrouillé (INV-7)
 PeriodModificationGuard.ensureModifiable() ─► 423 PeriodLockedException
     ▲                                             (sauf réouverture active)
     │
 RequestReopening (REQUEST_PERIOD_REOPENING) ─► ApproveReopening (MANAGE_PERIODS)
     └─────────── fenêtre de 48 h ───────────► lève le verrou, puis reclôture passive
```

## Modèle de domaine (`src/Domain/Period/`)

| Élément | Rôle |
|---------|------|
| `AccountingPeriod` | Mois `YYYY-MM` par tenant + statut (`open`/`closing`/`closed`), auteur & horodatage de clôture. `close()` idempotent. Unicité (tenant, période). |
| `PeriodStatus` | `open` / `closing` / `closed`. |
| `ReopeningRequest` | Demande de réouverture : demandeur, motif, statut, approbateur, `validUntil`. `isActiveAt(now)` = approuvée et non expirée. |
| `ReopeningStatus` | `requested` / `approved` / `rejected`. |
| `PeriodException` → 422 · `PeriodLockedException` → **423** | Erreurs métier (listener). |
| `Shared\CalendarMonth::bounds()` | Bornes de mois semi-ouvertes `[from, to)` — partagé avec la valorisation. |

Toutes les entités sont `TenantOwned`.

## Règles métier (couche Application)

- **`ClosePeriod`** (CA-1/CA-3) : `MANAGE_PERIODS` (403) ; refus si imputations non finalisées sauf
  `force` (422, « clôturer malgré tout », tracé) ; clôture idempotente ; journal `periode_cloturee` ;
  publication `PeriodClosed` (async).
- **`PeriodModificationGuard`** (CA-4, INV-7) : appelé par `RecordTimeEntry` avant toute écriture —
  un mois clôturé **sans réouverture active** → `PeriodLockedException` (423), tentative tracée
  `tentative_modification_periode_cloturee`. Le verrou **dérive du statut de la période** (pas de 4ᵉ
  statut sur l'imputation).
- **`RequestReopening`** (CA-5) : `REQUEST_PERIOD_REOPENING` (403) ; motif obligatoire ; période
  effectivement clôturée requise ; tracé `reouverture_demandee`.
- **`ApproveReopening`** (CA-2) : `MANAGE_PERIODS` (403) ; ouvre une fenêtre de **48 h** (`validUntil`) ;
  tracé `reouverture_approuvee`. Après expiration, la période est de nouveau verrouillée (reclôture
  automatique **passive** — le statut reste `closed`, la fenêtre expire).
- **`TriggerDownstreamOnPeriodClosed`** (CA-1) : handler async de `PeriodClosed` ; ré-émet la
  validation des imputations validées du mois → (re)déclenche leur valorisation. Tenant-aware.

## Verrouillage à deux niveaux (INV-7, défense en profondeur)

1. **Applicatif** : `PeriodModificationGuard` dans `RecordTimeEntry` → 423.
2. **Base de données** : trigger PostgreSQL `trg_time_entry_period_lock` (migration
   `Version20260901210000`) refuse tout **UPDATE/DELETE** d'une imputation dont le mois est clôturé
   (`accounting_period.status = 'closed'` pour le même tenant). Belt-and-suspenders même hors
   chemin applicatif.

## Raccord avec la valorisation (US-060)

`DoctrinePeriodClosure` implémente le port `PeriodClosureStatus` d'US-060 en lisant
`AccountingPeriod` **par tenant** : il **remplace le stub** `ConfiguredPeriodClosure` (action rétro
S4). Le `423` de `POST /api/valorisation/recompute` s'appuie désormais sur la clôture réelle.

## API & Web

| Opération | Effet |
|-----------|-------|
| `GET /administration/periodes` | Liste des périodes (statut code couleur), `MANAGE_PERIODS`. |
| `POST /administration/periodes/cloturer` | Clôture via **confirmation** (ressaisie du code) + **CSRF** ; option `force`. |
| `POST /api/time-entries` (US-050) | Sur période clôturée → **423** (via le garde). |

Réponses d'erreur : **403** (habilitation), **422** (`PeriodException`), **423**
(`PeriodLockedException`) — via `PeriodExceptionListener`, sans trace.

## Sécurité des données (RLS)

Les tables `accounting_period` et `period_reopening_request` naissent avec Row-Level Security
(`ENABLE` + `FORCE` + policy `tenant_isolation` en comparaison texte). Le trigger lit
`accounting_period` sous la RLS du tenant courant (cohérent avec le contexte de la requête / du worker).

## Tests

- `AccountingPeriodTest`, `ClosePeriodTest` (403/CA-3/idempotent), `ReopeningWorkflowTest`
  (403, fenêtre, verrou levé/re-verrouillé), `TriggerDownstreamOnPeriodClosedTest`.
- `PeriodAdminTest` (écran 401/403/200, clôture via confirmation), `PeriodLockApiTest` (**423**
  fonctionnel sur saisie en période clôturée, 201 sur période ouverte).
- `RecordTimeEntryTest::testRefusesRecordingOnAClosedPeriod`.

## Limites connues / suite

- **Journal immuable avant/après** (EF-TMP-23, table dédiée `timesheet_audit_log`, rétention 7 ans) :
  non encore implémenté — les événements sont tracés via le canal d'audit sécurité (Monolog) en
  attendant. À matérialiser en table INSERT-only.
- **UI de réouverture** (demande/approbation) : les use cases existent ; l'écran dédié reste à câbler.
- **48 h « ouvrées »** : la fenêtre est en heures calendaires (raffinement « ouvrées » ultérieur).
- **Reclôture** : passive (expiration de la fenêtre) — aucun statut `closing` transitoire matérialisé.

## Revue (T-057-09) — findings traités

Revues croisées `security-auditor` (OWASP 2025) et `symfony-reviewer`. Chaîne d'habilitation,
isolation tenant et défense en profondeur jugées solides ; corrections appliquées :

- **[Moyen — corrigé] Trigger « move-in »** : le trigger DB ne testait que `OLD.work_date` ; un
  UPDATE déplaçant une imputation d'un mois ouvert vers un mois clôturé passait la barrière. Il teste
  désormais `OLD` **et** `NEW.work_date` (migration `Version20260901230000`). L'appli ne déplace
  jamais une date (grain immuable), mais la défense en profondeur DB est maintenant complète.
- **[Moyen — corrigé] Séparation des tâches (4-eyes)** : `ApproveReopening` refuse désormais qu'un
  demandeur approuve sa propre réouverture (aligné sur CA-2 : Marc demande, Admin approuve), tentative
  tracée `reouverture_auto_approbation_refusee`.
- **[Faible — corrigé] Demandes redondantes** : `RequestReopening` refuse une nouvelle demande quand
  une réouverture est déjà **active** (anti « approval fatigue »).
- **[Faible — accepté/documenté] Trigger `SECURITY INVOKER`** : mitigé — `time_entry` est aussi en
  `FORCE RLS` (même policy) et le trigger filtre `ap.tenant_id = OLD.tenant_id` ; atteindre le trigger
  suppose déjà un contexte tenant correct. Le passage en `SECURITY DEFINER` (avec ses propres risques)
  est écarté au profit de l'invariant « contexte tenant posé » documenté ici.

Revue Symfony (82/100, « très bon ») :

- **[Élevé — corrigé] Accessibilité Twig** : les emojis de statut portent désormais `aria-hidden`
  (le libellé texte adjacent est lu par le lecteur d'écran).
- **[Élevé/Moyen — corrigé] Validation & DRY du format `YYYY-MM`** : centralisés dans
  `Shared\CalendarMonth` (`isValid()` + `bounds()` qui valide) ; `AccountingPeriod` et `ClosePeriod`
  y délèguent (plus de regex dupliquée).
- **[Moyen — documenté] `isActiveAt()` vs filtre SQL** : le double InMemory s'appuie sur le prédicat
  métier ; l'adapter Doctrine le reflète en SQL (perf) — miroir volontaire, commenté.
- **[Élevé — écarté, avec justification] « Attributs ORM sur paramètres de constructeur »** : c'est
  l'**idiome établi du projet** (toutes les entités : `TimeEntry`, `TimeEntryValuation`, `ProfileRate`…),
  **imposé par Rector** ; PHPStan max et Doctrine l'acceptent. « Corriger » romprait la cohérence et
  serait redéfait par Rector. Conservé.

Findings de style **écartés avec justification** : migration DQL→QueryBuilder (le projet utilise le
DQL en chaîne partout — cohérence prime), nommage `PeriodModificationGuard` (déjà explicite), mapping
listener pour d'hypothétiques futures sous-classes (YAGNI — l'`instanceof` couvre déjà tout héritier).

**Conclusion des revues : GO — prêt pour production** — habilitation, isolation tenant, CSRF et double
barrière conformes ; tous les écarts Moyen/Faible sont corrigés, documentés ou arbitrés.
