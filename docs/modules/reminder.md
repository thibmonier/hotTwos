# Module Relances de retard de saisie (US-056)

Relances automatiques, **paramétrables et bornées**, des retards de saisie détectés par la complétude
(US-058). Sert `OBJ-1` sans harceler : plancher anti-spam, opt-out individuel (RGPD), désactivation
globale. `EF-TMP-21`.

## Modèle

| Élément | Rôle |
|---------|------|
| `Domain\Reminder\ReminderRule` | Règle **unique par tenant** : délai initial, fréquence, canal, escalade, activation globale. Bornes 0–30 / 1–30. |
| `Domain\Reminder\ReminderPreference` | Opt-out **individuel** du collaborateur (droit RGPD). |
| `Domain\Reminder\ReminderLog` | Journal des relances émises : destinataire, semaine, canal, rang, escaladé, date. Mémoire du moteur. |
| `Domain\Reminder\ReminderChannel` | `in_app` / `email` / `both`. |
| `Domain\Reminder\ReminderNotifier` | Port d'acheminement (adaptateur `LoggingReminderNotifier` — livraison effective en dette). |
| `Application\Reminder\ScheduleReminders` | Moteur pur/déterministe : décide les relances dues à un instant. |
| `Application\Reminder\SendDueReminders(+Handler)` | Message async + handler : calcule, journalise, notifie (tenant-aware). |
| `Application\Reminder\ConfigureReminders` / `SetReminderPreference` | Use cases config (habilité) / opt-out (soi-même). |
| `Application\Reminder\ReminderBanner` | Rappel discret dans `/saisie` pour un collaborateur en opt-out + retard. |

## Règles du moteur (`ScheduleReminders`)

