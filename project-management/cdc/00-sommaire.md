# HotOnes — Cahier des charges fonctionnel

**Projet :** refonte de la plateforme HotOnes — ERP de gestion d'agence digitale / ESN
**Version :** 0.1 — document de travail
**Date :** 30 août 2026
**Rédaction :** AMOA
**Statut :** à instruire et arbitrer avec le sponsor

---

## 1. Objet du document

Ce cahier des charges décrit **le besoin**, pas la solution technique. Il a trois fonctions :

1. Fixer la cible fonctionnelle de HotOnes à horizon 18-24 mois, sous une forme exploitable par une équipe de développement interne ou un prestataire.
2. Instruire les scénarios de refonte du MVP existant et recommander une trajectoire.
3. Servir de référentiel de traçabilité : chaque exigence porte un identifiant unique, une priorité et un critère d'acceptation.

Ce document **n'est pas** un dossier de conception technique, ni une spécification d'interface, ni un plan de test. Ces livrables sont produits en aval (voir chapitre 09).

---

## 2. Guide de lecture

| Fichier | Contenu | Lecteur principal |
|---|---|---|
| `00-sommaire.md` | Ce document : objet, conventions, hypothèses structurantes | Tous |
| `01-contexte-enjeux.md` | Contexte marché, problème résolu, objectifs et indicateurs de succès | Sponsor, CODIR |
| `02-perimetre-acteurs.md` | Périmètre inclus/exclu, acteurs, personas, rôles et habilitations | Tous |
| `03-processus-cibles.md` | Macro-processus métier cibles et leur enchaînement | Métier, AMOA |
| `04-exigences/` | Exigences fonctionnelles détaillées par module (9 fichiers) | MOE, MOA |
| `05-exigences-non-fonctionnelles.md` | Performance, sécurité, multi-tenant, conformité, accessibilité | MOE, RSSI, DPO |
| `06-architecture-donnees-integrations.md` | Modèle de données conceptuel, socle IA, intégrations tierces | MOE, architecte |
| `07-scenarios-refonte.md` | Comparatif des scénarios de refonte et recommandation | Sponsor, CODIR |
| `08-trajectoire-lotissement.md` | Lotissement, jalons, chiffrage indicatif | Sponsor, chef de projet |
| `09-gouvernance-risques.md` | Instances, rôles projet, risques et parades | Sponsor, chef de projet |
| `10-annexes.md` | Glossaire, matrice de traçabilité, hypothèses et arbitrages | Tous |
| `11-criteres-design.md` | Principes de design, hiérarchie, patterns imposés, anti-patterns | Designer, MOE, MOA |
| `12-socle-technique.md` | 16 décisions d'architecture, stack arrêtée et vérifiée, contrainte de ressource, développement assisté | Sponsor, MOE |
| `13-perspectives-evolution.md` | Mobile, IA locale, autres pistes — et ce qu'elles imposent au présent | Sponsor, MOE |

**Ordre de lecture pour un décideur :** 01 → 12 § 0 → 07 → 08 → 09.
**Ordre de lecture pour une équipe de réalisation :** 02 → 03 → 04 → 05 → 06 → 12 → 11.
**Ordre de lecture pour un designer :** 01 → 02 → 11 → 03.

**Avertissement de fraîcheur.** Le chapitre 12 s'appuie sur des versions et des statuts de composants vérifiés le **30 août 2026** et sourcés en fin de chapitre. Ils évoluent vite : **revérifier avant tout engagement**. Deux points sont déjà datés par construction — la fin de support de Symfony 8.1 (janvier 2027) et le statut expérimental de Symfony Reprise.

**Point de départ imposé :** le chapitre 12 § 0 (contrainte de ressource) doit être lu avant les chapitres 07 et 08. Il pose un écart entre l'ambition du périmètre et l'équipe disponible qui conditionne la lecture de tout le reste du document.

---

## 3. Conventions

### 3.1 Identification des exigences

Chaque exigence porte un identifiant `EF-<MODULE>-<n>` (exigence fonctionnelle) ou `ENF-<DOMAINE>-<n>` (exigence non fonctionnelle).

| Code | Module |
|---|---|
| `REF` | Référentiels et paramétrage |
| `CRM` | Avant-vente et pipeline commercial |
| `PRJ` | Projets et delivery |
| `PLN` | Planification et staffing |
| `TMP` | Temps et activité |
| `FIN` | Finance et rentabilité |
| `RH` | RH et cycle de vie collaborateur |
| `REC` | Recrutement |
| `PIL` | Pilotage et reporting |

Les identifiants sont **stables** : une exigence abandonnée conserve son numéro avec le statut `Abandonnée`, elle n'est jamais réattribuée.

Autres familles d'identifiants employées dans le document :

