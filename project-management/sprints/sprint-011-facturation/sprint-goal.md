# Sprint 11 : Module de facturation — le facturé réel devient la base de la rentabilité (EPIC-005)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 11 |
| Planifié le | 2026-09-05 |
| Début | 2027-01-07 *(provisoire)* |
| Fin | 2027-01-20 *(provisoire)* |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~22 points (1 dev, post-fêtes → capacité pleine ; moy. récente ~24, sécurité 10 %) |
| Base git | `main` (après clôture S10) |
| EPIC | EPIC-005 (Finance & rentabilité) — **capstone** |

## Sprint Goal

> « Un **module de facturation minimal** permet d'émettre des **factures par projet/période** ; le
> **facturé réel** devient la source de la rentabilité (marge, budget, export FEC) **en lieu et place
> du proxy « CA reconnu »** (ADR-0020 supersédé), avec repli sur le CA reconnu tant qu'aucune facture
> n'existe. »

> **Positionnement** : capstone d'EPIC-005. Depuis S8-S10, la chaîne temps → valorisation → **marge**
> → budget/dérive → dashboard → **export FEC** s'appuie sur le **CA reconnu** (proxy acté ADR-0020 en
> l'absence de facturation). S11 introduit le **facturé réel** et rebranche le moteur financier dessus
> **sans le réécrire** (DIP — le port « source de revenu » a été anticipé).

## Definition of Done (rappel projet)

- [ ] Revue de clôture approuvée (`symfony-reviewer` par lot)
- [ ] Tests (couverture ≥ 80 % **gardée en CI** — QUAL-2), `make ci` vert (PHPStan max, Deptrac, gitleaks)
- [ ] Moteur financier **unique** (ARC-6) : le facturé réel est une **source** du même moteur, pas un second calcul
- [ ] Non-rétroactivité (INV-2) : une facture figée n'est pas réécrite ; RLS tenant sur les tables de facturation
- [ ] Gating HAB-1 (montants sensibles) · **ADR** superséder 0020 (facturé réel > proxy)
- [ ] Recette navigateur sur données peuplées (seed enrichi facturation) tracée dans `.recette/`

## Sprint Backlog (affiné — décisions PO 2026-09-05, ADR-0022)

> **Décisions PO** : (1) facturation **minimale, émission manuelle** (échéances/encaissement en tranche
> ultérieure) ; (2) **client structuré d'abord** (US-014 tranche minimale, prérequis d'US-075).
> Cadrage acté en **[ADR-0022](../../../docs/adr/0022-facturation-minimale-facture-reel.md)** (supersede 0020).

| Priorité | ID | Titre | Points | Statut |
|----------|-----|-------|--------|--------|
| ✅ | ADR-0022 | Cadrage facturation minimale + facturé réel > proxy (préambule) | — | ✅ Fait |
| 🔴 Must | US-014 | **Client structuré (tranche minimale)** : entité `Client` + rattachement projet | 5 | 🟢 Ready |
| 🔴 Must | US-075 | Émission **manuelle** de factures par projet/période (`Invoice`, RLS) | 8 | 🟢 Ready |
| 🔴 Must | US-076 | **Facturé réel comme source de marge** (port « source de revenu », repli CA reconnu) | 5 | 🟢 Ready |
| 🟡 Should | US-077 | Export **FEC sur facturé réel** | 5 | 🟢 Ready |
| 🟢 Could | R-01 | Correctif onglet « Suivi budgétaire » (1er clic) | — (dette) | 🔵 À faire |

**Engagement (Must) : US-014 (5) + US-075 (8) + US-076 (5) = 18 pts** (capacité ~22). US-077 (Should, 5)
si capacité ; R-01 (Could, quick-win). Ordre : **US-014 → US-075 → US-076 → US-077**.

### Notes de préparation (Definition of Ready)
- **US-014, US-075, US-076, US-077 sont Ready** (affinées le 2026-09-05). US-014 est **cadrée à sa tranche
  minimale** (entité + rattachement projet ; hiérarchie/contacts/recherche = tranche ultérieure).
- **Réutilisation** : le seam « source de revenu » est anticipé (ADR-0020/0021) → `MarginCalculator` et
  `FecGenerator` **inchangés** (ARC-6) ; US-076 matérialise le port et bascule la règle vers le facturé réel.

## Dépendances

| Élément | Dépend de | Note |
|---------|-----------|------|
| US-075 | US-014 (clients structurés) | ⚠️ US-014 en backlog : facturation par `Project.clientName` en 1re tranche, client structuré ultérieurement |
| US-076 | US-071 (moteur de marge), ADR superséder 0020 | Le moteur prend le facturé réel via un port ; repli CA reconnu |
| US-077 | US-074 (FecGenerator), US-076 | Le FEC bascule sur le facturé réel quand présent |

## Risques identifiés

| Risque | Prob. | Impact | Mitigation |
|--------|-------|--------|------------|
| Sur-modélisation (compta complète, encaissement) | **Forte** | Fort | **Spike + ADR** cadrant une facturation *minimale* ; échéances/encaissement en tranche ultérieure |
| Double source de revenu (proxy vs facturé) incohérente | Moyenne | Fort | Un **port unique « source de revenu »** ; règle claire : facturé réel s'il existe, sinon CA reconnu ; non-rétroactivité |
| US-014 (clients) manquante | Moyenne | Moyen | 1re tranche par `Project.clientName` ; structurer les clients plus tard |

## Cérémonies

| Cérémonie | Timing |
|-----------|--------|
| Planning P1 (QUOI) / P2 (COMMENT) | Début S11 (après spike/ADR) |
| Daily | Quotidien |
| Affinage | Début (créer + affiner US-075/076/077) |
| **Review + Rétro** | **Fin de sprint** |

## Actions rétro S10 à intégrer
- **R-01** (onglet « Suivi budgétaire ») — Could / quick-win.
- **`MAILER_DSN` staging + e2e reset** — reconduit (hors thème facturation ; à caser si capacité).

## Notes
Capstone d'EPIC-005. À l'issue de S11, la rentabilité s'appuiera sur le **facturé réel** (opposable),
le proxy CA reconnu restant le repli. Prochaine étape : **spike + ADR** puis
`/project:add-story EPIC-005 "…"` pour figer US-075/076/077, puis `/project:decompose-tasks 011`.
