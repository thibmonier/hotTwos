# US-072: Budget vs réalisé et alerte de dérive financière

## Métadonnées
- **ID**: US-072
- **EPIC**: EPIC-005 (Finance & rentabilité)
- **Sprint**: Sprint 9
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: P2 (Marc — chef de projet), P6 (Directeur financier)
- **Créé le**: 2026-09-04
- **Mis à jour**: 2026-09-04

## Traçabilité
- **Implémente**: EF-FIN (budget prévisionnel vs réalisé), OBJ-2 (lecture de la dérive financière en complément de la dérive charge), OBJ-6 (écart marge mi-projet vs clôture ≤ 5 pts)
- **Dépend de**: US-071 (marge réelle), US-033 (budget / charge / montant projet, EPIC-002), US-036 (atterrissage & détection de dérive — dérive de charge existante)
- **Complète**: US-036 (dérive de **charge**) par la dérive **financière** (marge/CA)

## User Story

**En tant que** chef de projet (Marc) et directeur financier (P6),
**je veux** comparer le **budget prévisionnel** d'un projet à son **réalisé valorisé** (coût, CA, marge) et être **alerté d'une dérive** dès qu'elle dépasse un seuil,
**afin de** réagir **avant la clôture** plutôt que de constater la dérive a posteriori.

## Critères d'Acceptation

### CA-1 (Nominal) : Comparaison budget vs réalisé par projet

```gherkin
GIVEN le projet "Refonte app" a un budget défini (US-033) : coût cible 40 000 €, CA cible 60 000 € (marge cible 20 000 €, 33 %)
  AND le réalisé valorisé à date est : coût 30 000 €, CA reconnu 42 000 € (marge 12 000 €, 28,6 %)
WHEN un utilisateur habilité consulte le suivi budgétaire du projet
THEN il voit, côte à côte, le budget cible et le réalisé à date pour : coût, CA, marge, taux de marge
  AND l'écart (absolu et %) est affiché pour chaque indicateur
  AND la consommation budgétaire est exprimée en % (coût réalisé / coût cible = 75 %)
```

### CA-2 (Nominal) : Alerte de dérive financière au-delà d'un seuil

```gherkin
GIVEN le seuil de dérive de marge est configuré à 5 points (OBJ-6) pour le tenant (paramétrable, US-018)
  AND le taux de marge cible d'un projet est 33 % et son taux de marge réel est 26 %
WHEN le suivi budgétaire est recalculé (après valorisation d'une période)
THEN une alerte de dérive financière est levée pour le projet : "Dérive de marge : −7 pts (réel 26 % vs cible 33 %)"
  AND l'alerte est visible dans le tableau de bord finance et sur la fiche projet
  AND l'alerte distingue la dérive financière (marge) de la dérive de charge (US-036)
```

### CA-3 (Contrainte) : Écart marge mi-projet vs clôture maîtrisé (OBJ-6)

```gherkin
GIVEN un projet suivi tout au long de son exécution
WHEN on compare la marge estimée à mi-projet à la marge figée à la clôture
THEN l'écart est mesuré et exposé comme indicateur de qualité de pilotage
  AND l'objectif OBJ-6 (écart ≤ 5 pts) est vérifiable a posteriori sur les projets clôturés
```

### CA-4 (Erreur) : Projet sans budget — comparaison impossible, sans blocage

```gherkin
GIVEN un projet interne ou non budgété (pas de budget défini dans US-033)
WHEN un utilisateur consulte son suivi budgétaire
THEN le réalisé valorisé (coût, CA, marge) est affiché normalement
  BUT la comparaison au budget et l'alerte de dérive sont désactivées avec le message "Aucun budget défini pour ce projet"
  AND un lien vers la définition du budget (US-033) est proposé aux rôles habilités
```

## Critères UI/UX

### Web
- Le suivi budgétaire réutilise les composants du tableau de bord finance (gating coût HAB-1, format centimes).
- Les écarts sont colorés (vert = favorable, rouge = défavorable) avec un libellé explicite (pas seulement une couleur — a11y WCAG, cf. US-065).
- Les alertes de dérive sont regroupées dans un bandeau/section dédiée, triées par gravité.

### Mobile
- Consommation budgétaire (%) et alerte de dérive (oui/non) visibles en tête de fiche projet.
- Détail des écarts par indicateur réservé au desktop.

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | À décomposer (`/project:decompose-tasks 009`) | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Seuil de dérive paramétrable (réutilise US-018 si disponible, sinon paramètre tenant)
- [ ] Distinction dérive financière / dérive de charge (US-036)
- [ ] Gating HAB-1 · `make ci` vert · revue de clôture

---

## Notes

**Positionnement vs US-036** : US-036 traite la dérive de **charge** (RAF, atterrissage). US-072 ajoute la dérive **financière** (marge/CA), en s'appuyant sur la marge réelle d'US-071 et le budget d'US-033. Éviter toute duplication de la logique d'alerte : mutualiser le mécanisme de seuil/alerte si US-036 en fournit déjà un.

**Seuil de dérive** : idéalement porté par le référentiel des seuils d'alerte (US-018). Si US-018 n'est pas livré, prévoir un paramètre tenant par défaut (ex. 5 points, aligné OBJ-6), surchargeable.
