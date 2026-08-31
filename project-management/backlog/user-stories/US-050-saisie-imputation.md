# US-050: Saisie d'imputation hebdomadaire et quotidienne

## Métadonnées
- **ID**: US-050
- **EPIC**: EPIC-003
- **Sprint**: Sprint 1
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: P1 (Camille — collaborateur)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-TMP-1 (maille paramétrable, journée type ≤30 s), EF-TMP-2 (vue hebdomadaire + quotidienne rapide sur mêmes données), EF-TMP-4 (commentaire optionnel par ligne), EF-TMP-5 (duplication du jour ou de la semaine précédente), RG-TMP-1 (imputation seulement sur projet ouvert et affecté, ouverture tracée si dérogatoire), RG-TMP-2 (plafond journalier paramétrable, justification obligatoire si dépassé)
- **Dépend de**: US-001 (socle multi-tenant), US-003 (RBAC/HAB)
- **Spec Technique**: EF-TMP-1, EF-TMP-2, EF-TMP-4, EF-TMP-5, RG-TMP-1, RG-TMP-2

## User Story

**En tant que** collaborateur (Camille),
**je veux** saisir mes imputations de temps sur mes projets de la semaine en mode hebdomadaire ou quotidien, avec possibilité de dupliquer une semaine ou un jour précédent et d'ajouter un commentaire,
**afin de** déclarer mon activité complète et exacte en un temps minimal tout en respectant les contraintes de plafond et d'affectation.

## Critères d'Acceptation

### CA-1 (Nominal) : Journée type sur 2 projets saisie en ≤ 30 secondes

```gherkin
GIVEN Camille est connectée et affectée aux projets P-Alpha et P-Beta (statut "En cours")
  AND la maille de saisie est paramétrée à "journée" (défaut)
  AND aucune imputation n'existe pour la date du jour
WHEN elle ouvre la vue de saisie du jour
  AND saisit 4h sur P-Alpha et 4h sur P-Beta sans commentaire
  AND valide la saisie
THEN les deux imputations sont enregistrées avec les durées exactes et la date du jour
  AND le temps total de la manipulation est inférieur ou égal à 30 secondes, mesuré de l'ouverture de la vue à la confirmation du serveur
  AND la vue récapitulative affiche le total journalier de 8h
```

### CA-2 (Alternatif) : Duplication de la semaine précédente

```gherkin
GIVEN Camille a saisi des imputations valides sur la semaine du 17 au 21 août 2026
  AND la semaine courante du 24 au 28 août 2026 est vide
WHEN elle sélectionne l'action "Dupliquer la semaine précédente"
  AND confirme l'action dans la boîte de dialogue de confirmation
THEN les imputations de la semaine du 17-21 août sont reproduites à l'identique (projet, durée, maille, commentaire) sur la semaine du 24-28 août
  AND les imputations dupliquées restent modifiables avant soumission finale
  AND un message indique "Semaine dupliquée depuis le 17/08/2026 – vérifiez et ajustez avant de soumettre"
```

### CA-3 (Alternatif) : Ajout d'un commentaire optionnel sur une ligne d'imputation

```gherkin
GIVEN Camille saisit une imputation de 3h sur le projet P-Gamma le 25/08/2026
WHEN elle clique sur l'icône "Commentaire" de la ligne et saisit "Réunion de lancement avec le client"
  AND valide la saisie
THEN l'imputation est enregistrée avec le commentaire associé
  AND le commentaire est visible dans la vue détaillée de la ligne et dans l'export du chef de projet
```

### CA-4 (Erreur) : Imputation sur un projet non affecté refusée avec trace d'ouverture dérogatoire

```gherkin
GIVEN Camille n'est pas affectée au projet P-Delta (statut "En cours")
WHEN elle tente de saisir 2h sur P-Delta via la vue de saisie (projet absent de sa liste)
  AND un administrateur tente forcer l'imputation via l'API sans ouverture tracée préalable
THEN l'interface ne propose pas P-Delta dans la liste des projets disponibles (filtrage HAB-5 à la source)
  AND l'API retourne 403 Forbidden avec le message "Projet P-Delta non affecté à ce collaborateur"
  AND aucune imputation n'est créée en base
  AND si une ouverture dérogatoire est créée par un rôle habilité, elle est tracée avec auteur, date, justification et durée de validité
```

### CA-5 (Erreur) : Dépassement du plafond journalier — justification obligatoire

```gherkin
GIVEN le plafond journalier est paramétré à 10 heures pour le tenant
  AND Camille a déjà saisi 9h sur la journée du 26/08/2026
WHEN elle tente d'ajouter 2h supplémentaires portant le total à 11h
THEN le système affiche une alerte "Plafond journalier de 10h dépassé — une justification est requise"
  AND la saisie est bloquée tant que le champ "Motif de dépassement" (255 caractères max) n'est pas renseigné
  AND après saisie du motif et validation, l'imputation est enregistrée avec le motif joint et un indicateur "dépassement justifié"
  AND un événement "plafond_journalier_depassé" est loggé avec l'identité du collaborateur, la date et le motif
```

## Critères UI/UX

### Web
- La vue hebdomadaire présente une grille jours × projets avec saisie directe dans les cellules (pas de modale intermédiaire pour la saisie d'une durée standard).
- La duplication (jour ou semaine) est accessible en un clic depuis la vue courante, sans navigation.
- Le champ commentaire est masqué par défaut et s'affiche à la demande par ligne (icône discrète) pour ne pas surcharger la vue.
- Le total journalier et hebdomadaire est recalculé en temps réel à chaque saisie.

### Mobile
- Sur écran ≤ 480 px, la vue bascule automatiquement en mode quotidien (voir US-052).
- La duplication et le commentaire restent accessibles via menu contextuel longpress ou swipe sur la ligne.

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

**RG-TMP-1** : la liste des projets proposés à l'imputation est filtrée à la source (HAB-5) — seuls les projets ouverts et pour lesquels le collaborateur possède une affectation active sont visibles. L'ouverture dérogatoire (accès temporaire à un projet non affecté) doit être tracée par un rôle habilité et comporter une durée de validité explicite.

**RG-TMP-2** : le plafond journalier (valeur par défaut : 10 h) est paramétrable par tenant et par catégorie de collaborateur. La justification obligatoire en cas de dépassement est conservée dans le journal d'audit.

**EF-TMP-5** : la duplication copie la structure (projet, durée, maille, commentaire) mais ne soumet pas automatiquement — le collaborateur doit confirmer après ajustements éventuels.
