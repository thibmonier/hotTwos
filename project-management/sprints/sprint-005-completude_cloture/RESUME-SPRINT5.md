# Note de reprise — Sprint 5 (après /clear)

> Handoff pour continuer le Sprint 5 « Complétude et clôture du cycle temps ». Lire aussi
> `task-board.md`, `tasks/README.md` et les docs modules `docs/modules/{period,absence,completeness}.md`.

## Où on en est

- **Branche** : `feature/sprint-5-planning`. **PR draft #7** ouverte (WIP). Base `main` (Sprint 4
  mergé PR #6, Railway à jour). Tout est commité et **vert** : `make ci` = **318 tests**, PHPStan
  max 0, Deptrac 0, cs/rector OK, gitleaks OK. `schema:validate` OK.
- **Avancement : 27/44 tâches (61 %)** — voir `task-board.md`.
  - ✅ **T-TECH-03** (hardening `set_config`).
  - ✅ **US-057 Clôture de période (9/9)** — `AccountingPeriod` + RLS, `ClosePeriod`, verrou **423**
    (garde `PeriodModificationGuard` + trigger DB), réouverture formelle (4-eyes), handler
    `PeriodClosed`, écran `/administration/periodes`. **Remplace le stub de clôture d'US-060**
    (`DoctrinePeriodClosure`). Doc `docs/modules/period.md`. Revues GO.
  - ✅ **US-054 Absences (8/8)** — entités demi-journée + RLS, `DeclareAbsence`/`DecideAbsence`
    (self-approval interdit), compteurs `AbsenceBalance`, blocage RG-TMP-3 dans `RecordTimeEntry`
    (422), API `/api/absences`, écran `/absences`, **gate RGPD/HAB-3** (`AbsenceRgpdComplianceTest`).
    Doc `docs/modules/absence.md`. Revues GO.
  - ✅ **US-058 Complétude (6/6)** — `CompletenessGrid` (4 états, absences déduites, J+2),
    `CompletenessScope` (403 équipe), API `/api/completude` + export CSV anti-injection, écran
    `/completude`. Doc `docs/modules/completeness.md`. Revue GO.

## Prochaine tâche : US-056 (relances automatiques de retard) — 7 tâches

Voir `tasks/US-056-tasks.md` (décomposition détaillée). Ordre : T-056-01 → 07.
- **T-056-01** : entités `ReminderRule` (tenant : délai, fréquence, canal, escalade, actif) +
  `ReminderLog` + préférence collaborateur `reminder_opt_out` + migration **RLS**.
- **T-056-02** : moteur `ScheduleReminders` (détecte les retards via la complétude US-058, borne de
  fréquence — plancher 1 j ouvré **hardcodé**, escalade N+1 à la 3ᵉ relance, arrêt à la soumission,
  respect opt-out + désactivation globale tenant).
- **T-056-03** : commande CLI `app:reminders:run` (cron) qui **dispatch** un message **tenant-aware
  async** + handler d'envoi (in-app/email) → **test d'intrusion RLS via consume** (action rétro S4).
