# HotOnes

ERP de gestion d'agence digitale / ESN. Refonte SaaS multi-tenant.

**Stack** : PHP 8.5 · Symfony 8.1 · FrankenPHP (mode worker, ADR-2) · PostgreSQL 16 + pgvector · API Platform (DTO strict) · Twig/Stimulus/Turbo. Architecture Clean + DDD (ADR-8).

> Cadrage complet (PRD, backlog, conception, ADR) : [`project-management/`](project-management/) · Spécification technique : [`project-management/tech-spec.md`](project-management/tech-spec.md).

## Prérequis

- Docker + Docker Compose
- (dev local hors conteneur) PHP 8.4+, Composer 2, Symfony CLI

## Démarrage rapide

```bash
make up        # build + démarre l'app (:8080) et PostgreSQL/pgvector (:5432)
make migrate   # applique les migrations Doctrine
make fixtures  # charge les jeux de données de test (3 tailles de tenant)
```

L'application répond alors sur http://localhost:8080 — vérifier : `curl http://localhost:8080/health` → `{"status":"ok","app":"HotOnes"}`.

## Commandes utiles

```bash
make help      # liste toutes les cibles
make test      # suite de tests (PHPUnit)
make deptrac   # vérifie les frontières d'architecture (0 violation attendue)
make db-shell  # psql sur la base
make secrets   # détection de secrets (gitleaks)
make down      # arrête l'environnement
```

## Architecture (couches)

```
src/
├── Domain/          # Cœur métier, invariants — aucune dépendance framework
├── Application/     # Cas d'usage (invocables sans HTTP — ARC-17)
├── Infrastructure/  # Doctrine, adaptateurs sortants
└── UI/              # Adaptateurs entrants : Http/ (contrôleurs), Cli/ (commandes)
```

Les frontières (sens des dépendances) sont vérifiées par **Deptrac** (`deptrac.yaml`) et bloquantes en CI.

## Configuration

| Variable | Description | Défaut (dev) |
|----------|-------------|--------------|
| `DATABASE_URL` | Connexion PostgreSQL | `postgresql://app:app@database:5432/app?serverVersion=16` |
| `APP_ENV` | Environnement | `dev` |

Les secrets réels vont dans `.env.local` (jamais commité — `ARC-88`).

## Tests

```bash
php bin/phpunit          # ou : make test
```

Objectif de couverture : ≥ 80 % sur les règles critiques (`ENF-MAINT-1`), bloquant en CI.
