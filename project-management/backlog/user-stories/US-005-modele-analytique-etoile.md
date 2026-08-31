# US-005: Modèle analytique en étoile et non-divergence

## Métadonnées
- **ID**: US-005
- **EPIC**: EPIC-000
- **Sprint**: Sprint 1 / Sprint 2
- **Statut**: 🔴 To Do
- **Points**: 8
- **Persona**: Équipe technique / P6 Élodie (Dirigeante)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: ADR-9, ARC-111, ARC-112, ARC-113, ARC-114, ARC-119, INV-2
- **Dépend de**: US-001, US-004
- **Spec Technique**: ADR-9 (schéma en étoile alimenté par événements), ARC-111-114 (projections analytiques), ARC-119 (non-divergence et réconciliation)

## User Story

**En tant que** membre de l'équipe technique et dirigeante (P6 Élodie),
**je veux** un modèle analytique en étoile alimenté exclusivement par projection d'événements de domaine, avec une commande de reconstruction complète, un test de non-divergence bloquant en CI et une réconciliation périodique en production déclenchant une alerte en cas d'écart,
**afin de** garantir que les indicateurs décisionnels (rentabilité, occupation, chiffre d'affaires) sont fiables, auditables, reproductibles et isolés par tenant, sans jamais dériver des données sources.

## Critères d'Acceptation

### CA-1 (Nominal) : Un indicateur calculé depuis le modèle étoile est reproductible à l'identique
```gherkin
GIVEN le tenant T-001 possède 3 projets avec des entrées de temps saisies sur la période du 01/08/2026 au 31/08/2026
  AND le modèle analytique (tables de faits et dimensions) a été alimenté par les projections d'événements de domaine
WHEN le tableau de bord de Élodie affiche le chiffre d'affaires réalisé pour août 2026
  AND l'équipe technique déclenche manuellement la commande de reconstruction complète du modèle analytique pour T-001
THEN après reconstruction, le chiffre d'affaires affiché pour août 2026 est strictement identique (à la décimale près) au chiffre calculé avant la reconstruction
  AND le test de non-divergence post-reconstruction passe avec 0 écart détecté
  AND la reconstruction ne produit aucune perte de données pour les autres tenants
```

### CA-2 (Nominal) : Le test de non-divergence bloque le build CI en cas d'écart
```gherkin
GIVEN une modification du code de projection introduit un bug de calcul (ex : un coefficient appliqué deux fois)
  AND un dataset de référence est disponible en base de données de test avec les résultats attendus
WHEN la pipeline CI exécute le job "non-divergence" qui compare les résultats du modèle analytique avec les agrégats calculés directement depuis les tables sources
THEN le job détecte un écart de 5 % sur l'indicateur "marge brute projet"
  AND le job se termine avec un code de sortie non nul et un rapport détaillant l'écart (indicateur, valeur attendue, valeur obtenue, delta)
  AND le build global est rouge et aucun merge n'est autorisé
  AND le rapport est publié en artefact de pipeline avec le périmètre exact de la divergence (tenant, période, indicateur)
```

### CA-3 (Alternatif) : La réconciliation périodique en production détecte un écart et déclenche une alerte
```gherkin
GIVEN la tâche de réconciliation périodique est planifiée toutes les 6 heures en production pour chaque tenant
  AND un événement de domaine n'a pas été rejoué correctement (bug de consommateur de message)
WHEN la tâche de réconciliation compare les agrégats du modèle analytique avec les recalculs à partir des tables sources pour T-002
THEN la tâche détecte un écart supérieur au seuil toléré (0,01 € ou 0,1 %) sur au moins un indicateur
  AND une alerte "analytical_model_divergence" est envoyée dans le canal de monitoring (Slack / PagerDuty) avec le détail de l'écart
  AND l'alerte contient : tenant_id, nom de l'indicateur, valeur modèle, valeur recalculée, delta absolu et delta relatif
  AND l'écart n'est pas corrigé automatiquement : une action humaine ou la commande de reconstruction est requise
```

### CA-4 (Alternatif) : Le modèle analytique porte le discriminant tenant et la double barrière
```gherkin
GIVEN le modèle analytique en étoile est déployé (tables de faits fact_project_revenue, fact_timesheet, dimensions dim_collaborator, dim_project, dim_period)
  AND chaque table de faits et chaque table de dimension portent une colonne tenant_id
WHEN une requête analytique est exécutée dans le contexte du tenant T-001
THEN le filtre ORM (ARC-33) ajoute automatiquement tenant_id = 'T-001' à toutes les jointures du modèle étoile
  AND le RLS PostgreSQL (ARC-34) bloque toute requête directe sur les tables analytiques sans paramètre de session app.current_tenant positionné
  AND un test d'intégration vérifie qu'une requête analytique cross-tenant retourne 0 lignes même si le filtre ORM est désactivé manuellement (RLS seul suffit)
```

### CA-5 (Erreur) : Reconstruction du modèle analytique sur un tenant sans événements → modèle vide cohérent
```gherkin
GIVEN le tenant "AgammaNew" (T-003) vient d'être créé et ne possède aucun événement de domaine
WHEN l'équipe technique déclenche la commande de reconstruction complète du modèle analytique pour T-003
THEN la commande se termine sans erreur (code de sortie 0)
  AND toutes les tables de faits pour T-003 sont vides (0 lignes)
  AND les tables de dimension pour T-003 contiennent uniquement les valeurs de référence initiales (calendrier, statuts par défaut)
  AND le test de non-divergence pour T-003 passe : 0 écart entre un modèle vide et des agrégats sources nuls
```

### CA-6 (Erreur — ARC-111) : Tentative d'écriture directe dans une table de faits hors canal événementiel → refus
```gherkin
GIVEN le projecteur analytique est le seul composant autorisé à écrire dans les tables de faits (fact_project_revenue, fact_timesheet) conformément à ARC-111
  AND un use case applicatif tente d'insérer directement une ligne dans fact_project_revenue sans publier d'événement de domaine
WHEN la requête INSERT est exécutée par le use case (en test d'intégration ou en production)
THEN l'opération est rejetée par la protection en base de données (trigger PostgreSQL ou contrainte applicative)
  AND le message d'erreur retourné est "Écriture directe interdite dans les tables analytiques : utiliser le canal événementiel"
  AND aucune ligne n'est insérée dans fact_project_revenue
  AND un événement "direct_write_attempt_rejected" est tracé dans les logs applicatifs avec : nom de la table cible, identifiant du composant appelant et horodatage
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

Conformément à ADR-9, le modèle analytique est alimenté exclusivement par des projections d'événements de domaine (Event Sourcing partiel). Aucune écriture directe depuis un use case applicatif dans les tables analytiques n'est tolérée : tout passe par la publication d'un événement et son traitement asynchrone par le projecteur analytique.

Conformément à ARC-119, la non-divergence est un invariant de production, pas seulement un test CI. La réconciliation périodique en production est une ligne de défense complémentaire au test de non-divergence CI.

La commande de reconstruction (ARC-114) doit être idempotente et pouvoir s'exécuter tenant par tenant sans impact sur les autres tenants ni sur la disponibilité de la plateforme (reconstruction en arrière-plan avec swap atomique des tables).

INV-2 impose que le modèle analytique soit la source unique de vérité pour tous les indicateurs décisionnels. Les APIs de reporting ne doivent jamais calculer à la volée depuis les tables transactionnelles en production.

Cette US est à cheval sur Sprint 1 (mise en place du schéma, des projections de base et du test CI) et Sprint 2 (réconciliation périodique en production, reconstruction atomique complète).
