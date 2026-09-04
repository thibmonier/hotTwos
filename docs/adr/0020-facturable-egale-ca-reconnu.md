# ADR-0020 — Produit « facturable » = CA reconnu (proxy pour la marge réelle, EPIC-005)

- **Statut :** Adopté (2026-09-04) — décision PO actée en préambule du Sprint 9
- **Réf. CDC :** EF-FIN (marge réelle projet = produit facturable − charge valorisée), OBJ-6, INV-2, ARC-6
- **Portée :** EPIC-005 (Finance & rentabilité, Lot 2) — US-071 (moteur de marge), US-072/073 (pilotage)
- **Dépend de :** [ADR clôture US-057], valorisation figée (US-060, Sprint 8 : `fact_project_revenue`)

## Contexte

EPIC-005 exige de calculer la **marge réelle par projet** :

```
marge = produit facturable − charge valorisée
```

La **charge valorisée** (coût réel par imputation) et le **CA reconnu par projet** sont déjà produits et
**figés** au Sprint 8 (US-060) : événement `RevenueRecognized` → table `fact_project_revenue` (grain
tenant / période / projet, dédup « dernière reconnaissance gagne »), et ventilation coût/CA via
`DoctrineTimeEntryValuationRepository::projectBreakdownFor()` (DTO `ProjectValuationLine`).

**Problème :** il n'existe **aucun module de facturation** dans le produit. La notion de « produit
**facturable** » au sens comptable strict (facture émise / facturé réel) n'est donc **pas modélisée**.
Sans décision, US-071 est bloquée (risque « Forte / Fort » identifié au sprint-goal S9).

## Décision

En l'absence de module de facturation, **le « produit facturable » est le CA reconnu** (taux de vente ×
temps validé), déjà matérialisé dans `fact_project_revenue`. La **marge réelle** est donc :

```
marge réelle = CA reconnu − coût valorisé
taux de marge = marge / CA reconnu   (si CA reconnu > 0)
```

- Le moteur de marge (US-071) **consomme** le CA reconnu figé, il **ne le recalcule pas**.
- La marge est **figée à la clôture de période** (US-057), en cohérence avec la valorisation figée
  (US-060) : **non-rétroactivité garantie** (INV-2) — un changement de taux ultérieur ne réécrit jamais
  une marge passée.
- Moteur de calcul **unique et testé côté backend** (ARC-6) : aucune duplication de la formule back/front.

## Alternatives considérées

| Option | Verdict |
|--------|---------|
| **CA reconnu comme proxy (choisi)** | ✅ Donnée déjà figée et traçable (S8), zéro nouveau circuit, cohérent avec la clôture. Limite assumée : ≠ facturé réel. |
| Modéliser un module de facturation complet (factures, échéances, encaissements) | ❌ YAGNI pour le Lot 2 ; hors scope S9 ; tranche EPIC-005 ultérieure. |
| Saisie manuelle du « facturable » par projet | ❌ Réintroduit la réconciliation manuelle que l'EPIC vise à supprimer ; non opposable. |

## Conséquences

### Positives
- US-071 débloquée immédiatement sur des données fiables, figées et traçables.
- Marge non-rétroactive « by design » (héritée du snapshot de valorisation S8).
- Un seul moteur financier (ARC-6) réutilisé par US-072 (budget vs réalisé) et US-073 (dashboard).

### Négatives / limites
- La marge reflète le **CA reconnu**, **pas le facturé réel** (pas d'écart facturation/encaissement).
  L'UI doit nommer la mesure « marge (sur CA reconnu) » pour éviter toute ambiguïté comptable.
- Le raccord « facturé réel » nécessitera une **tranche ultérieure d'EPIC-005** (module facturation), qui
  **superséderait** ce proxy sans changer le moteur (le moteur prendra alors « facturé réel » en entrée).

## Évolution prévue

Quand un module de facturation existera : introduire une source « facturé réel » comme entrée alternative
du moteur de marge (port dédié), et un ADR successeur ferait basculer le proxy → réel **sans réécrire**
les marges historiques (toujours figées à leur clôture).
