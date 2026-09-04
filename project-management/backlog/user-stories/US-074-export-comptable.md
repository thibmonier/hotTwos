# US-074: Export comptable configurable (réserve)

## Métadonnées
- **ID**: US-074
- **EPIC**: EPIC-005 (Finance & rentabilité)
- **Sprint**: Sprint 9 (**réserve** — Could, si capacité)
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: P6 (Directeur financier / contrôleur de gestion)
- **Créé le**: 2026-09-04
- **Mis à jour**: 2026-09-04

## Traçabilité
- **Implémente**: EF-FIN-22 (export vers la comptabilité — format configurable, sans donnée perdue), OBJ-3 (reporting financier automatisé)
- **Dépend de**: US-071 (marge réelle), US-073 (consolidation)
- **Note périmètre**: l'EF-FIN-22 est notée « hors périmètre direct » dans EPIC-005 → traitée ici comme **réserve** de sprint.

## User Story

**En tant que** directeur financier (P6),
**je veux** **exporter** les données de rentabilité figées (CA reconnu, coût, marge par projet/client/période) dans un **format configurable** (CSV/comptable),
**afin de** réconcilier avec la comptabilité sans ressaisie et garantir une traçabilité opposable.

## Critères d'Acceptation

### CA-1 (Nominal) : Export d'une période clôturée

```gherkin
GIVEN la période "Novembre 2026" est clôturée et ses marges sont figées (US-071)
WHEN un directeur financier exporte la rentabilité de la période
THEN un fichier est généré contenant, par projet (et/ou client) : période, CA reconnu, coût valorisé, marge, taux de marge
  AND chaque ligne est traçable (identifiant projet/client, période) et opposable
  AND aucune donnée n'est perdue ou tronquée (montants en centimes, pas d'arrondi destructeur)
```

### CA-2 (Nominal) : Format configurable

```gherkin
GIVEN plusieurs formats d'export sont proposés (ex. CSV séparateur `;`, colonnes ordonnables)
WHEN l'utilisateur choisit un format et lance l'export
THEN le fichier respecte le format demandé (encodage, séparateur, entêtes)
  AND le format retenu est mémorisable comme préférence tenant (paramétrable)
```

### CA-3 (Habilitation) : Réservé finance/direction (HAB-1)

```gherkin
GIVEN un utilisateur sans rôle finance/direction
WHEN il tente d'exporter la rentabilité
THEN l'export est refusé (deny-by-default)
  AND tout export réussi par un rôle habilité est tracé (auteur, périmètre, date — HAB-6)
```

### CA-4 (Erreur) : Export d'une période non clôturée

```gherkin
GIVEN une période non clôturée (marges provisoires)
WHEN un utilisateur tente d'exporter cette période
THEN l'export est refusé ou explicitement marqué "provisoire — non opposable"
  AND l'utilisateur est invité à clôturer la période (US-057) pour un export figé
```

## Critères UI/UX

### Web
- Bouton d'export accessible depuis le tableau de bord finance consolidé (US-073), avec choix du format et du périmètre (période/client).
- Retour utilisateur clair (génération en cours / fichier prêt) ; téléchargement serveur (pas de calcul front).

### Mobile
- Hors périmètre mobile (fonction desktop de contrôle de gestion).

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | À décomposer (`/project:decompose-tasks 009`) | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Traçabilité opposable de l'export · gating HAB-1/HAB-6
- [ ] `make ci` vert · revue de clôture

---

## Notes

**Réserve de sprint** : à prendre uniquement si US-071/072/073 sont terminées avant la fin du sprint. Sinon reportée au prochain incrément d'EPIC-005.

**Interface comptable réelle** (connecteur vers un logiciel comptable) : hors périmètre — ici on produit un fichier configurable, pas une intégration directe.