Pour chaque (collaborateur, semaine) de la fenêtre glissante (4 semaines) :
- **Détection** : la semaine est en retard si son état de complétude est `partial` ou `empty_late`
  (arrêt automatique à la soumission — CA-3 : une semaine `submitted` n'est jamais relancée).
- **Délai initial** : première relance à `échéance J+2` (réutilisée via `CompletenessGrid::deadline`)
  `+ délai initial` paramétré.
- **Fréquence bornée** : relances suivantes espacées d'au moins `max(fréquence, 1)` jours, **et**
  émises uniquement les **jours ouvrés** → plancher anti-spam hardcodé : au plus une relance par
  jour ouvré (CA-4), quelle que soit la configuration.
- **Escalade** : à partir de la **3ᵉ** relance, si l'escalade est activée, la relance est marquée
  escaladée (N+1).
- **Opt-out (CA-2)** : les collaborateurs en opt-out sont exclus.
- **Désactivation globale (CA-5)** : si la règle du tenant est inactive (ou absente), aucune relance.

Le moteur est **pur** (horloge injectée, aucun effet de bord) : borne et escalade testables hors
temps réel. L'émission (journal + notification) relève du handler.

## Déclenchement (cron → async)

`app:reminders:run [tenant?]` (CLI, planifiée par cron) **ne calcule rien** : elle publie un message
`SendDueReminders(tenant, now)` **asynchrone** par tenant (registre `TenantRegistry`, ou tenant ciblé),
l'instant figé au déclenchement. Le worker consomme sous **contexte de tenant** posé par le
`TenantContextMiddleware` (filtre ORM **et** `app.current_tenant` pour la RLS) : lectures du moteur et
écriture du journal sont ainsi cloisonnées. Jamais routé en `sync` (sinon le contexte de la requête
serait effacé).

## Opt-out & RGPD

L'opt-out est un **droit individuel** : `SetReminderPreference` n'agit **que** sur l'utilisateur
courant (`PUT /api/me/reminder-preference`). Aucune route ne permet d'agir sur la préférence d'un
tiers — l'administrateur peut désactiver **globalement** (`ReminderRule::deactivate`) mais **ne peut
pas** forcer la réactivation d'un opt-out. Le journal ne stocke que des identifiants techniques et le
rang (aucune donnée sensible). Opt-in/opt-out sont tracés (`SecurityAuditLogger`).

## Sécurité & RLS

Les trois tables (`reminder_rule`, `reminder_preference`, `reminder_log`) portent la **RLS** dès la
migration (double barrière `ENABLE`+`FORCE`, policy texte sur `app.current_tenant`). Le paramétrage
exige `MANAGE_REMINDERS` (chef de projet / admin — 403 sinon, ARC-19). Bornes invalides → **422**.

## API & Web

| Opération | Effet |
|-----------|-------|
| `GET/PUT /api/reminders/rules` | Lecture / mise à jour de la règle (habilité ; 401 anonyme, 403 non habilité, 422 bornes). |
| `PUT /api/me/reminder-preference` | Opt-out/opt-in **du collaborateur courant** (droit individuel). |
| `GET/POST /relances` | Écran config (fieldsets), prévisualisation des relances dues, historique. POST-Redirect-Get + CSRF. |
| Bandeau `/saisie` | Rappel discret (opt-out + retard), `role="status"`, sans lien de réactivation. |

## Tests

- `ReminderRuleTest` (bornes, défaut, reconfigure, escalade), `ScheduleRemindersTest` (délai initial,
  plancher, escalade on/off, arrêt à la soumission, opt-out, désactivation, jour non ouvré).
- `SendDueRemindersHandlerTest` (journal + notification), `ReminderBannerTest` (opt-out + retard).
- `ReminderApiTest` (401/403/422, opt-out non forçable), `ReminderPageTest` (403, config PRG, 422),
  `RunRemindersCommandTest` (dispatch 1/tous), `TimesheetPageTest` (bandeau).
- `ReminderRlsRuntimeTest` (isolation des 3 tables), `ReminderWorkerRlsTest` (**RLS via consume** du
  journal).

## Limites connues / suite

- **Livraison des notifications** : `LoggingReminderNotifier` trace l'intention ; l'envoi effectif
  in-app/email est une **dette suivie** (commune avec les notifications d'absence US-054).
- **Périmètre N+1 de l'escalade** : la relance est marquée escaladée, mais l'identification du
  manager destinataire réel dépend de la hiérarchie (US-010).
- **Opt-out en libre-service** : exposé par l'API ; une commande UI dédiée pour le collaborateur
  (au-delà du bandeau informatif) est un raffinement de la phase de conception UX/UI.
- **Jours fériés** : le plancher « jour ouvré » et l'échéance J+2 ignorent les jours fériés du tenant
  (raffinement ultérieur, cohérent avec la complétude).
- **Performance** : la prévisualisation réutilise `CompletenessGrid` (N+1 connu, accepté phase 1).

## Revue (T-056-07) — findings traités

Revues `security-auditor` + `symfony-reviewer` — **conclusion : GO** (architecture hexagonale saine,
moteur pur/déterministe, opt-out non forçable, RLS fail-closed, logs sans PII).

- **[Medium — corrigé] Plancher par jour ouvré** (`security-auditor`) : le plancher était par
  `(collaborateur, semaine)`, un collaborateur avec plusieurs semaines en retard pouvait recevoir
  plusieurs relances le même jour. **Corrigé** : cap **une relance par collaborateur et par jour
  ouvré** (`ScheduleReminders::capOnePerUserPerDay` + `ReminderLogRepository::sentOnDay`), ciblant la
  dette la plus ancienne. Tests ajoutés.
- **[Mineur — corrigé] Tests de `SetReminderPreference`** (`symfony-reviewer`) : logique opt-out/opt-in
  non couverte en unitaire → `SetReminderPreferenceTest` ajouté (création, bascule idempotente, audit).
- **[Majeur conformité — rejeté] Pseudonymisation du `userId`** (`symfony-reviewer`) : rejeté — le
  `userId` est un **UUID pseudonyme**, stocké en clair dans **toute** la codebase (dont `absence_request`,
  qui a passé son gate RGPD) ; le hasher casserait RLS et jointures. Le droit à l'oubli s'exerce au
  niveau de l'entité `User`.
- **[Mineurs — rejetés] `filter_var` vs Symfony Validator, invariant `fréquence < délai`** : `filter_var`
  est l'idiome des contrôleurs du projet (cf. `TimeEntryController`) ; l'invariant relève du YAGNI.
- **[Low — documentés, non bloquants]** CSRF absent sur l'API JSON (cohérent avec toutes les APIs JSON
  du projet, mitigé SameSite + `Content-Type`) ; `now` figé au dispatch (choix déterministe assumé,
  SLA worker court) ; historique tenant-wide en UUID (cohérent avec le rôle `MANAGE_REMINDERS`).

**Conclusion : GO** — le plancher anti-spam est désormais par jour ouvré et par collaborateur ; les
autres findings sont soit corrigés, soit rejetés pour cohérence avec la codebase, soit suivis en dette.
