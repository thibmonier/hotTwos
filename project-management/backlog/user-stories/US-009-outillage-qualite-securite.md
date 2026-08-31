# US-009: Outillage qualité/sécurité automatisé et conventions de développement assisté

## Métadonnées
- **ID**: US-009
- **EPIC**: EPIC-000
- **Sprint**: Sprint 0
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: Équipe technique / Responsable technique
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: ADR-15, ADR-16, ARC-51, ARC-53, ARC-103, ARC-105, ARC-106, ARC-108, ENF-SEC-11, ENF-MAINT-1
- **Dépend de**: US-006 (squelette applicatif)
- **Spec Technique**: ADR-15 (8 couches de sécurité outillées), ADR-16 (claude-craft, TDD imposé, conventions versionnées ARC-103/105/106/108), ARC-51 (tolérance zéro dépréciations), ARC-53 (Rector automatisé), ENF-SEC-11 (vulnérabilités critiques corrigées sous 15 jours), ENF-MAINT-1 (couverture tests ≥ 80 %)

## User Story

**En tant que** membre de l'équipe technique,
**je veux** un outillage qualité et sécurité opérationnel (PHPStan niveau max, Rector, détecteur de secrets, composer audit, scanner de conteneurs), des conventions de développement versionnées à la racine du dépôt comme contrat entre l'équipe et l'agent IA, et des hooks pré-commit bloquants,
**afin que** l'équipe puisse construire sur un socle sûr et reproductible, où la qualité et la sécurité du code sont vérifiées automatiquement à chaque contribution et où le développement assisté par agent (claude-craft) opère dans un cadre défini et non délégué (ARC-106).

## Critères d'Acceptation

### CA-1 (Nominal) : L'outillage qualité/sécurité s'exécute localement et rapporte un état complet

```gherkin
GIVEN le squelette applicatif (US-006) est en place et l'environnement de développement (US-007) est démarré
  AND l'outillage est installé : PHPStan, Rector, composer-audit, un détecteur de secrets (ex. detect-secrets ou gitleaks), un scanner de conteneurs
WHEN le développeur exécute la commande make quality (ou un script équivalent documenté dans le README)
THEN chaque outil s'exécute et retourne un statut individuel (PASS / FAIL) dans la sortie terminal
  AND la commande se termine avec le code de sortie 0 si tous les outils passent, non nul si au moins un échoue
  AND le rapport final récapitule le résultat de chacune des 8 couches de sécurité (ADR-15) : PHPStan, Deptrac, style/PSR-12, couverture, isolation multi-tenant, mode worker, audit dépréciations, audit dépendances/secrets/conteneurs
  AND la durée totale d'exécution de make quality est documentée (objectif : < 3 minutes en local)
```

### CA-2 (Alternatif) : PHPStan niveau maximum passe sans erreur sur le squelette initial

```gherkin
GIVEN PHPStan est configuré au niveau maximum (level: max dans phpstan.neon) avec le mode "bleedingEdge" activé
  AND le squelette applicatif contient uniquement du code de démonstration correctement typé
WHEN la commande vendor/bin/phpstan analyse src/ est exécutée
THEN PHPStan se termine avec le code de sortie 0 ("No errors")
  AND le rapport PHPStan ne contient aucune erreur, ni warning non supprimé
  AND la configuration phpstan.neon versionnée interdit l'utilisation de l'annotation @phpstan-ignore-next-line sans justification documentée dans un commentaire adjacent
```

### CA-3 (Alternatif) : Une dépréciation PHP/Symfony détectée est corrigée automatiquement par Rector (ARC-53)

```gherkin
GIVEN Rector est configuré avec les sets de règles Symfony (SetList::SYMFONY_60, SYMFONY_70, SYMFONY_80) et PHP 8.4
  AND un développeur introduit volontairement un appel à une fonction PHP dépréciée (ex. str_contains avec un argument null non typé déprécié)
WHEN la commande vendor/bin/rector process src/ est exécutée en mode correction (sans --dry-run)
THEN Rector détecte la dépréciation et modifie automatiquement le code source pour la corriger
  AND la commande se termine avec le code de sortie 0 et affiche le nombre de fichiers modifiés
  AND le code corrigé passe PHPStan niveau max sans nouvelle erreur
  AND une exécution en mode --dry-run préalable affiche la transformation prévue sans modifier les fichiers
```

