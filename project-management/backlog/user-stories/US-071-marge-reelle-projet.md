# US-071: Moteur de marge réelle par projet à la clôture

## Métadonnées
- **ID**: US-071
- **EPIC**: EPIC-005 (Finance & rentabilité)
- **Sprint**: Sprint 9
- **Statut**: 🔴 To Do
- **Points**: 8
- **Persona**: P6 (Directeur financier / contrôleur de gestion), P2 (Marc — chef de projet)
- **Créé le**: 2026-09-04
- **Mis à jour**: 2026-09-04

## Traçabilité
- **Implémente**: EF-FIN (marge réelle projet = produit facturable − charge valorisée), OBJ-6 (écart marge mi-projet vs clôture ≤ 5 pts), INV-2 (non-rétroactivité : les marges passées ne sont jamais recalculées), ARC-6 (moteur de calcul financier unique et testé)
- **Dépend de**: US-060 (valorisation figée — coût réel par imputation), US-057 (clôture de période), US-005 (modèle analytique), US-055 (validation des temps)
- **Décision structurante (ADR léger, actée avec le PO)**: en l'absence de module de facturation, le **« produit facturable » est le CA reconnu** (taux de vente × temps validé), déjà matérialisé dans `fact_project_revenue` (Sprint 8). La marge réelle est donc **CA reconnu − coût valorisé**. La ligne « facturé réel » sera raccordée quand un module facturation existera (EPIC-005 tranche ultérieure).

## User Story

**En tant que** directeur financier (P6) et chef de projet (Marc),
**je veux** que la **marge réelle par projet** (CA reconnu − coût valorisé) soit calculée à la **clôture de chaque période** et **figée**,
**afin de** piloter la rentabilité de mes projets sur des chiffres fiables, non-rétroactifs et opposables au contrôle de gestion, sans réconciliation manuelle.

## Critères d'Acceptation

### CA-1 (Nominal) : Marge réelle par projet calculée à la clôture

```gherkin
GIVEN la période "Novembre 2026" est clôturée (US-057) pour le tenant Agence A
  AND les temps validés de la période sont valorisés (US-060) : coût réel et CA reconnu par projet
WHEN la clôture de la période est confirmée
THEN pour chaque projet ayant une activité valorisée sur la période :
  - la marge réelle est calculée : marge = CA reconnu − coût valorisé
  - le taux de marge (%) est calculé : marge / CA reconnu (si CA reconnu > 0)
  - la ligne de marge est figée (snapshot) et datée de la clôture
  AND la marge consolidée du tenant sur la période est la somme des marges par projet
  AND chaque ligne de marge est traçable jusqu'aux imputations valorisées qui la composent
```

### CA-2 (Alternatif) : Non-rétroactivité — un changement de taux ultérieur ne réécrit pas la marge passée

```gherkin
GIVEN la marge réelle du projet "Site vitrine" pour Novembre 2026 est figée à la clôture (marge = 4 200 €)
WHEN un taux de vente ou un coût est révisé à compter de Décembre 2026
  AND un utilisateur consulte la marge de Novembre 2026 en Janvier 2027
THEN la marge de Novembre 2026 affichée est toujours 4 200 € (figée à la clôture)
  AND aucun recalcul rétroactif n'est déclenché (INV-2)
  AND la période Novembre 2026 étant clôturée, tout recalcul exige une réouverture formelle (US-057)
```

### CA-3 (Contrainte) : Moteur de calcul unique (ARC-6)

```gherkin
GIVEN la marge est calculée côté backend par un moteur unique et testé
WHEN un écran (web ou mobile) affiche la marge d'un projet
THEN l'écran n'effectue AUCUN calcul de marge (il ne fait que présenter la valeur fournie par le backend)
  AND il n'existe aucune duplication de la formule de marge entre back et front
  AND la couverture de tests du moteur de marge est ≥ 80 % (ENF-MAINT-1)
```

### CA-4 (Erreur) : Valorisation incomplète — marge signalée comme partielle

```gherkin
GIVEN un projet de la période comporte des imputations en statut "MISSING_RATE" (taux manquant, CA-4 de US-060)
WHEN la marge du projet est calculée à la clôture
THEN la marge est calculée sur les seules imputations valorisées
  AND la ligne de marge du projet est marquée "partielle — valorisation incomplète"
  AND le nombre d'imputations non valorisées est indiqué, avec un lien vers leur correction
  AND la marge consolidée du tenant signale globalement la présence de marges partielles
```

### CA-5 (Habilitation) : Coût et marge réservés aux rôles finance/direction (HAB-1)

```gherkin
GIVEN un chef de projet consulte la rentabilité de ses projets
  AND il ne porte pas l'habilitation VIEW_COLLABORATOR_COST
WHEN il ouvre la vue de marge
THEN il voit le CA reconnu par projet
  BUT le coût unitaire, le coût valorisé et la marge sont masqués (réservés finance/direction, HAB-1)
  AND la lecture des données sensibles par un rôle habilité est tracée (HAB-6)
```

## Critères UI/UX

### Web
- La marge par projet est présentée en cohérence avec le tableau de bord `/valorisation` existant (mêmes conventions : centimes, gating coût HAB-1, indicateur de fraîcheur).
- Une ligne de marge « partielle » (CA-4) affiche un badge d'avertissement avec le compte d'imputations non valorisées.
- La marge est présentée par période clôturée ; les périodes ouvertes affichent une marge « provisoire » clairement distinguée du figé.

### Mobile
- Marge et taux de marge disponibles en lecture (3 KPI en tête de fiche projet), sous réserve d'habilitation.
- Pas de détail de composition de la marge sur mobile (orienté desktop contrôle de gestion).

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | À décomposer (`/project:decompose-tasks 009`) | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Moteur de marge unique + tests ≥ 80 %
- [ ] Non-rétroactivité vérifiée par test (marge figée)
- [ ] Gating HAB-1 vérifié
- [ ] `make ci` vert · revue de clôture · documentation/ADR (proxy facturable)

---

## Notes

**Proxy « facturable » = CA reconnu** : décision actée avec le PO (2026-09-04). Le CA reconnu par projet est déjà calculé et figé au Sprint 8 (`RevenueRecognized` → `fact_project_revenue`, dédup « dernière reconnaissance gagne »). US-071 en dérive la **marge** (CA reconnu − coût valorisé). Formaliser en **ADR léger** (`docs/adr/`) : périmètre, limite (pas de facturé réel), évolution vers un module facturation.

**Réutilisation Sprint 8** : le coût valorisé et le CA par projet sont fournis par la ventilation par projet (`ProjectValuationLine`, join `time_entry_valuation ↔ time_entry`) et `fact_project_revenue`. US-071 ajoute la **jointure clôture** (figer la marge à la clôture de période) et le **moteur de marge** consolidé.

**Non-rétroactivité (INV-2)** : la marge figée à la clôture est stockée en snapshot (comme la valorisation), jamais recalculée par un changement de taux ultérieur. Cohérent avec US-057 (clôture) et US-060 (valorisation figée).
