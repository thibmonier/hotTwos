# US-032: Projets internes non facturables

## Métadonnées
- **ID**: US-032
- **EPIC**: EPIC-002
- **Sprint**: 2
- **Statut**: 🔴 To Do
- **Points**: 3
- **Persona**: P2 (Marc – Chef de projet), ADMIN
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-PRJ-5, RG-PRJ-6
- **Dépend de**: US-001 (socle multitenant), US-003 (socle authentification), US-030 (création projet)
- **Spec Technique**: PRJ-5 – Projets internes

## User Story

**En tant qu'** administrateur ou chef de projet,
**je veux** qualifier un projet comme "interne" lors de sa création ou de sa modification,
**afin que** le temps passé sur ce projet soit comptabilisé dans la capacité consommée de l'équipe sans affecter les indicateurs de marge commerciale.

## Critères d'Acceptation

### CA-1 (Nominal): Projet interne exclu des calculs de marge
```gherkin
GIVEN le projet PRJ-INT-001 "Formation interne IA" est qualifié "Interne" (non facturable)
  AND 5 jours ont été imputés par deux consultants
WHEN Élodie consulte le tableau de bord de la marge commerciale du mois
THEN les 5 jours imputés sur PRJ-INT-001 n'apparaissent pas dans le calcul de marge
  AND aucun chiffre d'affaires ni coût de vente n'est associé à ce projet dans les agrégats financiers
```

### CA-2 (Alternatif): Projet interne inclus dans la capacité consommée
```gherkin
GIVEN le projet PRJ-INT-001 "Formation interne IA" est qualifié "Interne"
  AND le consultant Jean a imputé 5 jours sur ce projet en août 2026
WHEN Sophie consulte le plan de charge de Jean pour août 2026
THEN les 5 jours sur PRJ-INT-001 apparaissent dans la capacité consommée de Jean
  AND le taux d'occupation de Jean intègre ces 5 jours
  AND la mention "Projet interne" est clairement indiquée dans la vue
```

### CA-3 (Alternatif): Qualification "interne" visible et distincte dans les listes
```gherkin
GIVEN plusieurs projets existent dont PRJ-INT-001 "Formation interne IA" qualifié "Interne"
WHEN Marc consulte la liste de tous les projets
THEN PRJ-INT-001 affiche un badge "Interne" distinctif
  AND un filtre "Type : Interne / Client" permet de trier la liste
```

### CA-4 (Erreur): Tentative de facturation sur un projet interne refusée
```gherkin
GIVEN le projet PRJ-INT-001 est qualifié "Interne"
WHEN le responsable facturation tente de créer une facture sur PRJ-INT-001
THEN l'action est bloquée avec le message "Facturation impossible : projet interne non facturable (EF-PRJ-5)"
  AND aucune facture n'est créée
```

### CA-5 (Alternatif): Requalification d'un projet interne en projet client tracée
```gherkin
GIVEN le projet PRJ-INT-001 est qualifié "Interne" avec 3 jours déjà imputés
WHEN ADMIN requalifie le projet en "Client" en renseignant un motif
THEN le projet devient facturable à partir de la date de requalification
  AND les imputations antérieures restent marquées "Interne – hors marge"
  AND la requalification est tracée : auteur, date, motif
```

### CA-6 (Erreur): Inclusion d'un projet interne dans le calcul de marge commerciale refusée
```gherkin
GIVEN le projet PRJ-INT-001 "Formation interne IA" est qualifié "Interne"
WHEN ADMIN tente de l'ajouter manuellement au périmètre du rapport de marge commerciale via l'interface de configuration des rapports
THEN le système refuse avec le message "Les projets internes ne peuvent pas être inclus dans le calcul de marge commerciale (RG-PRJ-6)"
  AND PRJ-INT-001 reste absent du rapport de marge
  AND aucune ligne de chiffre d'affaires ni de coût de vente n'est créée pour ce projet dans les agrégats financiers
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

RG-PRJ-6 : les projets internes sont exclus du calcul de marge commerciale mais inclus dans la capacité consommée. EF-PRJ-5 : le flag "interne" est positionnable à la création ou modifiable par ADMIN avec traçabilité. Les projets internes typiques : formation, R&D, avant-vente, administration.
