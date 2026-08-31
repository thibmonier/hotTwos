# US-017: Statuts et circuits de validation paramétrables

## Métadonnées
- **ID**: US-017
- **EPIC**: EPIC-001
- **Sprint**: Sprint 2
- **Statut**: 🔴 To Do
- **Points**: 8
- **Persona**: ADMIN
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-REF-24, EF-REF-25, RG-REF-1
- **Dépend de**: US-001 (fondation multi-tenant)
- **Spec Technique**: EF-REF-24 (statuts et transitions sans développement), EF-REF-25 (circuits de validation : valideurs, seuils, délégation, escalade)

## User Story

**En tant qu'** administrateur tenant,
**je veux** paramétrer librement les statuts, les transitions autorisées et les circuits de validation (valideurs, seuils financiers, délégation, escalade) de n'importe quel objet métier (devis, projet, feuille de temps, commande) sans recourir à un développement,
**afin de** adapter le workflow de validation à l'organisation et aux contraintes légales ou commerciales du tenant, et de les faire évoluer en autonomie totale.

## Critères d'Acceptation

### CA-1 (Nominal) : Ajout d'un statut "En attente juridique" sans développement
```gherkin
GIVEN le workflow des devis est configuré avec les statuts : Brouillon → En validation → Approuvé → Refusé
WHEN l'ADMIN ajoute le statut "En attente juridique" avec les transitions autorisées : (depuis "En validation") et (vers "En validation" ou "Approuvé")
THEN le nouveau statut apparaît immédiatement dans le workflow des devis sans redéploiement de l'application
  AND les utilisateurs habilités peuvent faire passer un devis vers "En attente juridique" depuis "En validation"
  AND les devis existants en statut "En validation" ne sont pas automatiquement migrés vers le nouveau statut
  AND le diagramme des transitions dans l'interface admin reflète le nouveau statut et ses arcs entrants/sortants
```

### CA-2 (Nominal) : Devis > 50 000 € requiert double validation, seuil modifiable
```gherkin
GIVEN le circuit de validation des devis est configuré avec : valideur N+1 requis pour tout montant
  AND un seuil de double validation est fixé à 50 000 € (Direction + Commercial Senior)
WHEN P4 Yann soumet un devis de 62 000 €
THEN le circuit de validation déclenche deux notifications : une pour le manager N+1 et une pour le Commercial Senior
  AND le devis ne peut passer à "Approuvé" qu'après les deux validations (AND logique, pas OR)
  AND si l'ADMIN modifie le seuil à 75 000 €, un devis de 62 000 € ne déclenche plus la double validation à la prochaine soumission
  AND la modification du seuil n'affecte pas les devis déjà en cours de validation (ils conservent leur circuit d'origine)
```

### CA-3 (Alternatif) : Délégation de validation — valideur absent
```gherkin
GIVEN P2 Marc est configuré comme valideur principal pour les feuilles de temps de son équipe
  AND Marc est absent du 14/07 au 25/07 et a configuré une délégation vers "Sophie (P3)" pour cette période
WHEN un collaborateur de l'équipe de Marc soumet une feuille de temps le 16/07
THEN la notification de validation est envoyée à Sophie (délégataire) et non à Marc
  AND Sophie dispose des mêmes droits de validation que Marc pour les objets délégués
  AND après le 25/07, les notifications reviennent automatiquement à Marc
  AND les validations effectuées par Sophie sont tracées avec la mention "par délégation de Marc"
```

### CA-4 (Alternatif) : Escalade automatique après délai de validation dépassé
```gherkin
GIVEN le circuit de validation des feuilles de temps configure un délai d'escalade de 48h ouvrées
  AND une feuille de temps est soumise le lundi 01/09 à 09h00 et aucune action n'est prise
WHEN le délai de 48h ouvrées est dépassé (jeudi 04/09 à 09h00)
THEN une notification d'escalade est automatiquement envoyée au responsable du valideur (N+2)
  AND le valideur initial (N+1) reçoit également un rappel indiquant que l'escalade a été déclenchée
  AND le statut de la feuille de temps affiche "En attente validation (escaladé)" pour traçabilité
  AND N+2 peut valider directement sans intervention de N+1
```

### CA-5 (Erreur) : Création d'une transition circulaire ou vers un statut terminal → refus
```gherkin
GIVEN le statut "Approuvé" est configuré comme statut terminal (aucune transition sortante)
WHEN l'ADMIN tente d'ajouter une transition de "Approuvé" vers "Brouillon" sur le workflow des devis
THEN le système refuse avec le message : "Le statut 'Approuvé' est configuré comme terminal. Retirez le marqueur 'terminal' avant d'ajouter une transition sortante."
  AND aucune transition n'est créée
  AND si l'ADMIN tente de créer une boucle "En validation" → "En validation" (transition sur soi-même), le système refuse avec : "Les transitions auto-référentielles ne sont pas autorisées."
```

### CA-6 (Erreur) : Activation d'un circuit de validation sans aucun valideur défini → refus
```gherkin
GIVEN un circuit de validation "Validation feuilles de temps équipe RH" est créé avec ses seuils et ses transitions
  AND aucun valideur (ni utilisateur nominatif, ni rôle, ni groupe) n'a été associé à ce circuit
WHEN l'ADMIN tente d'activer ce circuit de validation pour le module des feuilles de temps
THEN le système refuse avec le message : "Le circuit 'Validation feuilles de temps équipe RH' ne peut pas être activé : aucun valideur n'est défini. Associez au moins un valideur avant l'activation."
  AND le circuit reste en état "Brouillon" et n'est pas appliqué au module des feuilles de temps
  AND le formulaire de configuration du circuit est affiché avec la section "Valideurs" mise en évidence visuellement
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

Le moteur de workflow est implémenté comme un state machine générique piloté par la configuration tenant (tables `workflow_state`, `workflow_transition`, `validation_circuit`). Ce moteur est utilisé par tous les modules métier (DEV, PRO, RH, FAC).

EF-REF-24 impose que la configuration des statuts et transitions soit possible sans redéploiement. Les changements de workflow s'appliquent aux nouveaux objets (et aux objets existants qui passent à une nouvelle transition). Les objets existants conservent leur statut courant jusqu'à ce qu'une action les fasse évoluer.

EF-REF-25 : les circuits de validation supportent des conditions multiples (montant, type de client, entité juridique). La délégation (CA-3) et l'escalade (CA-4) sont des invariants non négociables pour couvrir les situations d'indisponibilité des valideurs.

Cette US est un prérequis pour toutes les stories qui implémentent des flux de validation (feuilles de temps, congés, devis, commandes).
