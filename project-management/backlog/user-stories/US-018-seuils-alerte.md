# US-018: Seuils d'alerte paramétrables

## Métadonnées
- **ID**: US-018
- **EPIC**: EPIC-001
- **Sprint**: Sprint 10 (candidate 🟡 Should)
- **Statut**: ✅ Done (livré Sprint 10 — PR #51)
- **Points**: 3
- **Note tranche S10**: si capacité (fêtes), prioriser le **seuil de dérive de marge paramétrable par tenant** qui **remplace** l'implémentation par défaut d'US-072 (`MarginDriftThresholdProvider` / `DefaultMarginDriftThresholdProvider`, défaut 5 pts). Patron : entité tenant type `ReminderRule`. Les autres seuils (occupation, retard saisie) constituent des tranches ultérieures.
- **Persona**: ADMIN / P2 Marc (Chef de Projet)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-REF-26
- **Dépend de**: US-011 (référentiel profils taux), US-012 (calendriers absences)
- **Spec Technique**: EF-REF-26 (seuils d'alerte : dérive projet, sur/sous-occupation, retard saisie)

## User Story

**En tant qu'** administrateur tenant et chef de projet (P2 Marc),
**je veux** configurer des seuils d'alerte paramétrables pour la dérive budgétaire des projets, la sur/sous-occupation des ressources et le retard de saisie des temps,
**afin d'** être notifié proactivement dès qu'un indicateur critique dépasse son seuil, sans attendre un rapport de fin de semaine, et de prendre des actions correctives à temps.

## Critères d'Acceptation

### CA-1 (Nominal) : Alerte de dérive budgétaire projet à 80 % de consommation
```gherkin
GIVEN l'ADMIN configure un seuil d'alerte de dérive projet à 80 % du budget consommé pour le tenant
  AND le projet "ERP Refonte" a un budget de 500 jours / 350 000 €
WHEN les saisies de temps atteignent 400 jours consommés (80 % du budget)
THEN P2 Marc (chef de projet) et P6 Élodie (dirigeante) reçoivent une notification : "Projet 'ERP Refonte' : 80 % du budget consommé (400/500 jours). Action requise."
  AND le tableau de bord projet affiche le projet en surbrillance orange
  AND si la consommation atteint 95 %, une seconde alerte de niveau "critique" (rouge) est déclenchée
  AND les seuils 80 % et 95 % sont modifiables par l'ADMIN sans développement
```

### CA-2 (Nominal) : Alerte de sous-occupation d'un collaborateur
```gherkin
GIVEN l'ADMIN configure un seuil de sous-occupation à 70 % de taux d'occupation minimum
  AND le collaborateur "Lucas Bernard" a une capacité de 5 jours/semaine
  AND Lucas n'est affecté qu'à 3 jours de projets pour la semaine du 15/09 (60 % d'occupation)
WHEN le planning de la semaine du 15/09 est finalisé (J-5 avant le début de la semaine)
THEN P3 Sophie (resource manager) reçoit une alerte : "Lucas Bernard — sous-occupation prévue la semaine du 15/09 : 60 % (3/5 jours). 2 jours disponibles."
  AND l'alerte inclut un lien direct vers le planning de Lucas pour affecter les jours disponibles
  AND si le seuil est modifié à 60 %, l'alerte n'est plus déclenchée pour Lucas cette semaine
```

### CA-3 (Alternatif) : Alerte de retard de saisie des temps
```gherkin
GIVEN l'ADMIN configure un seuil de retard de saisie : alerte si une feuille de temps de la semaine N-1 n'est pas soumise avant le mercredi de la semaine N à 12h00
  AND P1 Camille n'a pas soumis sa feuille de temps de la semaine du 08/09 au vendredi 12/09
WHEN mercredi 17/09 à 12h00 arrive
THEN Camille reçoit un rappel automatique : "Rappel : votre feuille de temps de la semaine du 08/09 n'a pas encore été soumise."
  AND son manager (P2 Marc) reçoit une copie de l'alerte après 24h supplémentaires sans action (escalade automatique)
  AND si Camille soumet sa feuille de temps avant l'envoi au manager, aucune notification n'est envoyée à Marc
```

### CA-4 (Alternatif) : Tableau de bord des alertes actives pour l'ADMIN
```gherkin
GIVEN plusieurs alertes sont actives : 2 dérives projet, 3 sous-occupations, 5 retards de saisie
WHEN l'ADMIN accède au tableau de bord "Alertes actives"
THEN les alertes sont regroupées par catégorie (Projets / Ressources / Saisies) avec un compteur par catégorie
  AND chaque alerte affiche : objet concerné, valeur courante, seuil configuré, date de déclenchement, personnes notifiées
  AND l'ADMIN peut acquitter une alerte (avec note obligatoire) pour la masquer du tableau de bord actif
  AND les alertes acquittées restent dans le journal d'audit
```

### CA-5 (Erreur) : Configuration d'un seuil incohérent → validation et refus
```gherkin
GIVEN l'ADMIN tente de configurer un seuil de dérive projet à 110 % (supérieur à 100 %)
THEN le système refuse avec le message : "Un seuil de dérive budgétaire doit être compris entre 1 % et 100 %. La valeur 110 % est invalide."
  AND si l'ADMIN configure un seuil de sous-occupation à 0 %, le refus indique : "Un taux d'occupation minimum de 0 % ne déclencherait jamais d'alerte. Valeur minimum : 10 %."
  AND aucun seuil invalide n'est enregistré
```

### CA-6 (Erreur) : Seuil d'alerte Direction inférieur au seuil Chef de Projet → incohérence détectée et refus
```gherkin
GIVEN l'ADMIN a configuré un seuil d'alerte de dérive projet pour le Chef de Projet à 80 % du budget
WHEN l'ADMIN tente de configurer le seuil d'alerte pour la Direction à 70 % (inférieur au seuil Chef de Projet)
THEN le système refuse avec le message : "Incohérence détectée : le seuil Direction (70 %) est inférieur au seuil Chef de Projet (80 %). Le seuil Direction doit être supérieur ou égal au seuil Chef de Projet pour respecter la cascade d'escalade."
  AND aucun seuil n'est enregistré
  AND l'interface affiche les deux seuils en conflit côte à côte pour faciliter la correction par l'ADMIN
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

EF-REF-26 liste trois catégories d'alertes : dérive projet (budget temps ou budget financier), sur/sous-occupation des ressources, retard de saisie. Les seuils sont stockés dans une table `alert_threshold(tenant_id, alert_type, threshold_value, notification_targets, escalation_delay_hours)`.

Les notifications d'alerte utilisent le système de notification centralisé du tenant (email, in-app, configurable). Le mécanisme d'escalade (CA-3) réutilise la logique d'escalade configurée dans US-017 (circuits de validation).

Cette US configure les seuils : le calcul et l'émission des alertes au runtime relèvent des modules métier respectifs (PRO pour les projets, RES pour les ressources, RH pour les saisies). Cette séparation est intentionnelle pour respecter les responsabilités de module.