- **T-056-04** : API config règles + opt-out individuel (non forçable par l'admin).
- **T-056-05** : écran config + prévisualisation ; bandeau discret dans `/saisie` si opt-out + retard.
- **T-056-06** : tests (borne, annulation, escalade, opt-out) + RLS-via-consume.
- **T-056-07** : doc + revue (opt-out RGPD).

Puis **US-052** (saisie mobile responsive — web, pas de Flutter) et **US-059** (synthèse activité,
planning dégradé car US-037 absente) — parallélisables (surtout FE).

## Pièges & conventions (NE PAS re-découvrir)

1. **TOUT en Docker** : `make test|ci|analyse|deptrac|cs-fix|rector-fix|migrate|composer|console`
   via `docker compose run --rm`. Le hook `.githooks/pre-commit` (dockerisé) tourne `make ci` +
   gitleaks à chaque commit (~30-40 s). **Ordre après avoir codé** : `make cs-fix` → `make rector-fix`
   → `make cs-fix` → `make ci`.
2. **PHPStan max** : casts `mixed`→string/int interdits (`is_string`/`is_numeric` d'abord) ;
   après un `assertSame(x, $obj?->m())`, un 2ᵉ `?->` déclenche `nullsafe.neverNull` → utiliser
   `assertNotNull($obj)` puis `->`. Un double de test sans expectations = `createStub`, pas
   `createMock` (notice PHPUnit 13). Sur une **entité** mappée, ne pas mettre `@param non-empty-string`
   (mismatch `doctrine.columnType`) → `string` + validation runtime.
3. **Migrations** : `doctrine:migrations:diff` puis réécrire (renommer `Version20260901XXXXXX`
   chronologique, retirer le bruit Messenger, **ajouter la RLS** `ENABLE`+`FORCE`+policy **texte**
   `tenant_id::text = current_setting('app.current_tenant', true)`). Finir par `schema:validate`.
   Toute nouvelle table a la RLS + un test d'intrusion `*RlsRuntimeTest` (rôle NOSUPERUSER).
4. **Messages async tenant-aware** : router en `async` dans `messenger.yaml` (jamais sync — le
   `TenantContextMiddleware` effacerait le contexte de la requête → 500). Le middleware pose
   maintenant aussi `SET app.current_tenant` (RLS worker). **Tout handler async écrivant en base ⇒
   test d'intrusion RLS via consume** (`ValuationWorkerRlsTest`), DoD action rétro S4.
5. **Garde ⇒ schéma des tests fonctionnels** : quand un use case (ex. `RecordTimeEntry`) interroge une
   nouvelle table (période, absence…), **tous** les tests fonctionnels de saisie qui SchemaTool-isent
   `TimeEntry` doivent ajouter cette entité à leur `$schema` (sinon 500 « table absente »).
6. **Tests fonctionnels** : le `KernelBrowser` reboote le kernel par requête → override de service
   après `$client->disableReboot()` + id concret. JSON échappe l'UTF-8 → décoder avant
   `assertStringContainsString`. Login : `POST /api/login` (session cookie). Écrans : nouvelle route
   → règle `access_control` (`^/...` ROLE_USER).
7. **Conventions** : entités mappées par attributs sur params de ctor (idiome **imposé par Rector** —
   ne pas « corriger ») ; VO/exceptions en Domain ; use cases `final readonly` (`Authorizer::ensureCan`
   + `SecurityAuditLogger`) ; ports Domain + adapters Doctrine (DQL en chaîne, tenant explicite) +
   doubles `tests/Support/` ; API Platform DTO strict OU contrôleur dédié pour les lectures calculées ;
   exceptions métier → listener → 422/423 ; AccessDenied → 403. Helpers partagés : `Shared\CalendarMonth`,
   `Shared\EffectivePeriod`. Permissions : enum `Permission` + `DefaultRoleMatrix`.
8. **Revues** : lancer `security-auditor` + `symfony-reviewer` en parallèle (agents), scope resserré,
   conclusion directe. **Leurs longs messages sont tronqués** → demander la suite via SendMessage.
   Esprit critique sur les findings (ex. rejeter « attributs ORM sur params » = idiome projet ;
   « DQL→QueryBuilder » = cohérence codebase ; N+1 dashboard = accepté phase 1). Appliquer :
   self-approval/4-eyes, RGPD (avertissement + longueurs bornées), index composites, a11y Twig
   (emoji `aria-hidden`), validation centralisée.

## Consigne PO (IMPORTANTE — rétro S4 Action 4)

**Une phase de conception UX/UI doit précéder l'implémentation des écrans à chaque sprint** :
maquettes/prototypes validés avant le dev FE, design system, revue d'accessibilité (WCAG). Les
écrans déjà livrés (`/saisie`, `/profils`, `/valorisation`, `/absences`, `/administration/periodes`,
`/completude`) sont à **reprendre** dans cette démarche. Agents dispo : `ui-designer`, `ux-ergonome`,
`accessibility-expert`.

## Dette / suivi (non bloquant)

- Journal immuable avant/après des modifs post-réouverture (EF-TMP-23, table 7 ans) — US-057.
- Scope N+1 réel du décideur d'absence + « équipe » de complétude (via hiérarchie US-010).
- Perf complétude : batch queries + cache 15 min (phase 2).
- Notifications (absences, relances) : handler de livraison effective email/in-app.

## Commande pour reprendre

`make ci` doit être vert au départ. Continuer par **US-056** (T-056-01). PR draft #7 à mettre à jour
(push) au fil des commits ; la passer « ready » quand US-056/052/059 sont livrées.
