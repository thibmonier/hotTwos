# US-006: Squelette applicatif Symfony 8 + FrankenPHP worker + architecture en couches

## Métadonnées
- **ID**: US-006
- **EPIC**: EPIC-000
- **Sprint**: Sprint 0
- **Statut**: ✅ Done (livré Sprint 2)
- **Points**: 8
- **Persona**: Équipe technique / Responsable technique
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: ADR-1, ADR-2, ADR-3, ADR-4, ADR-5, ADR-8, ARC-15, ARC-16, ARC-17, ARC-18
- **Dépend de**: —
- **Spec Technique**: ADR-1 (monolithe modulaire API-first), ADR-2 (FrankenPHP worker), ADR-3 (Symfony 8.1, zéro dépréciations), ADR-4 (API Platform 4.3.x DTO strict), ADR-5 (Twig + Stimulus + Turbo + Reprise/Vite), ADR-8 (Clean Architecture + DDD + Deptrac), ARC-15..18 (adaptateurs web/API/CLI, DTO obligatoires)

## User Story

**En tant que** membre de l'équipe technique,
**je veux** un projet Symfony 8.1 initialisé avec une arborescence `src/` organisée en couches (Domain / Application / Infrastructure) et ses trois adaptateurs (web, API, CLI), FrankenPHP configuré en mode worker, Deptrac garant des frontières, API Platform exposant uniquement des DTO de démonstration et un front Twig + Turbo minimal,
**afin que** l'équipe puisse construire sur un socle sûr et reproductible, où chaque couche a des responsabilités claires et où toute violation d'architecture est détectée automatiquement avant le merge.

## Critères d'Acceptation

### CA-1 (Nominal) : Une route de démonstration servie en mode worker répond HTTP 200

```gherkin
GIVEN le projet Symfony 8.1 est initialisé et FrankenPHP est lancé en mode worker
  AND une route GET /demo est déclarée dans l'adaptateur web (Controller Symfony)
  AND la route invoque un cas d'usage du layer Application qui retourne une réponse DTO
WHEN une requête GET /demo est envoyée à l'instance FrankenPHP worker
THEN la réponse HTTP est 200 OK
  AND le corps de la réponse contient un payload JSON conforme au DTO de démonstration (champ "status": "ok")
  AND le log FrankenPHP confirme le mode worker ("worker mode: true") dans la sortie standard
  AND la durée de traitement de la réponse est inférieure à 200 ms (première requête à chaud exclue)
```

### CA-2 (Alternatif) : Un cas d'usage est invocable en CLI sans serveur HTTP (ARC-17)

```gherkin
GIVEN le projet Symfony est démarré en mode console uniquement (sans FrankenPHP)
  AND une commande Symfony console app:demo:run est déclarée dans l'adaptateur CLI
  AND la commande appelle le même cas d'usage Application que la route HTTP /demo
WHEN la commande php bin/console app:demo:run est exécutée en ligne de commande
THEN la commande se termine avec le code de sortie 0
  AND la sortie standard affiche le résultat du cas d'usage (ex. "Démonstration : ok")
  AND aucun composant HTTP n'est instancié (pas de Request, pas de Response Symfony)
  AND le test phpunit vérifiant cet adaptateur CLI passe avec code 0
```

### CA-3 (Alternatif) : Deptrac valide les frontières architecturales sur le squelette

```gherkin
GIVEN Deptrac est configuré avec les règles de frontières : Domain ne dépend de rien, Application dépend uniquement de Domain, Infrastructure dépend de Application et Domain
  AND le squelette initial ne contient aucune violation délibérée
WHEN la commande vendor/bin/deptrac analyse est exécutée sur le répertoire src/
THEN Deptrac se termine avec le code de sortie 0 ("0 violations found")
  AND le rapport Deptrac liste les couches Domain, Application et Infrastructure avec leurs règles de dépendance
  AND le rapport confirme que les trois adaptateurs (web, API, CLI) sont isolés dans la couche Infrastructure
```

### CA-4 (Erreur) : Une violation de frontière Deptrac rend le build rouge

```gherkin
GIVEN Deptrac est configuré et la règle interdit tout import du namespace App\Infrastructure dans le namespace App\Domain
  AND un développeur ajoute par erreur un use App\Infrastructure\Persistence\DemoRepository dans une classe de domaine
WHEN la commande vendor/bin/deptrac analyse est exécutée (localement ou en CI)
THEN Deptrac émet une violation "Domain must not depend on Infrastructure"
  AND la commande se termine avec un code de sortie non nul
  AND le rapport détaille le chemin de dépendance complet (fichier source, ligne, classe cible)
  AND le job CI correspondant est rouge et aucun merge sur main n'est possible tant que la violation subsiste
```

### CA-5 (Erreur) : L'exposition directe d'une entité Doctrine via l'API est refusée (ARC-18)

```gherkin
GIVEN API Platform 4.3.x est configuré en mode DTO strict (aucune ressource API déclarée directement sur une entité Doctrine)
  AND un développeur tente de déclarer l'attribut #[ApiResource] directement sur une entité Doctrine (ex. DemoEntity)
WHEN la commande php bin/console cache:clear (ou le chargement des métadonnées API Platform) est exécutée
THEN Symfony ou API Platform lève une exception de configuration explicite signalant que l'entité ne peut pas être exposée directement comme ressource API
  AND le message d'erreur indique clairement qu'un DTO d'entrée/sortie est obligatoire (ARC-18)
  AND un test phpunit qui tente de charger cette configuration invalide échoue avec ce même message, rendant le build rouge
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

Le squelette est la fondation de toutes les stories suivantes. Il doit être établi avant toute story de fonctionnalité métier.

ADR-1 impose trois adaptateurs distincts (web, API, CLI) — chacun dans un répertoire dédié sous `src/Infrastructure/` — sans logique métier. Toute règle de gestion vit dans la couche `Application` (cas d'usage) ou `Domain` (entités et value objects).

ADR-4 interdit formellement l'exposition des entités Doctrine via API Platform (ARC-18). Chaque endpoint API Platform est porté par un DTO d'entrée et un DTO de sortie explicitement typés, avec un provider et un processor sur mesure.

ADR-8 délègue à Deptrac la vérification des frontières à chaque exécution de la CI. La configuration Deptrac doit être versionnée à la racine du projet (`deptrac.yaml`) et inclure toutes les couches définies, y compris les sous-modules métier à venir.

Symfony Reprise (expérimental 0.x) est retenu pour le build des assets (ADR-5, ARC-60) ; le front Twig + Turbo est délibérément minimal au Sprint 0 — une seule page de démonstration suffit.
