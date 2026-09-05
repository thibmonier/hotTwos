# ADR-0022 — Facturation minimale : le facturé réel devient la base de la rentabilité (supersede ADR-0020)

- **Statut :** Adopté (2026-09-05) — décisions PO du Sprint 11 · **supersede [ADR-0020](./0020-facturable-egale-ca-reconnu.md)**
- **Réf. CDC :** EF-FIN, EF-FIN-22, EF-REF-15, OBJ-3, INV-2, ARC-6
- **Portée :** EPIC-005 (capstone) — US-014 (tranche client minimale), US-075, US-076, US-077

## Contexte

Depuis le Sprint 9, la rentabilité (marge US-071, budget/dérive US-072, dashboard US-073, export FEC
US-074) s'appuie sur le **CA reconnu** comme proxy du « facturable » (ADR-0020), en l'absence de module
de facturation. Le PO décide d'introduire la **facturation réelle** comme capstone d'EPIC-005.

## Décisions (PO, 2026-09-05)

1. **Facturation minimale, émission MANUELLE.** Une facture est **émise manuellement** par un rôle
   habilité, par **projet et période**, avec un montant (pré-rempli depuis le CA reconnu, ajustable).
   **Échéances, encaissement, relances, avoirs = HORS scope** (tranche ultérieure).
2. **Client structuré d'abord.** Une **tranche minimale d'US-014** (entité `Client` par tenant +
   rattachement `Project → Client`) précède la facturation. La **hiérarchie groupe/filiale, les contacts
   et la recherche avancée** sont reportés à une tranche ultérieure.
3. **Le facturé réel supersède le proxy CA reconnu.** La rentabilité prend le **facturé réel** comme
   source de revenu **dès qu'une facture existe** pour (projet, période) ; **repli sur le CA reconnu**
   sinon. Un **port unique « source de revenu »** (DIP) alimente le moteur — `MarginCalculator` et
   `FecGenerator` restent **inchangés** (ARC-6, pas de second calcul).
4. **Non-rétroactivité & isolation.** Une facture émise est figée (INV-2) ; RLS tenant sur les tables
   `client` et `invoice`.

## Alternatives considérées

| Option | Verdict |
|--------|---------|
| **Facturation minimale manuelle + facturé réel comme source (choisie)** | ✅ Livrable en un sprint, boucle la valeur finance, sans construire une compta complète. |
| Facturation dérivée automatiquement du CA reconnu | ❌ Rejeté : le PO veut une émission maîtrisée (montant validé), pas un miroir du CA. |
| Module de facturation complet (échéances, encaissement, relances) | ❌ Hors scope S11 ; sur-modélisation. Tranche ultérieure. |
| Conserver le proxy CA reconnu (statu quo) | ❌ Ne répond pas au besoin d'opposabilité (facturé réel). |

## Conséquences

### Positives
- Rentabilité et FEC opposables sur le **facturé réel** ; repli CA reconnu garanti (pas de régression).
- Moteur financier unique réutilisé (ARC-6) ; le seam « source de revenu » était anticipé (ADR-0020/0021).
- Client structuré partageable (projet, facturation, dashboard).

### Négatives / limites
- Pas d'échéancier ni d'encaissement : la facture reflète l'émission, pas le recouvrement.
- US-014 livrée en tranche minimale : hiérarchie/contacts/recherche à suivre.
- Cohérence à surveiller : règle claire **facturé réel s'il existe, sinon CA reconnu**, jamais les deux.

## Évolution prévue

Tranches ultérieures d'EPIC-005 : échéances/encaissement/relances, avoirs, hiérarchie client complète
(US-014 full), migration des ventilations finance de `Project.clientName` vers l'entité `Client`.