### CA-4 (Erreur) : Un secret introduit dans le code est détecté et le commit est bloqué

```gherkin
GIVEN le hook git pre-commit est installé et configuré pour utiliser le détecteur de secrets (detect-secrets ou gitleaks)
  AND la configuration du détecteur couvre les patterns courants : clés AWS (AKIA...), tokens JWT, mots de passe en dur, DSN de base de données avec credentials
WHEN un développeur tente de commiter un fichier PHP contenant une variable $apiKey = "sk-prod-abcdef123456" (pattern de clé d'API réelle)
THEN le hook pre-commit intercepte le commit et le bloque avec un code de sortie non nul
  AND le message d'erreur identifie le fichier, la ligne et le type de secret détecté ("Secret detected: API key pattern in src/Service/DemoService.php:12")
  AND le commit n'est pas créé (git log ne montre pas de nouveau commit)
  AND le même contrôle est reproduit en CI (job "Secrets detection" dans la pipeline GitHub Actions) pour les cas où le hook local aurait été bypassé
```

### CA-5 (Erreur) : Une vulnérabilité critique de dépendance rend le build rouge (ENF-SEC-11)

```gherkin
GIVEN la commande composer audit (ou un équivalent integré à la CI) est configurée comme étape bloquante de la pipeline
  AND une dépendance Composer ou une image Docker du projet présente une vulnérabilité de sévérité CRITICAL ou HIGH selon la base CVE
WHEN la pipeline CI/CD est déclenchée (push ou pull request)
THEN le job d'audit des dépendances se termine avec un code de sortie non nul
  AND le rapport d'audit identifie la dépendance vulnérable, le numéro CVE, la sévérité et la version corrigée disponible
  AND le build global est rouge et aucun merge sur main n'est possible tant que la vulnérabilité n'est pas corrigée ou justifiée par une exception documentée
  AND si la vulnérabilité reste non corrigée après 15 jours (ENF-SEC-11), une alerte automatique est envoyée dans le canal de suivi de l'équipe
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

Les conventions de développement (ADR-16, ARC-105) sont versionnées à la racine du dépôt sous forme de fichiers de configuration (`.claude/CLAUDE.md`, `CONVENTIONS.md` ou équivalent) et constituent le contrat formel entre l'équipe et l'agent IA (claude-craft). Elles précisent notamment :

- **ARC-103** : chaque test porte dans son nom l'identifiant de la règle de gestion testée (ex. `testRG042_ImputationImmuable`).
- **ARC-105** : les conventions sont versionnées et toute modification passe par une pull request revue par l'équipe.
- **ARC-106** : le périmètre de sécurité (isolation multi-tenant, autorisation, gestion des secrets) n'est jamais délégué à l'agent IA sans relecture humaine systématique.
- **ARC-108** : les tests sont écrits depuis l'exigence (RG-*), pas depuis l'implémentation.

L'outillage qualité installe les 8 couches de sécurité définies par ADR-15 :
1. Analyse statique (PHPStan max + taint analysis)
2. Contrôle architectural (Deptrac)
3. Style et formatage (PHP-CS-Fixer / PSR-12)
4. Couverture de tests (≥ 80 %, ENF-MAINT-1)
5. Tests d'isolation multi-tenant
6. Tests en configuration worker
7. Audit des dépréciations (Rector --dry-run en CI, ARC-51/53)
8. Audit des dépendances et détection de secrets (composer audit, gitleaks, scanner conteneurs)

Les deux risques majeurs identifiés dans ADR-15 — fuite inter-tenant et exposition IA — ne sont pas détectables par les analyseurs statiques ; ils nécessitent des tests manuels dédiés et un test d'intrusion annuel externe. Cette US ne couvre que l'outillage automatisé.
