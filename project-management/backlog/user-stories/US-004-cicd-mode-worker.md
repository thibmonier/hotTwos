# US-004: Chaîne CI/CD et exécution en mode worker

## Métadonnées
- **ID**: US-004
- **EPIC**: EPIC-000
- **Sprint**: Sprint 0
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: Équipe technique
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: ADR-12, ADR-2, ARC-47, ARC-50, ARC-51, ARC-61, ENF-MAINT-1, ENF-MAINT-2
- **Dépend de**: US-006 (squelette applicatif), US-007 (environnement conteneurisé)
- **Spec Technique**: ADR-2 (mode worker PHP), ADR-12 (GitHub Actions pipeline), ARC-47 (aucun état entre requêtes), ARC-50/51 (isolation tenant par requête), ARC-61 (tolérance zéro dépréciations)

> Note : la pipeline est installée au Sprint 0 ; l'étape « tests d'isolation multi-tenant » se remplit avec US-001 (Sprint 1).

## User Story

**En tant que** membre de l'équipe technique,
**je veux** une chaîne CI/CD à étapes bloquantes (analyse statique PHPStan niveau max, contrôle des dépendances architecturales Deptrac, couverture de tests ≥ 80 %, tests d'isolation multi-tenant, tests en configuration worker, tolérance zéro dépréciations) et que le serveur d'application s'exécute en mode worker PHP sans conserver aucun état entre les requêtes,
**afin de** garantir que seul du code de qualité et sûr est mis en production, et que les bugs liés aux états résiduels entre requêtes (fuites de tenant, variables globales) sont détectés automatiquement avant tout merge.

## Critères d'Acceptation

### CA-1 (Nominal) : Un build vert est requis avant tout merge sur main
```gherkin
GIVEN une pull request est ouverte sur la branche main du dépôt
  AND la pipeline GitHub Actions est configurée comme "required status check" pour la branche main
WHEN tous les jobs de la pipeline passent (PHPStan, Deptrac, tests unitaires ≥ 80 % de couverture, tests d'intégration multi-tenant, tests en mode worker, audit dépréciations)
THEN la pull request peut être mergée (le bouton merge est déverrouillé)
  AND le rapport de couverture est publié en artefact de pipeline pour consultation
  AND la durée totale de la pipeline ne dépasse pas 10 minutes (gate de performance ENF-MAINT-2)
```

### CA-2 (Nominal) : Un test worker détecte une fuite d'état entre deux requêtes consécutives
```gherkin
GIVEN un test d'intégration simule deux requêtes HTTP successives dans la même instance worker PHP
  AND la première requête positionne le tenant courant sur T-001 (via le middleware de contexte)
  AND un bug fictif oublie de réinitialiser le contexte en fin de requête
WHEN la seconde requête arrive pour le tenant T-002
THEN le test détecte que le contexte tenant est encore T-001 en début de traitement de la seconde requête
  AND le test échoue avec le message "State leak detected: tenant context not reset between requests"
  AND le build est rouge et aucun merge n'est possible tant que la fuite n'est pas corrigée
```

### CA-3 (Alternatif) : PHPStan niveau max bloque les violations de typage et les chemins non sûrs
```gherkin
GIVEN un développeur ajoute une méthode retournant mixed sans déclaration de type de retour explicite
  AND il ouvre une pull request
WHEN la pipeline GitHub Actions exécute le job PHPStan au niveau maximum (level 9+)
THEN PHPStan émet une erreur sur la méthode non typée
  AND le job PHPStan se termine avec un code de sortie non nul
  AND le build est rouge, la pull request ne peut pas être mergée
  AND le rapport d'erreurs PHPStan est disponible en annotation sur la pull request
```

### CA-4 (Erreur) : Une dépréciation PHP ou Symfony détectée → build rouge
```gherkin
GIVEN le code source contient un appel à une fonction marquée @deprecated dans PHP 8.5 ou dans la version de Symfony utilisée
WHEN la pipeline exécute le job d'audit des dépréciations (Symfony DebugClassLoader + Rector --dry-run)
THEN le job se termine avec un code de sortie non nul indiquant la présence d'au moins une dépréciation
  AND le message d'erreur identifie précisément le fichier, la ligne et la dépréciation détectée
  AND le build global est rouge (tolérance zéro dépréciations, ARC-61)
  AND un commentaire automatique est posté sur la pull request avec la liste des dépréciations à corriger
```

### CA-5 (Erreur) : Deptrac détecte une violation de couplage architectural
```gherkin
GIVEN la règle Deptrac interdit tout import du namespace Infrastructure dans le namespace Domain
  AND un développeur ajoute par erreur un use App\Infrastructure\Persistence\DoctrineProjectRepository dans une classe de domaine
WHEN la pipeline exécute le job Deptrac
THEN Deptrac émet une violation "Domain must not depend on Infrastructure"
  AND le job se termine avec un code de sortie non nul
  AND le build est rouge, empêchant le merge
  AND le rapport Deptrac liste la violation avec le chemin de dépendance complet (classe source → classe cible)
```

### CA-6 (Alternatif) : Alternance de tenants sur un même worker — isolation vérifiée en mode séquentiel
```gherkin
GIVEN la pipeline est configurée pour exécuter les tests d'intégration en configuration worker (max_requests=50)
  AND un test séquentiel simule 10 requêtes alternant entre les tenants T-001 et T-002 sur la même instance worker PHP
  AND chaque requête impaire cible T-001 et chaque requête paire cible T-002
WHEN toutes les requêtes sont traitées et le contexte tenant est correctement réinitialisé entre chaque requête (ARC-50, ARC-51)
THEN le test se termine avec succès (code de sortie 0)
  AND chaque réponse ne contient que des données appartenant au tenant de la requête correspondante (0 ligne cross-tenant)
  AND le rapport de test confirme que la réinitialisation du contexte a eu lieu exactement 10 fois sur 10 requêtes
  AND le build reste vert et la pull request peut être mergée
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

Conformément à ADR-2, le serveur d'application s'exécute en mode worker PHP (FrankenPHP ou équivalent). Chaque requête doit remettre à zéro intégralement le contexte tenant (ARC-50) et les variables globales ou statiques (ARC-47). La durée de vie du worker ne doit pas introduire de mémoire résiduelle.

Conformément à ARC-51, un test dédié « requêtes consécutives sur le même worker » vérifie la réinitialisation complète du contexte entre deux appels, indépendamment de l'ordre des tenants.

La couverture de 80 % est un plancher obligatoire (ENF-MAINT-1). Les modules critiques de sécurité (authentification, habilitation, isolation tenant) doivent viser 100 % de couverture de branches.

La pipeline doit être configurée pour ne pas permettre les merges en cas d'échec d'un seul job (GitHub branch protection : require all status checks to pass).