| Préfixe | Objet | Chapitre |
|---|---|---|
| `OBJ` | Objectif du projet, avec indicateur et cible | 01 |
| `CTR` | Contrainte générale | 01 |
| `MP` | Macro-processus métier | 03 |
| `RG` | Règle de gestion | 03, 04 |
| `HAB` | Règle transverse d'habilitation | 02 |
| `INV` | Invariant structurel du modèle de données | 06 |
| `ARC` | Contrainte d'architecture | 06, 12 |
| `INT` | Principe d'intégration | 06 |
| `REP` | Exigence de reprise de données | 06 |
| `CDR` | Condition de réussite du scénario de refonte | 07 |
| `AUD` | Élément à établir par audit préalable | 07 |
| `RSQ` | Risque projet | 09 |
| `DP` | Principe directeur de design (ordonné) | 11 |
| `AP` | Anti-pattern de design interdit | 11 |
| `MD` | Règle de méthode de conception | 11 |
| `ADR` | Décision d'architecture documentée | 12 |
| `EVO` | Perspective d'évolution et exigence anticipatoire | 13 |

### 3.2 Priorisation (MoSCoW)

| Priorité | Signification | Engagement |
|---|---|---|
| **M** — Must | Sans elle, le produit n'est pas utilisable en production | Périmètre contractuel du lot |
| **S** — Should | Forte valeur, contournement manuel possible à court terme | Périmètre cible, ajustable |
| **C** — Could | Confort, différenciation | Réalisée si la marge le permet |
| **W** — Won't (this time) | Hors périmètre du lot, tracé pour mémoire | Backlog produit |

Règle de dimensionnement : les exigences **M** ne doivent pas dépasser 60 % de la charge d'un lot. Au-delà, le lot n'a plus de variable d'ajustement et le risque de dérive planning devient structurel.

### 3.3 Formalisme d'une exigence

Chaque exigence comporte : un identifiant, un intitulé, une priorité, un énoncé au format « Le système doit… », et un ou plusieurs **critères d'acceptation** vérifiables. Une exigence sans critère d'acceptation vérifiable est une intention, pas une exigence : elle est marquée `[à préciser]`.

### 3.4 Marquage des points ouverts

- `[HYP-n]` : hypothèse retenue par l'AMOA faute d'arbitrage — **à confirmer**, consolidée en annexe 10.3.
- `[ARB-n]` : arbitrage explicitement attendu du sponsor avant réalisation.
- `[à préciser]` : besoin identifié mais non spécifiable en l'état.

---

## 4. Hypothèses structurantes retenues

Ces hypothèses conditionnent l'ensemble du document. Si l'une est fausse, le CDC doit être révisé, pas amendé.

> **Avertissement à lire avant le reste du document.** `HYP-15` (une personne au démarrage) est incompatible avec le périmètre et le chiffrage des chapitres 04 et 08, établis pour une équipe de ~4,5 ETP. Cet écart est instruit au chapitre `12 § 0` et fait l'objet de l'arbitrage `ARB-20`, qui est **le premier à trancher** : il détermine le périmètre réel du projet, et donc la portion de ce cahier des charges qui est effectivement engageable.

| Réf | Hypothèse | Conséquence si fausse |
|---|---|---|
| `HYP-1` | L'existant est un **MVP partiel**, sans base d'utilisateurs en production ni données historiques critiques à reprendre. | Un chantier de reprise de données et de continuité de service devient nécessaire (+3 à 5 mois). |
| `HYP-2` | La cible est un **produit SaaS multi-clients**, commercialisé auprès d'agences et ESN tierces. | Le multi-tenant, le paramétrage par tenant et l'onboarding self-service deviennent inutiles : simplification majeure. |
| `HYP-3` | Le produit doit être **configurable pour des structures de 10 à 150 collaborateurs**. | Un modèle organisationnel figé suffirait, divisant par deux la complexité du module Référentiels. |
| `HYP-4` | Les capacités d'IA sont **intégrées aux modules métier**, sans chapitre ni lot séparé, et s'appuient sur un socle technique mutualisé décrit au chapitre 06. | Un lot IA autonome devrait être défini, avec sa propre gouvernance et son propre budget. |
| `HYP-14` | L'existant est bâti sur **Symfony + Twig + îlots Stimulus / Live Components**, sans couche applicative découplée ni modèle multi-tenant. | Le tableau des technologies du chapitre 12 § 16 s'allège nettement. |
| `HYP-15` | L'équipe de réalisation au démarrage est **d'une personne**, avec dix ans d'expertise Symfony. | Le lotissement et le chiffrage du chapitre 08 redeviennent directement applicables ; les décisions du chapitre 12 sont à réexaminer sur le critère `ARC-14`. |
| `HYP-19` | Le développement est **assisté par agent**, avec un outillage imposant Clean Architecture, DDD et TDD. | L'`ADR-8` reprend sa forme restrictive (couche applicative seule) et la charge du chapitre 08 augmente d'autant. |

---

## 5. Ce que ce document ne traite pas

Explicitement hors périmètre de ce CDC — à traiter dans d'autres livrables ou d'autres projets :

- La **charte graphique** et le design system eux-mêmes (livrable UX/UI distinct, produit en lot 0). Le chapitre `11` en fixe les critères et les contraintes, il ne les produit pas.
- Le **modèle tarifaire** de commercialisation de HotOnes (livrable business, prérequis au lot 5).
- La **comptabilité générale** : HotOnes s'arrête à la facturation et à l'analytique de gestion, il n'est pas un logiciel comptable (cf. `02`, périmètre exclu).
- La **paie** : HotOnes produit les éléments variables, il ne calcule pas les bulletins.
- La **stratégie de mise sur le marché** (go-to-market).
