# US-007: Environnement de développement conteneurisé et données de test

## Métadonnées
- **ID**: US-007
- **EPIC**: EPIC-000
- **Sprint**: Sprint 0
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: Équipe technique / Responsable technique
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: ADR-11, ADR-6, ARC-86, ARC-87, ARC-88
- **Dépend de**: US-006 (squelette applicatif)
- **Spec Technique**: ADR-11 (env dev conteneurisé, parité worker dès le dev), ADR-6 (PostgreSQL + pgvector), ARC-86 (même image FrankenPHP dev/prod), ARC-87 (données de test 3 tailles régénérables), ARC-88 (aucun secret réel au dépôt)

## User Story

**En tant que** membre de l'équipe technique,
**je veux** un environnement de développement démarrable en une commande unique, fondé sur la même image FrankenPHP worker qu'en production, avec PostgreSQL + pgvector, et des jeux de données de test des trois tailles de tenant régénérables à la demande — sans qu'aucun secret réel ne figure jamais dans le dépôt,
**afin que** l'équipe puisse construire sur un socle sûr et reproductible, où chaque développeur travaille dans un contexte identique à la production et peut valider son code sans dépendance à un environnement partagé.

## Critères d'Acceptation

### CA-1 (Nominal) : L'environnement de développement complet démarre en une commande

```gherkin
GIVEN le dépôt est cloné sur un poste ne disposant que de Docker et Docker Compose
  AND le fichier .env.local n'existe pas (premier démarrage)
WHEN le développeur exécute la commande make dev (ou docker compose up --build)
THEN tous les conteneurs (FrankenPHP worker, PostgreSQL + pgvector) démarrent sans erreur
  AND l'application Symfony est accessible sur https://localhost:8443 et répond HTTP 200 sur la route /demo
  AND PostgreSQL est accessible sur le port 5432 local avec les extensions pgvector activées (SELECT extname FROM pg_extension WHERE extname = 'vector' retourne une ligne)
  AND la durée totale du premier démarrage (build inclus) est documentée dans le README et ne dépasse pas 5 minutes sur une connexion standard
```

### CA-2 (Alternatif) : Les données de test des trois tailles de tenant sont régénérables

```gherkin
GIVEN l'environnement de développement est démarré
  AND les trois tailles de tenant sont définies : small (1 tenant, ~50 utilisateurs), medium (5 tenants, ~200 utilisateurs), large (20 tenants, ~1 000 utilisateurs)
WHEN le développeur exécute la commande make fixtures SIZE=small (ou medium, ou large)
THEN la base PostgreSQL est réinitialisée et peuplée avec le jeu de données correspondant
  AND la commande se termine avec le code de sortie 0 et affiche un résumé du nombre d'entités créées par type
  AND les données respectent les invariants de domaine (INV-1 : discriminant tenant sur toutes les lignes, INV-2 : taux historisés à date d'effet)
  AND une deuxième exécution consécutive de la même commande produit un résultat identique (idempotence)
```

### CA-3 (Alternatif) : Le mode worker FrankenPHP est exercé dès le développement (ARC-86)

```gherkin
GIVEN l'environnement de développement est démarré avec l'image FrankenPHP en mode worker
  AND l'image Docker utilisée localement est identique à celle déclarée dans la configuration de production (même tag, même Dockerfile)
WHEN le développeur envoie 10 requêtes HTTP successives à l'application locale via curl ou un test d'intégration
THEN chaque requête est traitée par le même processus PHP worker (le PID du worker reste stable entre les requêtes)
  AND le log FrankenPHP confirme le mode worker ("worker_mode: true") pour chaque requête
  AND aucune erreur liée à l'état inter-requêtes (fuite de contexte, variable statique résiduelle) n'est détectée dans les logs
```

### CA-4 (Erreur) : Un secret réel commité dans le dépôt est détecté et le commit est bloqué

```gherkin
GIVEN un hook git pre-commit est installé (via make setup ou l'installation initiale de l'environnement)
  AND le hook utilise un outil de détection de secrets (ex. detect-secrets, gitleaks)
WHEN un développeur tente de commiter un fichier contenant une valeur ressemblant à une clé d'API, un mot de passe ou un token réel (ex. une chaîne AWS_SECRET_ACCESS_KEY=AKIA...)
THEN le hook pre-commit bloque le commit avec un code de sortie non nul
  AND le message d'erreur identifie le fichier, la ligne et la raison du blocage ("Secret detected: AWS key pattern")
  AND le commit n'est pas créé — git status ne montre aucun nouveau commit
  AND les fichiers .env.example présents dans le dépôt ne contiennent que des valeurs factices (ex. "changeme", "your-api-key-here")
```

### CA-5 (Erreur) : Démarrage sans PostgreSQL disponible → message d'erreur explicite, pas de crash silencieux

```gherkin
GIVEN l'environnement est configuré mais le conteneur PostgreSQL est arrêté volontairement (docker stop <postgres-container>)
WHEN le développeur lance l'application (make dev ou docker compose up sans le service postgres) ou exécute php bin/console doctrine:schema:validate
THEN l'application ne démarre pas en silence ni ne renvoie une page d'erreur 500 générique
  AND le message d'erreur affiché indique explicitement que PostgreSQL est inaccessible (ex. "SQLSTATE[08006] connection to server on socket failed")
  AND la commande make dev se termine avec un code de sortie non nul et affiche dans le terminal "Erreur : le service PostgreSQL n'est pas disponible — vérifiez que le conteneur est démarré"
  AND aucune donnée applicative n'est corrompue ou perdue à la suite de ce démarrage incomplet
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

La parité d'image entre dev et production (ARC-86) est non négociable : tout comportement lié au mode worker doit être observable dès le développement, pas seulement en staging ou en production.

Les données de test (ARC-87) couvrent les trois ordres de grandeur de tenant (small, medium, large) pour permettre de tester les performances et l'isolation multi-tenant dans des conditions réalistes. Elles sont régénérées via les fixtures Symfony (DoctrineFixturesBundle ou équivalent) et doivent respecter tous les invariants définis dans `constraints.md`.

Aucun secret réel ne doit jamais figurer dans le dépôt (ARC-88) : les fichiers `.env.local`, `.env.test.local` et tout fichier contenant des credentials sont dans `.gitignore`. Seuls des `.env.example` avec des valeurs factices sont versionnés.

L'extension pgvector doit être activée dès le Sprint 0 dans l'image PostgreSQL de développement, même si les fonctionnalités IA ne sont implémentées qu'au lot 1 (ADR-6/10).
