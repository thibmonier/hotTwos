# US-035: Avancement physique et reste à faire

## Métadonnées
- **ID**: US-035
- **EPIC**: EPIC-002
- **Sprint**: 3
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: P2 (Marc – Chef de projet)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-PRJ-12, EF-PRJ-13, INV-4, RG-PRJ-2, RG-PRJ-3
- **Dépend de**: US-001 (socle multitenant), US-003 (socle authentification), US-030 (création projet), US-031 (lots et jalons), US-033 (budget charge/montant)
- **Spec Technique**: PRJ-12 – Avancement physique et RAF

## User Story

**En tant que** Marc, chef de projet,
**je veux** saisir manuellement un pourcentage d'avancement physique et un reste à faire (RAF) indépendants de la consommation réelle,
**afin de** refléter l'état d'avancement réel de la production sans être trompé par les heures consommées, et de détecter les dérives budgétaires avant qu'il ne soit trop tard.

## Critères d'Acceptation

### CA-1 (Nominal): Avancement physique jamais déduit de la consommation
```gherkin
GIVEN le lot L2 "Développement" a un budget de 80 j
  AND 40 j ont été imputés par les développeurs (50 % du budget)
WHEN Marc consulte la fiche du lot L2
THEN l'avancement physique affiché est "Non saisi" ou la dernière valeur saisie manuellement
  AND le système n'affiche JAMAIS automatiquement "50 % d'avancement" déduit des 40 j consommés
  AND un message invite Marc à saisir l'avancement physique : "Avancement à mettre à jour"
```

### CA-2 (Alternatif): RAF et budget restant affichés côte à côte
```gherkin
GIVEN le lot L2 "Développement" a un budget initial de 80 j
  AND 40 j ont été consommés
  AND Marc a saisi un RAF de 50 j (le développement est plus complexe que prévu)
WHEN Marc consulte la vue d'avancement du lot L2
THEN la vue affiche en parallèle :
     Budget initial : 80 j
     Consommé : 40 j
     Budget restant (calculé) : 80 – 40 = 40 j
     RAF saisi : 50 j
     Écart RAF / budget restant : +10 j (dérive détectée)
  AND cet écart est mis en évidence visuellement (couleur orange/rouge)
```

### CA-3 (Alternatif): RAF initial d'un lot = budget du lot (RG-PRJ-3)
```gherkin
GIVEN le lot L1 "Analyse" vient d'être créé avec un budget de 40 j
  AND aucune imputation n'a encore été enregistrée
  AND aucun RAF n'a encore été saisi
WHEN Marc consulte la vue d'avancement du lot L1
THEN le RAF affiché est 40 j (égal au budget du lot)
  AND la mention "RAF initial = budget (non encore réestimé)" est affichée
```

### CA-4 (Alternatif): RAF diverge du budget restant dès la première réestimation (RG-PRJ-3)
```gherkin
GIVEN le lot L1 "Analyse" a un budget de 40 j et 10 j consommés
  AND le RAF initial était 40 j (= budget)
WHEN Marc saisit une première réestimation du RAF à 35 j
THEN le RAF passe à 35 j
  AND le budget restant reste à 30 j (40 – 10)
  AND l'atterrissage prévu = 10 + 35 = 45 j (dépassement de 5 j)
  AND la mention "RAF réestimé le 2026-10-15 par Marc Dupont" apparaît
```

### CA-5 (Alternatif): Saisie de l'avancement physique par lot
```gherkin
GIVEN le lot L2 "Développement" existe et aucun avancement n'a été saisi
WHEN Marc saisit un avancement physique de 65 % avec la date de mise à jour 2026-11-01
THEN l'avancement physique affiché est 65 % daté du 2026-11-01
  AND l'historique conserve toutes les saisies précédentes d'avancement
  AND la courbe d'avancement sur le temps est mise à jour
  AND si Marc tente de saisir un avancement < à la valeur précédente, un avertissement est affiché (mais pas bloquant)
```

### CA-6 (Erreur): Avancement physique hors bornes [0 %, 100 %] refusé (INV-4)
```gherkin
GIVEN le lot L2 "Développement" existe et est en cours
WHEN Marc tente de saisir un avancement physique de 110 %
THEN la saisie est refusée avec le message "L'avancement physique doit être compris entre 0 % et 100 % (INV-4)"
  AND aucune valeur d'avancement n'est enregistrée
  AND le champ revient à la dernière valeur valide saisie ou à "Non saisi"
```

### CA-7 (Erreur): RAF négatif ou avancement déduit automatiquement de la consommation refusé (RG-PRJ-2, INV-4)
```gherkin
GIVEN le lot L2 "Développement" a 40 j consommés et un budget de 80 j
  AND aucun RAF n'a été saisi manuellement
WHEN Marc tente de saisir un RAF de -5 j
     OU un processus tente d'enregistrer un RAF calculé automatiquement égal à (budget – consommé)
THEN l'opération est refusée avec le message "Le RAF doit être saisi manuellement et être ≥ 0 j — il ne peut pas être déduit de la consommation budgétaire (RG-PRJ-2, INV-4)"
  AND le système n'enregistre aucune valeur de RAF
  AND Marc est invité à saisir le RAF manuellement via le formulaire de réestimation
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

INV-4 : l'avancement physique est une donnée indépendante de la consommation — il ne peut pas être calculé automatiquement depuis le ratio consommé/budget. RG-PRJ-2 : principe fondamental, l'avancement physique n'est JAMAIS déduit de la consommation budgétaire. RG-PRJ-3 : à la création d'un lot, le RAF initial = budget du lot. Dès la première réestimation manuelle, les deux valeurs (RAF et budget restant) divergent et sont suivies séparément. EF-PRJ-12 : l'avancement physique (%) est saisi par le chef de projet par lot ou par jalon. EF-PRJ-13 : le RAF est une saisie indépendante du budget restant arithmétique.
