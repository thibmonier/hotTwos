# Rapport de recette — Sprint 5 (EPIC-003 : complétude & clôture)

| Attribut | Valeur |
|----------|--------|
| Session | REC-20260902-sprint5 |
| Date | 2026-09-02 |
| Périmètre | Sprint 5 — US-057, US-054, US-058, US-056, US-052, US-059 (profondeur : cœur ✅) |
| Base | http://localhost:8080 (app déjà up), tenant démo (camille/marc/admin, mdp demo-1234-solide) |
| Auth | `POST /api/login` (json_login) ; contrat saisie : `POST /api/time-entries {projectId, date, minutes}` |

## Synthèse

Recette majoritairement **verte**. Les gardes **sécurité/intégrité** du module de pilotage sont
solides et vérifiées côté serveur : blocage d'imputation sur absence validée (422), verrou de période
clôturée (423), cloisonnement RBAC (403). 2 findings **UX/a11y** (aucun bug de logique ni de sécurité).

| Verdict | Nb |
|---------|----|
| ✅ PASS (navigateur/API) | 14 |
| 🟡 partiel / non déroulé | 7 |
| ⚪ hors-navigateur (canal notif/async) | 3 |
| ❌ finding | 2 (UX/a11y) |

## Cas ✅

- **US-058 complétude** — CA-1 grille collaborateurs × 4 semaines, **4 états** (✅/⚠️/❌/⏳) + légende
  permanente + badges non-couleur-seule (WCAG 1.4.1) ; CA-3 export CSV (200, `text/csv`, attachment,
  périmètre équipe, aucun champ dangereux) ; CA-5 cloisonnement : `/api/completude?scope=team` = **403**
  pour camille, sa page reste accessible.
- **US-059 synthèse** — CA-1 panneau « Ma synthèse » **inline en 1 clic** (taux d'occupation, répartition
  par type/projet) ; CA-4 `/api/activity-summary?user_id=<autre>` = **403** (propre = 200).
- **US-052 saisie mobile** — CA-1/CA-2 parité fonctionnelle de /saisie/jour (lignes projet, durée en
  minutes [clavier `numeric`], commentaire, « Reprendre la saisie de la veille » = duplication, navigation
  jour, enregistrer) ; CA-5 **aucun overflow-x** même à 220 px.
- **US-054 absences** — CA-2 imputation de production sur jour d'absence validée → **HTTP 422** (message
  exact, RG-TMP-3, contrôle serveur) ; CA-3 compteurs Acquis/Pris/En attente/Solde **+ projeté** ; CA-4
  **HAB-3** : aucun champ ni texte « motif médical / diagnostic ».
- **US-056 relances** — CA-2 opt-out individuel : `PUT /api/me/reminder-preference {optedOut:true}` = 200,
  persisté.
- **US-057 clôture période** — CA-3 clôture sans `force` avec imputations non finalisées → **avertissement**
  (« N imputation(s) non finalisée(s) ») + non clôturée ; CA-1 clôture avec `force` → « période 2026-08
  clôturée (4 imputation(s) non finalisée(s) exclue(s)) » ; CA-4 imputation sur période clôturée →
  **HTTP 423 Locked** (« … réouverture formelle », message + code exacts).

## Findings (UX/a11y — aucun bug de sécurité)

### F-S5-1 — moyen — cibles tactiles < 44 px sur la vue jour mobile (ENF-UX-3, US-052)
Champs (durée/commentaire) et boutons de `/saisie/jour` ≈ **18–21 px de haut** (mesuré à viewport ≤ 390 px),
sous le minimum tactile de 44 px. `overflow-x` OK. → REG-S5-001 (à traiter en passe UI mobile).

### F-S5-2 — faible — cellules d'un jour d'absence non grisées dans la grille (US-054/CA-2)
Les cellules de production d'un jour d'absence validée restent éditables (aucun `disabled`). Le serveur
refuse correctement (422), donc **pas de risque d'intégrité** ; la parité UI ↔ serveur reste à faire. → REG-S5-002.

## Non déroulé (cœur ✅) / hors-navigateur

- 🟡 US-057 CA-2 (réouverture formelle 48 h + trace avant/après), CA-5 (403 rôle non habilité) ;
  US-054 CA-1/CA-5 (déclaration/refus — notification ⚪) ; US-058 CA-2 (relance manuelle — envoi ⚪) ;
  US-052 CA-3/CA-4 (swipe, offline) ; US-059 CA-2/CA-5 (planning US-037, focus).
- ⚪ US-056 CA-1/3/4 (moteur async + canal), US-058 CA-4 (tenant vierge) : canal de notification non
  livré (action rétro S5 toujours ouverte) → attestés par la suite de tests unitaires.

## Observations

- **F1 (Sprint 6, récurrent)** : collaborateurs affichés par **préfixe d'UUID** (complétude, absences) —
  lié à l'absence de référentiel utilisateur (US-014).
- **Outillage** : la grille /saisie est pilotée par Stimulus ; les `input`+`click` synthétiques ne
  persistent pas → tests de saisie faits via `POST /api/time-entries` (le contrat que l'AC cible).

## État des données dev (à connaître)

- ⚠️ La **période 2026-08 a été clôturée (force)** pendant la recette → imputations d'août verrouillées,
  4 exclues. Une imputation de contrôle a été créée le 2026-08-21. Un `app:demo:seed` sur base fraîche
  réinitialise cet état.

## Suite

- Les 2 findings sont **UX/a11y**, non bloquants et sans impact sécurité → à regrouper dans une passe UI
  mobile (avec la phase de conception UX/UI). `/qa:tdd` possible pour F-S5-1 si une garde CSS testable est souhaitée.
