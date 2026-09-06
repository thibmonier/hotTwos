# Sprint Review — Sprint 11 (Module de facturation, capstone EPIC-005)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-06 |
| Sprint Goal | « Un module de facturation minimal permet d'émettre des factures par projet/période ; le **facturé réel** devient la source de la rentabilité (marge, budget, export FEC) en lieu et place du proxy « CA reconnu » (ADR-0020 supersédé), avec repli sur le CA reconnu tant qu'aucune facture n'existe. » |
| EPIC | EPIC-005 (Finance & rentabilité) — **capstone** |

## 🎯 Atteinte du Sprint Goal

**Sprint Goal atteint : ✅ OUI (100 %)**

- **Émission de factures (US-075)** — factures manuelles par projet/période (période clôturée, montant > 0), figées, gated finance + trace HAB-6, rattachées au client du projet.
- **Facturé réel = source de rentabilité (US-076, ADR-0022)** — port `RevenueSource` unique (facturé réel s'il existe, sinon CA reconnu en repli) ; `ComputeProjectMargins` s'appuie dessus **sans réécrire le moteur** (ARC-6/DIP) ; marge re-figée à l'émission d'une facture.
- **FEC cohérent avec le facturé (US-077)** — l'export FEC reflète automatiquement le facturé réel (il lit `ProjectMargin` re-figé) : cohérence FEC ↔ marge garantie par construction.
- **Client structuré (US-014)** — prérequis facturation : entité `Client` (nom + SIREN, RLS), rattachement projet, CRUD gated.

## 📦 User Stories livrées

| ID | Titre | Points | Priorité | PR | Statut |
|----|-------|--------|----------|-----|--------|
| US-014 | Comptes clients structurés (tranche minimale) | 5 | Must | #57 | ✅ Livré |
| US-075 | Émission manuelle de factures par projet/période | 8 | Must | #58 | ✅ Livré |
| US-076 | Facturé réel comme source de marge (supersede proxy) | 5 | Must | #59 | ✅ Livré |
| US-077 | Export FEC sur facturé réel | 5 | Should | #61 | ✅ Livré |

**Points livrés : 23/23 (18 Must + 5 Should).** T-R01 (Could — correctif onglet « Suivi budgétaire ») non pris.

## 📈 Métriques

| Métrique | Valeur |
|----------|--------|
| Points planifiés / livrés | 18 (Must) planifiés → **23 livrés** (Should US-077 en sus, dans la capacité ~22) |
| PR mergées | #55 → #61 (dont docs de cadrage/décomposition) |
| Tests | 535 → **558** |
| Couverture | ≥ 80 % gardée en CI (`bin/coverage-gate.php`) — verte à chaque merge |
| `make ci` | vert à chaque merge (PHPStan max, Deptrac 0, cs/rector, gitleaks) |
| Migrations | `client` + `project.client_id` (US-014), `invoice` (US-075) — RLS pattern A ; US-076/077 sans migration |

Vélocité S1→S11 : 29 / 20 / 23 / 21 / 22 / 21 / 33 / 22 / 21 / 11 / **23** (retour à la capacité pleine post-fêtes).

## 🎬 Démonstration

1. **Client (US-014)** — `/clients` : création client (nom + SIREN), rattachement depuis la fiche projet.
2. **Facturation (US-075)** — fiche projet, panneau finance : émission d'une facture sur une période clôturée (montant pré-rempli), liste des factures émises.
3. **Facturé → marge (US-076)** — après émission, la marge de la période se re-fige sur le **facturé réel** (dashboard `/finance` et fiche projet) ; sans facture, repli sur le CA reconnu.
4. **FEC (US-077)** — `/finance` → « Export FEC » : les écritures de produit reflètent le facturé réel (libellé « Revenu retenu »), fichier conforme (18 champs, débit = crédit).

## 💬 Feedback à collecter

1. La facturation manuelle minimale (sans échéances/encaissement) suffit-elle pour la prochaine tranche, ou faut-il prioriser le suivi des règlements ?
2. Le passage du proxy « CA reconnu » au facturé réel change-t-il des chiffres de rentabilité déjà communiqués (période de transition) ?
3. Priorités Sprint 12 : suite EPIC-005 (échéances/relances/encaissement) ou nouvel EPIC ?

## Impact backlog

- **T-R01** (Could non pris) : 1er clic onglet « Suivi budgétaire » ne bascule pas (contrôleur Stimulus `tabs`) — reconduit.
- **Harmonisation libellés UI** : les vues `/finance` et `/valorisation` affichent « CA reconnu » alors que la valeur est désormais le revenu retenu (potentiellement facturé réel) → finition dashboard à planifier.
- **Facturation avancée** (échéances, encaissement, avoirs, numérotation légale) — tranches ultérieures EPIC-005.

## Prochaines étapes

1. Rétrospective S11 (`sprint-retro.md`).
2. Planifier Sprint 12 (`/workflow:start 012`).
