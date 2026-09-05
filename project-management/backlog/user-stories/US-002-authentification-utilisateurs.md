# US-002: Authentification et cycle de vie des utilisateurs

## Métadonnées
- **ID**: US-002
- **EPIC**: EPIC-000
- **Sprint**: Sprint 1
- **Statut**: ✅ Done (livré Sprint 1)
- **Points**: 5
- **Persona**: ADMIN
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-REF-30, ENF-SEC-1, ENF-SEC-3
- **Dépend de**: US-001
- **Spec Technique**: ENF-SEC-1 (politique de mots de passe), ENF-SEC-3 (chiffrement en transit et au repos), EF-REF-30 (gestion du cycle de vie utilisateur)

## User Story

**En tant qu'** administrateur tenant,
**je veux** gérer le cycle de vie complet des utilisateurs (invitation, activation, désactivation) avec une politique de mot de passe robuste, un 2FA activable par tenant et un chiffrement des données en transit et au repos,
**afin de** garantir que seules des personnes habilitées et authentifiées accèdent à la plateforme et que les données restent protégées même en cas de compromission partielle de l'infrastructure.

## Critères d'Acceptation

### CA-1 (Nominal) : Invitation, activation du compte et première connexion
```gherkin
GIVEN l'administrateur du tenant T-001 est connecté sur la console d'administration
  AND l'adresse email "camille.dupont@agencealpha.fr" n'existe pas encore dans le tenant
WHEN l'administrateur invite cet email avec le rôle "Collaborateur"
THEN un email d'invitation est envoyé à l'adresse dans les 60 secondes
  AND le lien d'activation est signé (HMAC-SHA256) et expire après 24 heures
  AND l'utilisateur clique sur le lien, choisit un mot de passe conforme (≥12 caractères, majuscule, minuscule, chiffre, caractère spécial) et son compte est activé
  AND à la connexion suivante l'utilisateur accède au tableau de bord du tenant T-001 uniquement
```

### CA-2 (Alternatif) : Activation du 2FA par l'administrateur du tenant
```gherkin
GIVEN le 2FA est désactivé par défaut pour le tenant T-002
  AND l'administrateur de T-002 active l'option "2FA obligatoire" dans les paramètres de sécurité du tenant
WHEN un utilisateur de T-002 se connecte avec ses identifiants valides après l'activation du 2FA
THEN l'application exige un code TOTP (RFC 6238) avant d'accorder l'accès
  AND un utilisateur qui n'a pas encore configuré son application TOTP est redirigé vers l'écran de configuration avant de pouvoir accéder à la plateforme
  AND un tenant sans 2FA obligatoire peut toujours se connecter sans TOTP (isolation de la configuration par tenant)
```

### CA-3 (Alternatif) : Désactivation d'un utilisateur
```gherkin
GIVEN l'utilisateur "marc.durand@agencealpha.fr" est actif dans le tenant T-001
WHEN l'administrateur désactive cet utilisateur depuis la console
THEN les sessions actives de marc.durand sont immédiatement révoquées (dans les 5 secondes)
  AND toute tentative de connexion ultérieure avec ses identifiants retourne 401 avec le message "Compte désactivé — contactez votre administrateur"
  AND ses données (projets, feuilles de temps, etc.) restent intactes et accessibles par les administrateurs
```

### CA-4 (Erreur) : Mot de passe non conforme à la politique de sécurité
```gherkin
GIVEN un utilisateur tente de définir son mot de passe lors de l'activation de son compte
WHEN il soumet le mot de passe "motdepasse" (inférieur à 12 caractères, aucun caractère spécial)
THEN l'application refuse l'enregistrement avec le message détaillant les règles non respectées
  AND aucun hash n'est stocké en base de données
  AND le formulaire reste pré-rempli avec l'email pour permettre une nouvelle tentative sans ressaisie
```

### CA-5 (Erreur) : Tentative de connexion avec un lien d'activation expiré
```gherkin
GIVEN un administrateur a invité "sophie.martin@agencealpha.fr" il y a plus de 24 heures
  AND l'utilisateur n'a pas encore activé son compte
WHEN l'utilisateur clique sur le lien d'invitation dans l'email
THEN l'application affiche le message "Ce lien d'activation a expiré. Veuillez contacter votre administrateur pour recevoir une nouvelle invitation."
  AND le lien ne peut pas être utilisé même si la signature HMAC est valide (contrôle de la date d'expiration)
  AND l'administrateur peut régénérer un nouveau lien d'invitation depuis la console
```

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Code reviewé
- [ ] Tests unitaires passent
- [ ] Tests d'intégration passent
- [ ] Documentation mise à jour

---

## Notes

Le hachage des mots de passe doit utiliser Argon2id (128 MiB RAM, t=3, p=1) conformément à ENF-SEC-1. Toute utilisation de bcrypt, MD5 ou SHA-1 est interdite.

Le chiffrement en transit est assuré par TLS 1.3 minimum (ENF-SEC-3). Les données sensibles au repos (secrets applicatifs, tokens de refresh) sont chiffrées avec AES-256-GCM.

La configuration 2FA est isolée par tenant : l'activation ou la désactivation sur un tenant n'affecte pas les autres (cohérence avec l'isolation US-001).
