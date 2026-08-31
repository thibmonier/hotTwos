# US-019: Ouverture de tenant et time-to-value < 15 minutes

## Métadonnées
- **ID**: US-019
- **EPIC**: EPIC-001
- **Sprint**: Sprint 1
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: ADMIN
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-REF-29, RG-REF-3, ENF-SAAS-2
- **Dépend de**: US-001 (fondation multi-tenant)
- **Spec Technique**: EF-REF-29 (paramètres par défaut, usage immédiat), RG-REF-3 (usage productif sans configuration préalable), ENF-SAAS-2 (SLA time-to-value)

## User Story

**En tant qu'** administrateur tenant nouvellement créé,
**je veux** qu'à la création de mon tenant, un ensemble de paramètres par défaut cohérents et opérationnels soit automatiquement configuré,
**afin de** pouvoir créer mon premier projet et saisir un premier temps en moins de 15 minutes, sans aucune configuration préalable, et commencer à utiliser HotOnes immédiatement après l'inscription.

## Critères d'Acceptation

### CA-1 (Nominal) : Tenant nouvellement créé — premier projet créé et temps saisi en < 15 min (critère SMART mesurable)
```gherkin
GIVEN un nouveau tenant "StartupAgile" est activé (sans aucune configuration manuelle préalable)
  AND l'ADMIN "alice@startupagilile.fr" se connecte pour la première fois
WHEN Alice suit le parcours guidé : (1) Créer un premier profil, (2) Créer un premier projet, (3) Saisir un temps
THEN Alice peut créer un profil "Développeur" en moins de 2 minutes grâce aux valeurs par défaut pré-remplies
  AND Alice peut créer un projet "Projet Demo" avec un client "Client Demo" en moins de 5 minutes
  AND Alice peut saisir 1 journée de temps sur ce projet en moins de 3 minutes
  AND la durée totale du parcours (activation → première saisie de temps soumise) est inférieure à 15 minutes
  AND cette durée est mesurée et journalisée automatiquement par le système pour monitoring SLA (ENF-SAAS-2)
```

### CA-2 (Nominal) : Paramètres par défaut prêts à l'emploi sans configuration
```gherkin
GIVEN un nouveau tenant "ConsultingRapide" est créé par la plateforme SaaS
WHEN l'ADMIN consulte le panneau de configuration immédiatement après activation
THEN les éléments suivants sont déjà configurés par défaut :
  - Devise de référence : EUR
  - Calendrier de travail : France — 5 jours × 7h, jours fériés de l'année civile en cours
  - Échelle de compétences : 4 niveaux (Débutant, Intermédiaire, Avancé, Expert)
  - Profil par défaut : "Consultant" avec taux de vente = 700 €/j et coût de revient = 0 €/j (à renseigner)
  - Statuts projet : Brouillon, En cours, Terminé, Archivé (avec transitions standard)
  - Client par défaut : "Client interne" (pour les projets internes)
  AND chaque valeur par défaut est modifiable par l'ADMIN à tout moment
  AND le premier accès affiche un bandeau "Configuration par défaut active — personnalisez selon vos besoins"
```

### CA-3 (Alternatif) : Wizard d'onboarding guidé pour personnaliser les paramètres clés
```gherkin
GIVEN un nouveau tenant est activé avec les valeurs par défaut
WHEN l'ADMIN choisit de lancer le wizard d'onboarding (optionnel, proposé au premier accès)
THEN le wizard guide l'ADMIN en 4 étapes : (1) Informations de la société, (2) Devise et calendrier, (3) Premiers profils, (4) Premier projet
  AND chaque étape est pré-remplie avec les valeurs par défaut et l'ADMIN peut valider en cliquant "Suivant" sans modification
  AND l'ADMIN peut quitter le wizard à n'importe quelle étape sans perdre les paramètres saisis précédemment
  AND à l'issue du wizard, le tenant est opérationnel avec les paramètres personnalisés ou par défaut selon les choix de l'ADMIN
```

### CA-4 (Alternatif) : Réinitialisation aux valeurs par défaut tenant (hors données utilisateurs)
```gherkin
GIVEN un tenant "TestConfig" a des expérimentations de configuration qui ont rendu le paramétrage incohérent
  AND aucune saisie de temps réelle ni projet productif n'a encore été créé
WHEN l'ADMIN demande la réinitialisation du paramétrage aux valeurs par défaut
THEN le système demande une confirmation explicite avec l'inventaire des éléments qui seront réinitialisés
  AND après confirmation, tous les paramètres de configuration (calendriers, profils par défaut, statuts) reviennent aux valeurs initiales
  AND aucune donnée utilisateur (comptes, projets créés, saisies de temps) n'est supprimée
  AND un événement d'audit enregistre la réinitialisation avec l'identifiant de l'ADMIN et le timestamp
```

### CA-5 (Erreur) : Création d'un tenant sans email valide → refus avant activation
```gherkin
GIVEN le processus d'activation d'un nouveau tenant requiert un email d'administrateur valide et vérifié
WHEN l'email "admin@" (format invalide) est soumis lors de la création du tenant
THEN le système refuse avec le message : "L'adresse email 'admin@' n'est pas valide. Saisissez une adresse email complète."
  AND le tenant n'est pas créé et aucun identifiant de tenant n'est généré
WHEN un email valide mais non vérifié est soumis (lien de confirmation non cliqué après 24h)
THEN le tenant est créé à l'état "En attente de vérification" et aucun accès n'est possible
  AND un email de relance est envoyé automatiquement après 24h si le lien de confirmation n'a pas été cliqué
```

### CA-6 (Erreur) : Dépassement du délai time-to-value de 15 min → journalisé comme échec SLA et alerte Customer Success
```gherkin
GIVEN un nouveau tenant "LenteConfig" est activé et l'ADMIN démarre le parcours guidé
  AND l'ADMIN effectue de nombreuses personnalisations manuelles et marque des pauses prolongées
WHEN le système détecte que la durée entre l'activation du tenant et la première saisie de temps soumise dépasse 15 minutes
THEN l'événement est journalisé comme "time_to_value_exceeded" avec la durée réelle (ex : 23 min 47 s) et l'identifiant du tenant (ENF-SAAS-2)
  AND la métrique SLA du tableau de bord plateforme est mise à jour et le tenant est comptabilisé hors objectif
  AND aucune action bloquante n'est déclenchée pour l'utilisateur (le dépassement est loggé, pas bloquant)
  AND une alerte interne est envoyée à l'équipe Customer Success pour proposer un accompagnement au tenant concerné
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

RG-REF-3 : le tenant doit être utilisable immédiatement après activation, sans configuration obligatoire. Les valeurs par défaut sont définies dans un fichier de seeds `tenant_defaults.yaml` versionné et appliqué via une commande `tenant:init` exécutée automatiquement à la création de chaque tenant.

ENF-SAAS-2 (SLA time-to-value) : la durée du parcours activation → première saisie est mesurée via des événements d'analytics (tenant_created, first_project_created, first_timesheet_submitted). L'objectif contractuel est < 15 minutes pour 90 % des nouveaux tenants.

Le critère CA-1 est un critère de performance fonctionnel mesurable (SMART). Un test de régression automatisé doit simuler le parcours complet et valider qu'il reste sous 15 minutes à chaque release.

Cette US dépend de US-001 (multi-tenant foundation) et préfigure le contenu du Sprint 1 en garantissant qu'un tenant vierge est immédiatement opérationnel. Les US-010 à US-016 enrichissent les paramètres par défaut, mais ne les bloquent pas.
