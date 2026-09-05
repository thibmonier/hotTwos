# US-068: Écrans web d'authentification (connexion, déconnexion, mot de passe oublié, changement de mot de passe)

## Métadonnées
- **ID**: US-068
- **EPIC**: EPIC-000
- **Sprint**: Backlog (non assigné)
- **Statut**: ✅ Done (livré Sprint 8)
- **Points**: 8
- **Persona**: Tous (P1-P6) — tout utilisateur s'authentifie
- **Créé le**: 2026-09-02
- **Mis à jour**: 2026-09-02

## Traçabilité
- **Implémente**: parcours d'authentification côté **interface web** (aujourd'hui absent : seul `json_login` API existe)
- **Dépend de**: US-002 (auth backend : provider `app_users`, Argon2id, firewall), US-061/US-063 (design system + layout pour les écrans), **mailer** (à configurer — prérequis du « mot de passe oublié »)
- **Question ouverte (hors périmètre)**: **création / provisionnement d'un compte** — « comment un compte est-il créé ? » → à définir plus tard (pas d'inscription self-service dans cette US)

## User Story

**En tant que** utilisateur de l'application,
**je veux** disposer d'écrans web pour **me connecter**, **me déconnecter**, **réinitialiser un mot de passe
oublié** et **changer mon mot de passe** depuis « Mon compte »,
**afin d'** accéder à l'application et gérer mon accès sans passer par l'API, de façon sécurisée.

## Critères d'Acceptation

### CA-1 (Nominal) : Connexion via une page web

```gherkin
GIVEN un utilisateur disposant d'identifiants valides
WHEN il ouvre la page de connexion, saisit son e-mail et son mot de passe et valide
THEN il est authentifié (pare-feu web `form_login`, protection CSRF active)
  AND il est redirigé vers sa page d'accueil applicative
  AND son tenant est résolu à la connexion (US-001) et sa session est sécurisée (cookie httpOnly, secure, sameSite)
```

### CA-2 (Alternatif) : Déconnexion depuis l'interface

```gherkin
GIVEN un utilisateur authentifié
WHEN il clique sur "Se déconnecter" dans l'interface (topbar / menu compte)
THEN sa session est invalidée
  AND il est redirigé vers la page de connexion
  AND aucune page protégée n'est plus accessible sans se reconnecter
```

### CA-3 (Alternatif) : Changement de mot de passe dans « Mon compte »

```gherkin
GIVEN un utilisateur authentifié sur l'écran "Mon compte"
WHEN il saisit son mot de passe actuel, un nouveau mot de passe (≥ 12 caractères, politique OWALP) et sa confirmation
THEN le nouveau mot de passe est haché en Argon2id et enregistré
  AND une confirmation est affichée
  AND si le mot de passe actuel est incorrect, l'opération est refusée sans révéler d'information
```

### CA-4 (Alternatif) : Mot de passe oublié — réinitialisation par e-mail

```gherkin
GIVEN un utilisateur qui a oublié son mot de passe
WHEN il saisit son adresse e-mail sur l'écran "Mot de passe oublié"
THEN un e-mail contenant un lien de réinitialisation à usage unique et à durée limitée est envoyé (si le compte existe)
  AND le message affiché est identique que le compte existe ou non (pas d'énumération de comptes)
  AND en suivant le lien valide, l'utilisateur peut définir un nouveau mot de passe (mêmes règles que CA-3)
```

### CA-5 (Erreur) : Identifiants invalides et anti-force brute

```gherkin
GIVEN un utilisateur saisit des identifiants invalides sur la page de connexion
WHEN il valide le formulaire
THEN un message d'erreur générique est affiché (ni "e-mail inconnu", ni "mot de passe incorrect" — pas d'énumération)
  AND les tentatives répétées sont limitées (login throttling / rate limiting) pour contrer le credential stuffing
  AND aucune stack trace ni détail technique n'est exposé
```

### CA-6 (Erreur) : Jeton de réinitialisation invalide ou expiré

```gherkin
GIVEN un lien de réinitialisation expiré, déjà utilisé, ou falsifié
WHEN l'utilisateur l'ouvre
THEN la réinitialisation est refusée avec un message clair (proposition de recommencer)
  AND aucun mot de passe n'est modifié
  AND le jeton est à usage unique (invalidé après emploi)
```

## Critères UI/UX

### Web
- Écrans dédiés (hors layout applicatif authentifié) pour connexion et mot de passe oublié / réinitialisation, conformes au design system (US-061).
- Écran « Mon compte » (dans le layout authentifié) regroupant le changement de mot de passe (et, avec US-067, le nom/prénom).
- Messages d'erreur génériques et non révélateurs ; champs mot de passe masqués avec option d'affichage.
- Accès à la déconnexion depuis la topbar / le menu compte (US-063).

### Mobile
- Formulaires de connexion utilisables sur mobile (champs ≥ 44px, `type` d'input adaptés, pas de zoom auto iOS).

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Firewall web `form_login` (CSRF), logout web, login throttling configurés
- [ ] Reset password : mailer configuré, jeton usage unique + expiration courte
- [ ] Argon2id, politique de mot de passe ≥ 12 caractères, pas d'énumération de comptes
- [ ] Sessions sécurisées (httpOnly, secure, sameSite)
- [ ] Écrans conformes au design system (US-061) + accessibilité WCAG 2.2 AA
- [ ] `make ci` vert ; tests fonctionnels des parcours (login, logout, reset, change) ; documentation mise à jour

---

## Notes

**État actuel** : seule l'authentification **API** existe (`json_login` sur `/api/login`, logout `/api/logout`,
`/api/me` — cf. `SecurityController`). Aucun écran web, aucun changement/reset de mot de passe.

**Prérequis mailer** : le « mot de passe oublié » (CA-4/CA-6) nécessite un **mailer configuré** (`MAILER_DSN`,
`config/packages/mailer.yaml`) — absent aujourd'hui. À provisionner dans le sprint qui prend cette US.

**Question ouverte — création de compte** : la façon dont un compte est **créé/provisionné** (par un
administrateur ? à l'ouverture de tenant US-019 ? invitation ?) reste **à définir plus tard**. Cette US ne
couvre **pas** l'inscription self-service.

**INVEST / découpage** : 8 points au plafond. Découpe possible si la capacité l'exige — (a) connexion +
déconnexion + changement de mot de passe (~5) ; (b) mot de passe oublié / réinitialisation par e-mail (~3,
porte le prérequis mailer).

**Sécurité (rule 11 / OWASP)** : périmètre sensible (authentification) — **non délégable** à un agent sans
revue humaine. Points de vigilance : pas d'énumération de comptes, rate limiting, CSRF, jetons de reset à
usage unique et expiration courte, Argon2id, sessions durcies.
