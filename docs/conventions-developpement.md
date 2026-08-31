# Conventions de développement — HotOnes

> **Contrat versionné entre l'équipe et l'agent de développement assisté** (`ADR-16`, `ARC-105`).
> Ce document fait autorité. Il est lu avant toute contribution, humaine ou assistée.

## Architecture (couches)

Clean Architecture + DDD dosés par sous-domaine (`ADR-8`). Sens des dépendances **strict**, vérifié par Deptrac (`deptrac.yaml`, `ARC-63`) :

```
Domain  ←  Application  ←  UI
   ↑            ↑
   └──── Infrastructure ──┘
```

- **Domain** — entités, value objects, invariants. **Aucune dépendance framework.**
- **Application** — cas d'usage, invocables sans HTTP (`ARC-17`). Portent le comportement.
- **UI** — adaptateurs entrants : `UI/Http/Controller` (web), `UI/Api` (API Platform DTO), `UI/Cli` (commandes). **Aucune logique métier** (`ARC-15`).
- **Infrastructure** — Doctrine, adaptateurs sortants.

### Règles non négociables
- `ARC-15/16` — aucune logique métier dans un contrôleur ni un gabarit.
- `ARC-18` — aucune entité de persistance exposée par l'API : **DTO uniquement** (`ADR-4`).
- `ARC-6` — les calculs financiers/capacitaires vivent dans un moteur unique testé, jamais dupliqués.
- `ARC-19` — validation et **habilitation dans la couche applicative**, jamais dans l'adaptateur.
- `ARC-47/61` — mode worker : aucun état conservé entre requêtes ; tenant posé/effacé par requête.

## Qualité (bloquant en CI et en pré-commit)

| Vérification | Commande | Règle |
|--------------|----------|-------|
| Analyse statique | `make analyse` | PHPStan **niveau max**, 0 erreur |
| Frontières | `make deptrac` | 0 violation (`ARC-63`) |
| Tests | `make test` | verts ; couverture ≥ 80 % sur règles critiques (`ENF-MAINT-1`) |
| Dépréciations | `make rector` | 0 changement attendu (tolérance zéro, `ARC-51`) |
| Dépendances | `make audit` | 0 vulnérabilité critique non traitée (`ENF-SEC-11`) |
| Secrets | `make secrets` | aucun secret commité (`ARC-88`) |

Miroir local complet : `make ci`. Hook pré-commit : `git config core.hooksPath .githooks`.

## TDD (obligatoire — `07-testing`, `ADR-16`)

1. **RED** — écrire un test qui échoue, dérivé de l'exigence (`ARC-108`), **jamais** de l'implémentation.
2. **GREEN** — le minimum de code pour passer.
3. **REFACTOR** — nettoyer, tests toujours verts.

- **`ARC-103`** — chaque règle de gestion `RG-*` est couverte par **au moins un test nommé d'après elle** (ex. `RgTmp1CannotImputeOnUnassignedProjectTest`).
- **`ARC-107`** — tout code livré vient avec ses tests dans le même incrément.

## Périmètre de sécurité — NON délégué (`ARC-106`)

Isolation multi-tenant, habilitations (`HAB-*`) et construction de contexte IA sont **écrites à la main, relues ligne à ligne, testées à la main**, plus test d'intrusion humain. Jamais acceptées sur la seule foi d'une génération.

## Style

- PHP 8.4+, `declare(strict_types=1)` partout (appliqué par Rector).
- Classes `final` par défaut ; `readonly` quand l'état est immuable.
- PSR-12. Nommage explicite, méthodes courtes (`05-kiss-dry-yagni`).

## Git (`09-git-workflow`)

- Branches `feature/`, `fix/`… ; jamais de commit direct sur `main`.
- Conventional Commits (`feat`, `fix`, `docs`, `refactor`, `test`, `chore`…).
- PR + revue avant merge ; CI verte obligatoire (`ARC-89`).
