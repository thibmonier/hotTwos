# US-019: Onboarding tenant — défauts prêts à l'emploi & mise en route guidée

## Métadonnées
- **ID**: US-019
- **EPIC**: EPIC-001
- **Sprint**: 16
- **Statut**: 🟢 Ready
- **Points**: 5
- **Persona**: P-ADMIN (administrateur tenant nouvellement créé)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-09-07 (affinage S16 — recadrage défauts + checklist ; hors SLA/analytics/wizard)

## Traçabilité
- **Implémente**: EF-REF-29 (paramètres par défaut, usage immédiat), RG-REF-3 (usage productif sans configuration préalable)
- **Dépend de**: US-001 (multi-tenant), US-010 (org), US-011 (profils/taux), **US-012** (fériés), **US-013** (échelle compétences), US-016 (devise EUR par défaut, livrée)
- **Réutilise** : `Application\Authorization\InitializeDefaultRoles` (rôles par défaut, existant), la commande de seed de démo (`app:demo:seed`) comme référence, l'enum `ProjectStatus` (statuts déjà disponibles par construction).
- **Reporté (hors périmètre)** : mesure/monitoring **SLA time-to-value** & analytics, **wizard** multi-étapes, **réinitialisation** aux défauts, **vérification email** (relève de l'auth US-002) → US ultérieures.

## User Story

**En tant qu'** administrateur d'un tenant nouvellement créé,
**je veux** qu'un ensemble de **paramètres par défaut cohérents** soit provisionné automatiquement et qu'une **checklist de mise en route** me guide,
**afin de** créer mon premier projet et saisir un premier temps **immédiatement, sans configuration préalable**.

## Contexte (Conversation)
Aujourd'hui, seuls les rôles par défaut sont provisionnés (`InitializeDefaultRoles`). Cette US ajoute un
**provisioning de défauts idempotent** (`tenant:init`) et une **checklist d'onboarding** affichée à la
première connexion. Objectif EF-REF-29 : un tenant vierge est **opérationnel sans paramétrage
obligatoire** (RG-REF-3). La mesure de la performance « < 15 min » (ENF-SAAS-2) est **reportée** (pas
d'analytics dans ce sprint) ; l'US garantit ici l'*usage immédiat*, condition nécessaire.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : un tenant fraîchement initialisé est opérationnel sans configuration
```gherkin
GIVEN un nouveau tenant vient d'être initialisé (tenant:init exécuté)
WHEN l'administrateur se connecte pour la première fois
THEN il peut créer un projet et saisir un temps SANS aucune étape de configuration préalable obligatoire
  AND les défauts nécessaires (rôles, profil par défaut, client interne, devise EUR, échelle de compétences, fériés de l'année en cours) sont déjà présents
```

### CA-2 (Nominal) : les défauts provisionnés sont cohérents et modifiables
```gherkin
GIVEN un tenant initialisé
WHEN l'administrateur consulte le paramétrage juste après activation
THEN sont présents par défaut : rôles standard, un profil « Consultant » (taux à renseigner),
     un client « Client interne », la devise de référence EUR, l'échelle de compétences 4 niveaux,
     les jours fériés de l'année civile en cours
  AND chacun de ces éléments est modifiable par l'administrateur
```

### CA-3 (Alternatif) : checklist de mise en route à la première connexion
```gherkin
GIVEN un tenant initialisé sans activité
WHEN l'administrateur accède au tableau de bord pour la première fois
THEN une checklist affiche les étapes : (1) Vérifier le profil par défaut, (2) Créer un premier projet, (3) Saisir un premier temps
  AND chaque étape franchie est cochée automatiquement (ex. « premier projet créé »)
  AND la checklist disparaît / se replie une fois toutes les étapes accomplies
```

### CA-4 (Alternatif) : provisioning idempotent (rejouable sans doublon)
```gherkin
GIVEN un tenant déjà initialisé (défauts présents)
WHEN tenant:init est exécuté à nouveau pour ce tenant
THEN aucun doublon n'est créé (profil/client/échelle/fériés existants conservés)
  AND l'opération se termine sans erreur (idempotence)
```

### CA-5 (Erreur) : initialisation d'un tenant inexistant → refus explicite
```gherkin
GIVEN aucun tenant n'existe pour l'identifiant fourni
WHEN tenant:init est invoqué avec cet identifiant
THEN l'opération échoue avec un message explicite (« Tenant introuvable »)
  AND aucun élément par défaut n'est créé
```

### CA-6 (Erreur) : la checklist et le provisioning respectent l'isolation multi-tenant
```gherkin
GIVEN deux tenants A et B initialisés
WHEN on consulte la checklist et les défauts du tenant A
THEN seuls les éléments du tenant A sont visibles (RLS) ; jamais ceux de B
  AND l'avancement de la checklist de A est indépendant de celui de B
```

## Notes techniques (pour la décomposition)
- **Service/commande** `tenant:init` (`Application\Onboarding\InitializeTenantDefaults` + commande console) : orchestre `InitializeDefaultRoles` (existant) + création idempotente : profil par défaut (`Pricing\Profile`), client interne (`Client`), devise EUR (US-016), échelle de compétences (US-013), fériés année en cours (US-012).
- **Idempotence** : chaque défaut vérifié avant création (upsert) — rejouable.
- **Checklist** : état d'onboarding par tenant (ex. `TenantOnboarding` `TenantOwned`, ou dérivé de l'existence projet/temps) + bandeau/encart Twig sur le dashboard (gating : administrateur). Étapes cochées par requêtes de comptage (projets, saisies).
- **Statuts projet** : déjà disponibles via l'enum `ProjectStatus` (aucun provisioning nécessaire).
- **Tests** : unit (idempotence, tenant inexistant) + fonctionnels (dashboard checklist, isolation, parcours création projet→temps sans config) ; ajouter les entités aux SchemaTool.
- **Deptrac/RLS** : provisioning via ports du Domaine ; toute nouvelle table `TenantOwned` + RLS.

## Definition of Ready
- [x] Description INVEST recadrée (défauts + checklist ; SLA/wizard/reset reportés)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Estimation 5 pts confirmée
- [x] Dépendances explicites (US-012/013/016 ; réutilise InitializeDefaultRoles) — **à ordonnancer en dernier** dans le sprint
- [x] Impact multi-tenant/RLS et gating explicités

## Definition of Done
- [ ] CA validés (unit + fonctionnels), `make ci` vert (PHPStan max, Deptrac, couv. ≥ 80 %)
- [ ] `tenant:init` idempotent ; migration + RLS pour toute nouvelle table ; code review

---

## Notes
La mesure du **time-to-value < 15 min** (ENF-SAAS-2, analytics `tenant_created`/`first_project_created`/
`first_timesheet_submitted`) est **reportée** à une US d'observabilité dédiée. Cette US garantit la
**condition** (usage immédiat sans config) et la mise en route guidée. Le provisioning s'appuie sur les
référentiels livrés plus tôt dans le sprint (US-012 fériés, US-013 échelle), d'où l'ordre d'exécution
« onboarding en dernier ».
