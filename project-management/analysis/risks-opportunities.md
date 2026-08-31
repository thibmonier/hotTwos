# Risques et opportunités — Phase d'analyse

**Projet :** HotOnes — refonte ERP agence digitale / ESN
**Source de vérité :** `project-management/cdc/09` (registre RSQ-*)
**Date :** 2026-08-31

Cotation : Probabilité (P) × Impact (I), de 1 à 5. Criticité = P × I.

---

## 1. Registre des risques (RSQ-*)

| Réf | Risque | P | I | Crit. | Parade |
|---|---|---|---|---|---|
| `RSQ-1` | **Saisie de temps non adoptée** → toute la chaîne financière et capacitaire devient fausse | 4 | 5 | **20** | Budget d'ergonomie prioritaire ; test utilisateur avant dev ; critère de sortie lot 1 bloquant (`ENF-UX-1`) ; contrepartie visible pour le collaborateur |
| `RSQ-3` | **Dérive du périmètre** — l'ambition croît plus vite que la capacité de livraison | 4 | 4 | **16** | Exclusions écrites et opposables ; règle des 60 % de `M` par lot ; toute nouvelle exigence passe par le comité de pilotage |
| `RSQ-9` | **Maintien de l'ancien produit en parallèle** → capacité de construction effondrée | 4 | 4 | **16** | `CDR-4` : arrêt du MVP à la MEP du lot 1 ; décision explicite du sponsor, pas d'entre-deux |
| `RSQ-10` | **Référent pilote indisponible** → l'équipe spécifie sans retour terrain | 4 | 4 | **16** | Engagement formel du pilote avant lot 1 ; 1 j/semaine minimum ; escalade sponsor |
| `RSQ-17` | **La construction devient un refuge** — le produit avance visiblement sans être mis devant un utilisateur | 4 | 4 | **16** | `ARC-100` ; `MD-1`/`MD-2` (tests d'usage) ; indicateur : jours depuis le dernier test utilisateur réel |
| `RSQ-20` | **Capacité de relecture saturée** — le volume de code dépasse ce qui peut être lu ; du code testé fait autre chose que voulu | 4 | 4 | **16** | `ADR-16` : intention machine-vérifiable — `ARC-103` (un test par `RG-*`), `ARC-104` (invariants en base), `ARC-108` (tests écrits depuis l'exigence) |
| `RSQ-22` | **Divergence silencieuse du modèle analytique** vs transactionnel — découverte en comité | 4 | 4 | **16** | `ARC-112` (reconstruction), `ARC-113` (test de non-divergence bloquant en CI), `ARC-114` (réconciliation en prod avec alerte) |
| `RSQ-2` | **Fuite de données inter-tenant, notamment via l'IA** — conséquence commerciale irréversible | 3 | 5 | **15** | `INV-1` porté par le modèle ; filtrage à la source (`ARC-9`) ; test d'intrusion bloquant à chaque lot IA ; ouverture progressive de l'exploration libre (`ARB-17`) |
| `RSQ-5` | **Chiffres financiers non réconciliés avec la comptabilité** → perte de confiance direction | 3 | 5 | **15** | `EF-FIN-23` (écran de contrôle des écarts) dès lot 2 ; `INV-2`/`INV-3` ; traçabilité (`EF-PIL-5`) |
| `RSQ-15` | **Fuite d'état entre requêtes en mode worker** — un service garde le tenant d'une requête précédente | 3 | 5 | **15** | `ARC-47..50` ; parité des environnements (`ARC-86`) ; tests en config worker en CI |
| `RSQ-21` | **Règle d'habilitation générée non relue** — une habilitation manquante ne produit pas d'erreur, elle produit un accès | 3 | 5 | **15** | `ARC-106` : périmètre de sécurité écrit à la main, relu ligne à ligne, testé à la main + test d'intrusion humain |
| `RSQ-6` | Alertes IA à trop de faux positifs → ignorées, puis toutes les alertes | 4 | 3 | 12 | Seuil de faux positifs mesuré (`EF-PRJ-29`) ; retrait sous seuil (`ENF-IA-7`) ; budget de notification (`EF-PIL-14`) |
| `RSQ-8` | Non-conformité réglementaire RH / pré-remplissage → risque juridique et social | 3 | 4 | 12 | AIPD en lot 0 (prérequis bloquant) ; qualification juridique externe (`CTR-3`) ; exclusions `EF-RH-20`/`EF-REC-17` |
| `RSQ-11` | Effet tunnel sur le lot 1 → aucune valeur avant 8 mois | 3 | 4 | 12 | `CDR-3` : MEP pilote à 4-5 mois ; démonstration toutes les 2 semaines |
| `RSQ-12` | Le resource manager continue son tableur → module de planification en échec silencieux | 4 | 3 | 12 | `EF-PLN-2/10/21` ; critère de sortie lot 2 vérifié directement |
| `RSQ-14` | Personnalisation excessive → coût de maintenance/support explose | 3 | 4 | 12 | Matrice « paramétrable / non paramétrable » arrêtée en lot 0 (`ARB-5`) |
| `RSQ-16` | Dérive de version — Symfony 8.1 hors support janv. 2027, montées repoussées | 4 | 3 | 12 | `ARC-51` (tolérance zéro dépréciations), `ARC-52` (montée planifiée), `ARC-53` (réécriture automatisée) |
| `RSQ-4` | L'audit révèle un existant moins réutilisable qu'espéré → budget lot 1 en hausse | 3 | 3 | 9 | Audit en lot 0 avant tout engagement ; recommandation du chap. 07 révisable |
| `RSQ-7` | Coût d'inférence IA dégrade la marge SaaS | 3 | 3 | 9 | Suivi + plafond par tenant (`ENF-IA-5`) ; dégradation gracieuse ; arbitrage économique par fonction |
| `RSQ-13` | Le choix du socle technique devient un projet en soi | 3 | 3 | 9 | Décision en lot 0, 3 critères ordonnés, non rouverte (`ARB-18`) |
| `RSQ-18` | Friction d'installation des clés IA — tenant sans DSI n'active rien, ne perçoit pas la valeur | 3 | 3 | 9 | `ARC-80` (produit utilisable sans IA) ; offre avec inférence incluse (`ARB-24`) ; test commercial pilote |
| `RSQ-19` | Rupture d'API d'un composant jeune (Reprise 0.x, Symfony AI) | 3 | 2 | 6 | `ARC-60` ; couche produit tampon (`ARC-73..79`) ; provision chap. 12 § 17 |

## 2. Risques à surveiller en priorité (criticité ≥ 15)

`RSQ-1` (20) · `RSQ-3`, `RSQ-9`, `RSQ-10`, `RSQ-17`, `RSQ-20`, `RSQ-22` (16) · `RSQ-2`, `RSQ-5`, `RSQ-15`, `RSQ-21` (15).

> **Observation clé du CDC.** La majorité de ces risques sont **organisationnels ou comportementaux, pas techniques**. Le développement assisté par agent ne change pas cette proportion — il la **renforce** : en retirant le facteur limitant qui ralentissait mécaniquement la production, il supprime le temps de relecture qui protégeait par la lenteur. Ce qui protégeait par la lenteur doit désormais protéger par l'outillage (`ADR-16`).

> **Lien avec la décision de cadrage.** Le périmètre complet ayant été retenu malgré `HYP-15` (1 personne), les risques `RSQ-3`, `RSQ-9`, `RSQ-17` et `RSQ-20` sont les plus directement aggravés par l'écart ambition/ressource. Ils exigent un suivi rapproché tant que l'équipe cible n'est pas constituée.

## 3. Opportunités

- **Rupture d'usage sur l'adoption** — traiter la saisie de temps comme un enjeu d'ergonomie (budget 2 min/semaine, pré-remplissage IA) est le principal différenciateur face aux ERP d'agence existants.
- **IA explicable comme argument commercial** — `ENF-RGPD-8` (données jamais utilisées pour l'entraînement) et l'explicabilité (`ENF-IA-1`) sont des points de vente autant que des exigences.
- **Socle multi-tenant posé tôt à bas coût** — construire l'outil mono-organisation sur une architecture SaaS permet d'industrialiser plus tard sans réécriture (`ADR-6`, `ADR-10`).
- **Effet de levier du développement assisté** — facteur 1,4-1,6 sur la charge, à condition de tenir l'outillage de relecture (`ADR-16`).
- **Modèle économique IA sans coût éditeur** — clés fournies par les tenants (`ADR-10`) : le coût d'inférence sort du budget de l'éditeur (sauf option `ARB-24`).
- **Réversibilité fournisseur IA** — couche d'abstraction unique (`ARC-5`/`ARC-8`) : changement de modèle sans réécriture métier, ouvre les marchés souverains.

## 4. Indicateurs de pilotage projet (suivi continu)

| Indicateur | Fréquence | Seuil d'alerte |
|---|---|---|
| Avancement du lot (`M` livrées et recettées / total) | Bimensuel | Écart > 15 % au plan |
| Exigences ajoutées depuis le début du lot | Mensuel | > 10 % du périmètre initial (`RSQ-3`) |
| Couverture de tests sur règles critiques | Continu | < 80 % (`ENF-MAINT-1`) |
| Taux de saisie complète sur le pilote | Hebdomadaire | < 80 % après 6 semaines (`RSQ-1`) |
| Arbitrages `ARB` bloquants en attente | Mensuel | > 3 ouverts |
| Disponibilité effective du référent pilote | Mensuel | < 0,7 j/semaine (`RSQ-10`) |
| Jours depuis le dernier test d'usage réel | Bimensuel | > 30 j (`RSQ-17`) |
| Dépréciations déclenchées par le code applicatif | Continu | > 0 (`ARC-51`, `RSQ-16`) |
| Divergence détectée par la réconciliation analytique | Hebdomadaire | > 0 (`ARC-114`, `RSQ-22`) |
| Règles `RG-*` sans test nommé | Mensuel | > 0 (`ARC-103`, `RSQ-20`) |

---

**Documents liés :** `research-summary.md`, `constraints.md`, `technical-options.md`.
