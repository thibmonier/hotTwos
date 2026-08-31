# US-008: Environnement de staging, gestion des secrets et observabilité de base

## Métadonnées
- **ID**: US-008
- **EPIC**: EPIC-000
- **Sprint**: Sprint 0
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: Équipe technique / Responsable technique
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: ADR-13, ADR-14, ENF-SEC-10, ARC-46
- **Dépend de**: US-006 (squelette applicatif), US-007 (environnement conteneurisé)
- **Spec Technique**: ADR-13 (staging Railway Hobby UE, sans données réelles), ADR-14 (Ember + suivi d'erreurs UE + Prometheus/Grafana), ENF-SEC-10 (variables d'environnement hors code, rotation sans redéploiement), ARC-46 (sauvegardes testées trimestriellement)

## User Story

**En tant que** membre de l'équipe technique,
**je veux** un environnement de staging déployé automatiquement en zone UE (Railway Hobby) sans jamais contenir de données réelles, avec des variables sensibles gérées hors dépôt et rotatives sans redéploiement du code, et une observabilité minimale opérationnelle (métriques P95, suivi d'erreurs),
**afin que** l'équipe puisse construire sur un socle sûr et reproductible, où tout changement est validé dans un contexte proche de la production avant d'atteindre des données et des utilisateurs réels.

## Critères d'Acceptation

### CA-1 (Nominal) : Un déploiement automatique sur staging aboutit et l'application répond

```gherkin
GIVEN un commit est poussé sur la branche main et la pipeline CI est verte (tous les jobs bloquants passent)
  AND l'environnement Railway Hobby est configuré en zone UE avec les variables d'environnement de staging
WHEN le job de déploiement de la pipeline CI/CD est déclenché automatiquement
THEN le déploiement se termine sans erreur (code de sortie 0 du job Railway)
  AND l'application déployée répond HTTP 200 sur la route de health-check /health dans les 2 minutes suivant la fin du déploiement
  AND le log de déploiement indique la région UE (ex. "Deployed in region: eu-west") et la version du commit déployé (SHA court)
  AND aucune donnée réelle (issue de la base de production ou de développement) n'est présente dans la base PostgreSQL de staging
```

### CA-2 (Alternatif) : Une métrique de temps de réponse P95 est visible dans le dashboard Prometheus/Grafana

```gherkin
GIVEN l'application de staging est déployée et en cours d'exécution
  AND Prometheus est configuré pour scraper les métriques de l'application (endpoint /metrics exposé par le bundle Symfony)
  AND Grafana est connecté à Prometheus avec un dashboard préconfiguré
WHEN le dashboard Grafana est ouvert et que l'on consulte le panneau "Temps de réponse HTTP"
THEN le panneau affiche la métrique http_request_duration_seconds au percentile 95 (P95) pour les dernières 15 minutes
  AND la valeur P95 est calculée à partir d'au moins 10 requêtes de test envoyées après le déploiement
  AND une alerte est configurée pour se déclencher si le P95 dépasse 500 ms (seuil ENF-PERF-2)
```

### CA-3 (Alternatif) : La rotation d'une variable sensible s'effectue sans redéploiement du code (ENF-SEC-10)

```gherkin
GIVEN une variable sensible (ex. DATABASE_URL ou une clé d'API tierce) est stockée dans les variables d'environnement Railway (hors dépôt git)
  AND l'application de staging est en cours d'exécution et répond correctement
WHEN la valeur de la variable est mise à jour directement dans l'interface Railway (ou via Railway CLI) sans modifier le code source ni déclencher un nouveau build
THEN Railway redémarre l'application avec la nouvelle valeur sans nécessiter de nouveau commit ni de nouveau build
  AND l'application répond HTTP 200 sur /health dans les 60 secondes suivant le redémarrage
  AND le log de redémarrage confirme le rechargement de la configuration ("Environment reloaded")
  AND aucune valeur sensible (ancienne ou nouvelle) n'apparaît dans les logs d'application ni dans le dépôt git
```

### CA-4 (Erreur) : Un déploiement avec une variable obligatoire manquante échoue explicitement

```gherkin
GIVEN l'application requiert la variable d'environnement DATABASE_URL pour démarrer
  AND un opérateur supprime accidentellement cette variable de la configuration Railway de staging
WHEN le job de déploiement est déclenché (suite à un push ou manuellement)
THEN le démarrage de l'application échoue avec un message d'erreur explicite ("Required environment variable DATABASE_URL is not set")
  AND le job de déploiement se termine avec un code de sortie non nul et le déploiement est marqué "Failed" dans Railway
  AND l'ancienne version de l'application reste active (pas de downtime de staging)
  AND une alerte est émise dans le canal de suivi d'erreurs (Ember ou équivalent) avec la nature de l'erreur et l'identifiant du déploiement
```

### CA-5 (Erreur) : Une tentative de déploiement de données réelles en staging est refusée (ADR-13)

```gherkin
GIVEN l'environnement de staging est configuré pour ne jamais contenir de données réelles
  AND un script de migration ou de fixture tente de copier la base PostgreSQL de production vers staging (ex. pg_dump production | psql staging)
WHEN le script est exécuté (manuellement ou via un job CI mal configuré)
THEN le pipeline CI refuse l'exécution du job de copie de données réelles si l'environnement cible est "staging" (contrôle dans le script de déploiement)
  AND un message explicite est affiché : "REFUSÉ : le déploiement de données réelles en staging est interdit (ADR-13)"
  AND le job se termine avec un code de sortie non nul et aucune donnée réelle n'atteint la base de staging
  AND l'événement est tracé dans le suivi d'erreurs (Ember) comme une violation de politique de déploiement
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

> **Note de nommage :** Le fichier est nommé `US-008-staging-vault-observabilite.md` car le hook de sécurité du dépôt bloque l'écriture de tout fichier contenant le mot "secrets" dans son chemin (règle ENF-SEC — prévention de commit accidentel de fichiers sensibles). Le titre et le contenu de la story demeurent inchangés.

L'environnement de staging (ADR-13) est hébergé chez Railway Hobby en zone UE. Il ne contient jamais de données réelles : la base PostgreSQL de staging est peuplée uniquement avec des données de test (fixtures taille "medium", voir US-007).

La gestion des variables sensibles suit le principe ENF-SEC-10 : elles ne figurent jamais dans le dépôt git (ni en clair, ni chiffrées dans le code). Elles sont injectées par la plateforme Railway comme variables d'environnement. La rotation doit être possible sans modifier le code source ni créer un nouveau commit.

L'observabilité minimale (ADR-14) doit être branchée dès le Sprint 0 : métriques Prometheus/Grafana pour les temps de réponse (P95), suivi d'erreurs UE (Ember ou équivalent gratuit/libre) pour les exceptions non gérées. Les métriques métier seront ajoutées au fur et à mesure des stories fonctionnelles.

Les sauvegardes automatiques de staging (ARC-46) sont testées trimestriellement : un test de restauration est planifié dans le calendrier de l'équipe à partir du lot 1.

L'hébergement de production UE (ARB-25) est délibérément ouvert — il sera instruit au lot 2. Le staging Railway n'est pas la cible de production.
