# US-013: Référentiel de compétences structuré

## Métadonnées
- **ID**: US-013
- **EPIC**: EPIC-001
- **Sprint**: Sprint 2
- **Statut**: 🔴 To Do
- **Points**: 3
- **Persona**: ADMIN / P3 Sophie (Resource Manager)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-REF-10, EF-REF-11, RG-REF-1
- **Dépend de**: US-001 (fondation multi-tenant)
- **Spec Technique**: EF-REF-10 (catégories de compétences), EF-REF-11 (échelle de niveau paramétrable)

## User Story

**En tant qu'** administrateur tenant et resource manager (P3 Sophie),
**je veux** structurer le référentiel de compétences en catégories (technique, fonctionnel, méthodologique, linguistique, sectoriel) avec une échelle de niveau paramétrable,
**afin de** qualifier précisément les profils des collaborateurs, faciliter le staffing des projets sur des critères objectifs et maintenir la cohérence du référentiel dans le temps.

## Critères d'Acceptation

### CA-1 (Nominal) : Création de compétences par catégorie et association à des collaborateurs
```gherkin
GIVEN l'ADMIN a créé les catégories : Technique, Fonctionnel, Méthodologique, Linguistique, Sectoriel
  AND l'échelle de niveau par défaut est à 4 niveaux : Débutant, Intermédiaire, Avancé, Expert
WHEN l'ADMIN crée la compétence "React.js" dans la catégorie Technique
  AND P3 Sophie associe "React.js" au collaborateur "Pierre Martin" avec le niveau "Avancé"
THEN "React.js" apparaît dans le profil de Pierre Martin avec le niveau "Avancé"
  AND le référentiel liste la compétence avec sa catégorie et le nombre de collaborateurs qui la possèdent (1)
  AND la recherche de collaborateurs par compétence "React.js" retourne Pierre Martin
```

### CA-2 (Nominal) : Modification de l'échelle de niveaux sans perte de données existantes
```gherkin
GIVEN l'échelle de niveaux est configurée à 4 niveaux : Débutant (1), Intermédiaire (2), Avancé (3), Expert (4)
  AND 8 collaborateurs ont des compétences évaluées avec ces niveaux (dont 3 à "Avancé = 3")
WHEN l'ADMIN modifie l'échelle pour ajouter un niveau "Notions (0)" en début d'échelle (nouvelle valeur 1 à 5)
THEN les 8 associations collaborateur/compétence existantes sont conservées avec leur niveau d'origine
  AND le système affiche un avertissement : "La modification de l'échelle peut désaligner les niveaux existants. Vérifiez la cohérence."
  AND les 3 collaborateurs à "Avancé" restent à "Avancé" (pas de réindexation automatique silencieuse)
  AND le référentiel affiché indique la nouvelle échelle dès la prochaine connexion de Sophie
```

### CA-3 (Alternatif) : Recherche multi-critères de collaborateurs disponibles avec une compétence
```gherkin
GIVEN le référentiel contient les compétences : "Java" (Technique), "Scrum" (Méthodologique), "Finance" (Sectoriel)
  AND P3 Sophie recherche un collaborateur disponible maîtrisant Java niveau ≥ Avancé + Scrum niveau ≥ Intermédiaire
WHEN Sophie lance la recherche combinée depuis le module de staffing
THEN la liste des résultats affiche uniquement les collaborateurs répondant aux deux critères simultanément
  AND chaque résultat indique le niveau pour chaque compétence et la disponibilité calendaire (en heures libres sur la période)
  AND le filtre peut être affiné par catégorie supplémentaire sans perte des critères précédents
```

### CA-4 (Alternatif) : Désactivation d'une compétence obsolète sans suppression des historiques
```gherkin
GIVEN la compétence "Lotus Notes" (Technique) est référencée dans les profils de 2 anciens collaborateurs
  AND aucun collaborateur actif ne possède cette compétence
WHEN l'ADMIN désactive la compétence "Lotus Notes" (conformément à RG-REF-1)
THEN "Lotus Notes" n'apparaît plus dans les listes de saisie pour les nouveaux profils
  AND les 2 profils historiques conservent la mention de la compétence en lecture seule
  AND une recherche avancée peut inclure les compétences désactivées si l'option est cochée
```

### CA-5 (Erreur) : Création d'une compétence en doublon → refus avec suggestion
```gherkin
GIVEN la compétence "JavaScript" existe déjà dans la catégorie Technique
WHEN l'ADMIN tente de créer une compétence "javascript" (casse différente) dans la catégorie Technique
THEN le système refuse avec le message "Une compétence similaire existe déjà : 'JavaScript' (Technique). Voulez-vous utiliser celle-ci ?"
  AND aucune nouvelle compétence n'est créée
  AND l'ADMIN peut choisir d'utiliser l'existante ou de forcer la création avec un nom différent et explicite
```

### CA-6 (Erreur) : Réduction de l'échelle de niveaux en dessous de niveaux déjà attribués → refus
```gherkin
GIVEN l'échelle de niveaux est configurée à 4 niveaux : Notions (1), Intermédiaire (2), Avancé (3), Expert (4)
  AND 5 collaborateurs ont des compétences évaluées au niveau "Expert (4)"
WHEN l'ADMIN tente de réduire l'échelle à 3 niveaux maximum (supprimant le niveau 4)
THEN le système refuse l'opération avec le message "Impossible de réduire l'échelle à 3 niveaux : 5 association(s) utilisent le niveau 4 (Expert). Désattribuez ces niveaux avant de modifier l'échelle."
  AND l'échelle reste à 4 niveaux
  AND la liste des 5 collaborateurs concernés est affichée pour permettre une correction manuelle
  AND aucune association compétence/collaborateur n'est modifiée ou supprimée automatiquement
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

P3 Sophie (Resource Manager) est le persona principal consommateur du référentiel de compétences pour le staffing. L'ADMIN en est le gestionnaire. P5 (DRH / Responsable RH) peut également gérer ce référentiel selon la configuration des rôles RBAC du tenant.

EF-REF-11 : l'échelle par défaut à 4 niveaux doit être pré-configurée à la création du tenant (US-019). La valeur de l'échelle est stockée dans la configuration tenant et non en dur dans le code.

La recherche combinée de compétences (CA-3) est un prérequis pour les fonctionnalités de staffing avancé. Cette US pose la fondation référentielle, le moteur de recherche est adressé dans les US du module RES.
