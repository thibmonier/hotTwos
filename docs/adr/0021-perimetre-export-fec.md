# ADR-0021 — Périmètre de l'export comptable FEC (US-074)

- **Statut :** Adopté (2026-09-05) — décision PO (format FEC) actée en préambule d'US-074
- **Réf. CDC :** EF-FIN-22 (export vers la comptabilité), OBJ-3 (reporting automatisé), norme FEC (art. A47 A-1 du LPF)
- **Portée :** EPIC-005 — US-074
- **Dépend de :** US-071 (marges figées `ProjectMargin`), US-057 (clôture de période)

## Contexte

L'application produit du **CA reconnu** et du **coût valorisé** figés par projet à la clôture (US-071),
mais **n'est pas une comptabilité en partie double** (pas de plan comptable, pas de journal d'écritures).
Le PO exige un export au format **FEC** (Fichier des Écritures Comptables), norme légale opposable.

## Décision

L'export FEC **dérive des écritures comptables équilibrées** (débit = crédit) à partir des montants figés,
via une **configuration comptable par tenant** (`FecConfiguration`) :

- **SIREN** du tenant (obligatoire pour le nommage `<SIREN>FEC<AAAAMMJJ>.txt`).
- **Journal** (code + libellé).
- **4 comptes** : produit (classe 7), tiers/client (classe 4), charge (classe 6), contrepartie de charge.

Pour chaque `ProjectMargin` d'une période **clôturée** :
- une écriture de **produit** : débit *tiers* / crédit *produit*, montant = CA reconnu ;
- une écriture de **charge** : débit *charge* / crédit *contrepartie*, montant = coût valorisé.

Chaque écriture est **équilibrée**. Le fichier respecte les **18 champs FEC** (ordre normé, tabulation,
UTF-8, décimales à la virgule, dates AAAAMMJJ), en-tête en 1re ligne, total débit = total crédit.

## Alternatives considérées

| Option | Verdict |
|--------|---------|
| **Écritures dérivées + config comptable tenant (choisie)** | ✅ FEC conforme, opposable, sans construire un GL complet. Limite assumée : reflète le CA reconnu, pas un grand livre réel. |
| Comptabilité en partie double complète (plan comptable, journaux, lettrage) | ❌ Hors périmètre EPIC-005 Lot 2 ; effort disproportionné. |
| Export CSV libre (non normé) | ❌ Rejeté par le PO — le FEC est la norme légale attendue. |

## Conséquences

### Positives
- Fichier FEC conforme et opposable, généré depuis des données figées et traçables (INV-2).
- Aucune duplication de calcul financier (réutilise `ProjectMargin` / le moteur de marge, ARC-6).
- Export réservé aux périodes clôturées (pas de FEC « provisoire »).

### Négatives / limites
- Le FEC reflète le **CA reconnu** et le **coût valorisé**, pas des écritures issues d'une facturation
  réelle ni d'un grand livre complet — nommer la mesure sans ambiguïté.
- Nécessite une **configuration comptable par tenant** (SIREN + comptes) : sans elle, l'export est refusé.

## Évolution prévue

L'arrivée d'un module de facturation / d'une compta en partie double superséderait cette dérivation :
le générateur FEC prendrait alors en entrée de vraies écritures, sans changer le format de sortie.
