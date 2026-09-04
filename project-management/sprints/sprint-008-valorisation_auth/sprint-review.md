# Sprint Review — Sprint 8 (Valorisation & authentification web)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-04 |
| Durée | 2h (cérémonie) |
| Sprint Goal | « La **valorisation du temps validé est calculée automatiquement et démontrable** sur le jeu de démo, les **irritants de recette** sont résorbés, et les utilisateurs disposent d'**écrans d'authentification web** et d'un **profil enrichi** (nom/prénom). » |
| EPICs | EPIC-003 (valorisation), EPIC-000 (comptes/auth), EPIC-012 (recette) |

## 🎯 Atteinte du Sprint Goal

**Sprint Goal atteint : ✅ OUI (périmètre engagé livré à 100 %, + durcissements au-delà)**

Justification :
- **Valorisation automatique & démontrable** — le pipeline async valorise le temps validé et le tableau de bord `/valorisation` est démontrable sur le jeu de démo (CA 3 600 €, 5/5 imputations valorisées), enrichi de la **ventilation par projet** (CA/coût/marge) et du **taux d'occupation par collaborateur**. Le finding F2 (`MISSING_RATE`) est fermé structurellement (écran d'affectation).
- **Irritants de recette résorbés** (US-070) — build Tailwind (`make up`), unité en heures sur `/validation`, seed profils/tarifs/valorisation.
- **Auth web** (US-068) — login/logout, écran « Mon compte », **mot de passe oublié** (bundle + mailer), et **durcissement** (rate limiting, invalidation des sessions, mot de passe non compromis).
- **Profil enrichi** (US-067) — nom/prénom, affichage « Prénom Nom » avec repli e-mail.

## 📦 User Stories livrées

| ID | Titre | Points | Priorité | Demo | Statut |
|----|-------|--------|----------|------|--------|
| US-070 | Findings recette (build, unité, seed) | 3 | 🔴 Must | ✅ | ✅ Livré |
| US-060 | Valorisation automatique du temps validé | 8 | 🔴 Must | ✅ | ✅ Livré |
| US-067 | Enrichissement profil (nom/prénom) | 3 | 🟡 Should | ✅ | ✅ Livré |
| US-068 | Écrans d'authentification web | 8 | 🟡 Should | ✅ | ✅ Livré |

**Livré : 22/22 points (100 %)**

### Au-delà du périmètre engagé (durcissement issu des revues de clôture)
- **T-060-09** — perf analytique : index `time_entry(project_id)` + coalescence du rebuild (`AnalyticsRebuildScheduler`).
- **T-068-10** — durcissement auth : rate limiting (`login_throttling` + limiteur IP reset) + invalidation des sessions au changement de mot de passe.
- **T-068-11** — mot de passe non compromis (HaveIBeenPwned, fail-open) via `PasswordPolicy` partagé.

## ❌ User Stories non terminées

Aucune. Le périmètre engagé (22 points) est intégralement livré et mergé dans `main`.

## 📈 Métriques

| Métrique | Valeur | Tendance |
|----------|--------|----------|
| Points planifiés | 22 | - |
| Points livrés | 22 | - |
| Vélocité | 22 | ↔️ (moy. récente ~24) |
| Taux de complétion | 100 % | ↗️ |
| Tâches board | 26 initiales + 2 follow-ups livrés | - |
| PR mergées (sprint) | #23 → #35 (13 PR) | - |
| `make ci` | vert à chaque merge (470 tests, PHPStan max, Deptrac, gitleaks) | ↗️ |
| Dette technique ajoutée | 0 (2 revues de clôture : `symfony-reviewer` 28/30, `security-auditor` sans bloquant) | ↘️ |

Historique vélocité (S1→S8) : 29 / 20 / 23 / 21 / 22 / 21 / 33 / **22**.

## 🎬 Démonstration

Ordre de démo suggéré (sur le jeu de démo `app:demo:seed`, comptes `camille|marc|admin@demo.test`) :

1. **Valorisation démontrable (US-060)** (~8 min)
   - `/valorisation` : CA reconnu, avancement, fraîcheur ; **ventilation par projet** (CA + coût/marge selon habilitation) ; **occupation par collaborateur**.
   - Écran d'affectation profil↔collaborateur (`/profils`) → la valorisation cesse d'être `MISSING_RATE`.
2. **Auth web (US-068)** (~7 min)
   - Login / logout ; écran « Mon compte » (nom/prénom + changement de mot de passe) ;
   - **Mot de passe oublié** : demande → e-mail → réinitialisation ; refus d'un mot de passe compromis.
3. **Profil enrichi (US-067) + irritants (US-070)** (~3 min)
   - Affichage « Prénom Nom » ; build `make up` fonctionnel ; durée `/validation` en heures.

### Scénario de démo (Gherkin)

```gherkin
Given un manager habilité VIEW_PROJECT_FINANCIALS
When il ouvre /valorisation
Then il voit le CA reconnu, la ventilation par projet et l'occupation par collaborateur

Given un utilisateur ayant oublié son mot de passe
When il demande une réinitialisation puis suit le lien reçu par e-mail
Then il définit un nouveau mot de passe (12–128 car., non compromis) et peut se reconnecter
```

## 💬 Feedback à collecter (stakeholders)

1. La ventilation par projet et l'occupation répondent-elles au besoin de pilotage financier ?
2. Le mois de référence de l'occupation (dernière prestation valorisée) est-il le bon cadrage, ou faut-il un sélecteur de période ?
3. Le parcours « mot de passe oublié » est-il clair ? Faut-il un envoi SMTP réel en staging (MAILER_DSN) ?
4. Priorités pour le Sprint 9 ?

## 📝 Notes de session

Feedback reçu :
- _(à compléter en séance)_

Nouvelles demandes :
- _(à compléter en séance)_

Décisions prises pendant le sprint :
- Occupation cadrée sur le mois de la dernière prestation valorisée (démontrable) — sélecteur de période hors périmètre (YAGNI).
- Mot de passe oublié : un jeton **par compte** (e-mail unique seulement par tenant).
- Envoi d'e-mail **async** (anti-énumération par timing) ; `NotCompromisedPassword` **fail-open**.

## Impact sur le Backlog

| Action | ID | Description |
|--------|-----|-------------|
| Suivi | — | Recette navigateur sur données peuplées (action rétro S7) à rejouer sur les nouveaux écrans |
| Config déploiement | — | Définir un `MAILER_DSN` SMTP réel par environnement (défaut committé : `null://null`) |

## Prochaines étapes

1. Rétrospective Sprint 8 (`/workflow:retro`).
2. Recette navigateur sur données peuplées des nouveaux écrans (valorisation enrichie, auth).
3. Planification Sprint 9.
