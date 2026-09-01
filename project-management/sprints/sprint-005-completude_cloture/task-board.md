# Task Board — Sprint 5 (Complétude et clôture du cycle temps)

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🔲 À Faire

| ID | US | Tâche | Est. |
|----|----|-------|------|
| T-TECH-04 | Tech | Fixtures démo EPIC-003 (🟢 optionnel) | 2h |

## 🔄 En Cours
| ID | US | Tâche | Démarré |
|----|----|-------|---------|

## 👀 En Review
| ID | US | Tâche | Reviewer |
|----|----|-------|----------|

## ✅ Terminé
| ID | US | Résultat | Commit |
|----|----|----------|--------|
| T-TECH-03 | Tech | Hardening `sprintf`→`set_config` (3 sites, param lié) | `f231e3f` |
| T-057-01 | US-057 | Agrégat `AccountingPeriod` + RLS + repository | `89eab6e` |
| T-057-02 | US-057 | `ClosePeriod` (403/CA-3 422/idempotent/journal/`PeriodClosed` async) | `9af3db0` |
| T-057-03 | US-057 | `DoctrinePeriodClosure` → **remplace le stub** ; recompute 423 sur clôture réelle | `9af3db0` |
| T-057-04 | US-057 | Verrou modif **423** (`PeriodModificationGuard`) + trigger DB anti-UPDATE/DELETE | `a7a4cc4` |
| T-057-05 | US-057 | Réouverture formelle (403, fenêtre 48h) + garde levé/re-verrouillé | `15fa37c` |
| T-057-06 | US-057 | Handler async `PeriodClosed` → calculs aval ; helper `CalendarMonth` (DRY) | `2718372` |
| T-057-07 | US-057 | Écran `/administration/periodes` (statut couleur, clôture + confirmation + CSRF) | `bf52fee` |
| T-057-08 | US-057 | Fonctionnel **423** saisie en période clôturée (201 si ouverte) | `c64af85` |
| T-057-09 | US-057 | Doc `docs/modules/period.md` + revues (GO) : 4-eyes, trigger move-in, dédup, a11y, DRY | `a47bdbc` |
| US-054 | US-054 | **Absences (8/8)** : entités demi-journée + RLS, déclaration/décision + notifs, compteurs, blocage RG-TMP-3 (422), API, écran `/absences`, **gate RGPD HAB-3** + intrusion RLS, doc + revues (GO : self-approval, RGPD commentaire, index) | `6e2feb3`→`739c506` |
| US-058 | US-058 | **Complétude (6/6)** : service grille (4 états, absences déduites), périmètre RBAC (403 équipe), API + export CSV anti-injection, écran `/completude` (grille couleur), doc + revue (GO ; N+1 accepté phase 1, cache/batch phase 2) | `222eb18`→`0add3d5` |
| US-056 | US-056 | **Relances (7/7)** : entités + RLS (3 tables), moteur borné/déterministe (plancher **par jour ouvré**, escalade 3ᵉ, arrêt à la soumission, opt-out, désactivation globale), CLI cron + handler async + notifier + RLS-via-consume, permission MANAGE_REMINDERS + API (opt-out non forçable), écran `/relances` + bandeau `/saisie` (**conception UX/UI préalable** — consigne PO), doc + revues (GO) | `(S5)` |
| US-052 | US-052 | **Saisie mobile (6/6)** : vue dédiée `/saisie/jour/{date}` (cartes mobile-first, 44px, inputmode, font ≥16px), Stimulus (total live, swipe + flèches accessibles, reprise veille, offline localStorage + resync), dégradation 320px, `<meta viewport>`+`lang=fr`, réutilise l'API US-050 (**conception UX/UI préalable**), doc + revues (GO : fuite listeners + N+1 corrigés) | `(S5)` |
| US-059 | US-059 | **Synthèse activité (6/6)** : service `ActivitySummary` (répartition projet/type, occupation, statuts RG-TMP-4), API `/api/activity-summary` scoped soi-même (403 sur user_id tiers) + planning dégradé (US-037 absente), drawer « Ma synthèse » `<dialog>` SSR lecture seule (1 clic, focus rétabli — CA-5) + bottom-sheet mobile, barres accessibles, doc + revues (**conception UX/UI préalable** ; GO) | `(S5)` |

## 🚫 Bloqué
| ID | US | Raison | Action |
|----|----|--------|--------|

## Métriques
- **Tâches** : 44 total | 43 terminées (98 %) — seul T-TECH-04 (🟢 optionnel) reste
- **Heures** : ~126h estimées | ~132h consommées
- **Points** : 22 engagés · **6/6 US livrées** : US-057 ✅ · US-054 ✅ · US-058 ✅ · US-056 ✅ · US-052 ✅ · US-059 ✅ (+ T-TECH-03)
- **CI** : `make ci` vert — **371 tests**, PHPStan max, Deptrac, cs/rector, gitleaks, `schema:validate`.
- **Suite** : T-TECH-04 (fixtures démo, optionnel) ; sinon sprint prêt pour Review/Rétro et PR #7 « ready ».
