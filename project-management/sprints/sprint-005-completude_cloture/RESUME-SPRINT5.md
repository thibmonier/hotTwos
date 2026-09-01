# Note de reprise — Sprint 5 (après /clear)

> Handoff pour continuer le Sprint 5 « Complétude et clôture du cycle temps ». Lire aussi
> `task-board.md`, `tasks/README.md` et les docs modules `docs/modules/{period,absence,completeness}.md`.

## Où on en est

- **Branche** : `feature/sprint-5-planning`. **PR draft #7** ouverte (WIP). Base `main` (Sprint 4
  mergé PR #6, Railway à jour). Tout est commité et **vert** : `make ci` = **318 tests**, PHPStan
  max 0, Deptrac 0, cs/rector OK, gitleaks OK. `schema:validate` OK.
- **Avancement : 43/44 tâches (98 %)** — **6/6 US livrées**, seul T-TECH-04 (fixtures démo, 🟢
  optionnel) reste. Voir `task-board.md`. `make ci` = **371 tests** verts.
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
  - ✅ **US-056 Relances (7/7)** — entités `ReminderRule`/`Preference`/`Log` + RLS (3 tables), moteur
    `ScheduleReminders` pur/déterministe (**plancher par jour ouvré et par collaborateur** —
    `capOnePerUserPerDay`/`sentOnDay`, escalade 3ᵉ, arrêt à la soumission, opt-out, désactivation),
    CLI `app:reminders:run` + `SendDueReminders`/handler async + `ReminderNotifier` (adaptateur
    logging — livraison in-app/email en dette) + **RLS-via-consume**, permission `MANAGE_REMINDERS` +
    API (`/api/reminders/rules`, `/api/me/reminder-preference` opt-out **non forçable**), écran
    `/relances` + bandeau `/saisie` (**conception UX/UI préalable** : ux-ergonome + ui-designer +
    accessibility-expert). Doc `docs/modules/reminder.md`. Revues security-auditor + symfony-reviewer :
    **GO** (findings traités : plancher par jour ouvré corrigé, tests opt-out ajoutés ; hashage userId
    rejeté — UUID pseudonyme cohérent codebase).

  - ✅ **US-052 Saisie mobile (6/6)** — vue dédiée `/saisie/jour/{date}` (`TimesheetDayController` +
    `day.html.twig`, cartes mobile-first 44px, `inputmode`, font ≥16px), Stimulus `timesheet-day`
    (total live, swipe + flèches accessibles, reprise veille, offline `localStorage` + resync),
    dégradation 320px, `<meta viewport>` + `lang=fr` en base, réutilise l'API US-050. Doc
    `docs/modules/timesheet-mobile.md`. Revues GO (fuite listeners + N+1 corrigés).
  - ✅ **US-059 Synthèse activité (6/6)** — `ActivitySummary` (répartition projet/type production/
    absence, occupation, statuts VALIDÉ+SOUMIS — RG-TMP-4), API `/api/activity-summary` scoped
    soi-même (403 sur `user_id` tiers) + planning dégradé (US-037 absente), drawer « Ma synthèse »
    (`<dialog>` natif **SSR lecture seule**, 1 clic, focus rétabli — CA-5) + bottom-sheet mobile,
    barres CSS accessibles. Doc `docs/modules/activity.md`. Revue GO.

## Reste : T-TECH-04 (optionnel) puis clôture

- **T-TECH-04** (🟢 optionnel) : fixtures de démo EPIC-003. `LoadFixturesCommand` est aujourd'hui un
  squelette (affiche les volumétries cibles, ne génère pas d'entités — « à brancher avec le modèle
  US-001 »). Générer des données de démo réalistes (tenant, users, projets, imputations, absences,
  périodes, règles de relance) est un effort à part entière ; à traiter isolément si souhaité.
- Sinon, le sprint est **prêt pour la Sprint Review / Rétro** et la PR draft #7 peut passer « ready ».

**Consigne PO honorée** : chaque écran livré ce sprint (US-056 `/relances` + bandeau, US-052
`/saisie/jour`, US-059 drawer) a été **précédé d'une phase de conception UX/UI** (ux-ergonome +
ui-designer + accessibility-expert), puis revu (a11y WCAG 2.2 AA).

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
