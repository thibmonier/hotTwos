# 04.6 — RH et cycle de vie collaborateur (`RH`)

**Lot cible :** 4 — **Personas concernés :** RH (P5), manager, collaborateur (P1), direction (P6)

## Avertissement réglementaire

Ce module traite des **données personnelles de salariés** et produit des éléments pouvant influencer des décisions de carrière. Deux conséquences :

1. Toute fonctionnalité d'aide à la décision portant sur l'évaluation ou l'orientation d'une personne relève de catégories réglementaires encadrées au niveau européen. **La qualification exacte de ces usages doit être établie par un conseil juridique avant conception** — ce document identifie le risque, il ne le tranche pas (cf. `CTR-3`).
2. Le principe de minimisation s'applique strictement : HotOnes ne stocke que ce dont il a besoin pour la capacité, la compétence et le suivi d'entretien. Il n'est pas un SIRH.

Les exigences ci-dessous sont rédigées de manière volontairement conservatrice. Les élargir est un arbitrage explicite, pas une évolution de conception.

---

## 1. Dossier collaborateur

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-RH-1` | M | Le système doit gérer une fiche collaborateur portant les données nécessaires à la production : identité professionnelle, rattachement organisationnel, profil, date d'entrée, type de contrat, taux d'activité, calendrier. | Aucune donnée personnelle non nécessaire à ces usages n'est collectée. |
| `EF-RH-2` | M | Le système doit gérer le cycle de vie du collaborateur : candidat → intégration → actif → en préavis → sorti, avec impact automatique sur la capacité. | Un préavis saisi réduit la capacité projetée à la date de sortie. |
| `EF-RH-3` | S | Le système doit historiser les évolutions du collaborateur (profil, rattachement, taux d'activité) avec date d'effet. | L'historique permet de reconstituer l'organisation à toute date passée. |
| `EF-RH-4` | S | Le système doit gérer les ressources externes (freelances, intérim, sous-traitants) avec un dossier allégé et un statut distinct. | Les externes n'apparaissent pas dans les indicateurs de masse salariale. |
| `EF-RH-5` | C | Le système doit permettre le stockage de documents contractuels rattachés au collaborateur, avec habilitation restreinte. | Accès limité à la RH et à l'intéressé. |

## 2. Compétences

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-RH-6` | M | Le système doit permettre d'associer à chaque collaborateur des compétences du référentiel, avec un niveau de maîtrise et une date de dernière évaluation. | — |
| `EF-RH-7` | M | Le système doit distinguer le niveau **auto-déclaré** par le collaborateur du niveau **validé** par un manager ou un pair. | Les deux valeurs coexistent et sont affichées distinctement. Seul le niveau validé est utilisé dans les recherches d'affectation critiques. |
| `EF-RH-8` | S | Le système doit produire une **cartographie des compétences** de l'organisation : couverture par compétence, profondeur (nombre de personnes par niveau), points de fragilité (compétence portée par une seule personne). | La cartographie est filtrable par pôle et exportable. |
| `EF-RH-9` | S | Le système doit permettre au collaborateur d'exprimer des **souhaits d'évolution** (compétences à développer, types de projets recherchés). | Ces souhaits alimentent `EF-PLN-15` et les entretiens. |
| `EF-RH-10` | S | Le système doit signaler les compétences **critiques** : forte demande dans les projets à venir, faible couverture interne. | Le signalement alimente `EF-REC-1`. |
| `EF-RH-11` | C | **[IA]** Le système doit proposer une mise à jour des compétences d'un collaborateur à partir des projets sur lesquels il a réellement travaillé et des technologies qui y étaient employées. | La proposition est soumise à validation du collaborateur **et** de son manager. Jamais appliquée automatiquement. |
| `EF-RH-12` | C | **[IA]** Le système doit extraire une proposition de compétences structurées depuis un CV ou un document de profil. | La proposition est éditable ; l'extraction ne produit aucun jugement de niveau, seulement une liste de compétences candidates. |

**Sur `EF-RH-7` :** la distinction auto-déclaré / validé est indispensable. Un référentiel de compétences uniquement auto-déclaré est inutilisable pour le staffing (surestimation systématique) ; uniquement validé, il n'est jamais renseigné (charge managériale trop lourde). Les deux ensemble fonctionnent.

**Sur `EF-RH-11` :** l'inférence de compétence à partir de l'activité est utile et sensible. Une compétence inférée puis utilisée pour une décision d'affectation ou d'évolution sans que l'intéressé l'ait validée est un profilage. La double validation est la condition de son acceptabilité.

## 3. Entretiens et suivi

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-RH-13` | M | Le système doit gérer les campagnes d'entretien (annuel, professionnel, de suivi) avec échéances, périmètre et suivi de complétude. | Le taux de réalisation par manager et par période est visible de la RH. |
| `EF-RH-14` | M | Le système doit fournir des **trames d'entretien paramétrables** par type, remplissables par le collaborateur et par le manager. | La trame est modifiable par la RH sans développement. |
| `EF-RH-15` | M | Le système doit restreindre l'accès au contenu des entretiens à l'intéressé, son manager direct et la RH (`HAB-2`). | Aucun autre rôle n'accède au contenu, y compris la direction. |
| `EF-RH-16` | S | Le système doit permettre de définir des objectifs et des actions de développement issus de l'entretien, avec échéance et suivi. | Les actions en retard sont signalées au manager. |
| `EF-RH-17` | S | Le système doit alimenter l'entretien avec des **éléments factuels** issus du système : projets réalisés, compétences mobilisées, formations suivies, évolution de charge. | Ces éléments sont présentés comme du contexte, jamais comme une évaluation. |
| `EF-RH-18` | S | Le système doit produire les éléments variables destinés à la paie et les exporter. | Cf. `EF-TMP-30`. |
| `EF-RH-19` | C | **[IA]** Le système doit produire une **synthèse rédigée** d'un entretien à partir de notes prises pendant celui-ci. | La synthèse est éditée et validée par le manager avant enregistrement. Elle ne produit aucune évaluation, aucun score, aucune recommandation. |
| `EF-RH-20` | W | Notation, scoring ou classement automatique de la performance d'un collaborateur. | **Explicitement hors périmètre.** Cf. avertissement en tête de chapitre. |

**Sur `EF-RH-20` :** cette exclusion est délibérée et doit être maintenue. Un score de performance produit par un système sur un salarié est un cas d'usage exposé sur le plan réglementaire, contestable sur le plan social, et faible sur le plan de la valeur : les managers n'en ont pas besoin pour savoir qui performe. Le lever exigerait une analyse d'impact complète et un avis juridique. `[ARB-14]`

## 4. Formation et montée en compétence

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-RH-21` | S | Le système doit enregistrer les formations suivies et à venir, avec impact sur la capacité (absence) et sur les compétences. | Une formation planifiée réduit la capacité disponible sur la période. |
| `EF-RH-22` | C | Le système doit gérer un plan de développement des compétences à l'échelle de l'organisation, rapproché des besoins projetés. | L'écart entre compétences projetées nécessaires et compétences disponibles est visible. |
| `EF-RH-23` | C | **[IA]** Le système doit proposer, pour un besoin de compétence identifié, les collaborateurs pour qui une montée en compétence serait la plus pertinente, en croisant compétences proches, souhaits exprimés et disponibilité. | Chaque proposition expose ses critères. Le souhait exprimé par le collaborateur est un critère obligatoire, non optionnel. |

## 5. Vie de l'équipe

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-RH-24` | S | Le système doit gérer un parcours d'intégration (onboarding) avec étapes, responsables et échéances. | Le taux de complétion du parcours est suivi. |
| `EF-RH-25` | S | Le système doit gérer un processus de sortie (offboarding) : transfert de projets, restitution, entretien de départ. | La sortie d'un collaborateur déclenche automatiquement l'identification de ses affectations à réattribuer. |
| `EF-RH-26` | C | Le système doit produire des indicateurs RH agrégés : effectif, ancienneté moyenne, rotation, pyramide des profils, taux d'occupation moyen. | Tous les indicateurs sont agrégés ; aucun n'est nominatif. |
| `EF-RH-27` | C | Le système doit gérer un annuaire interne consultable par tous, limité aux informations professionnelles. | Aucune donnée sensible n'y figure. |

## 6. Règles de gestion du module

| Réf | Règle |
|---|---|
| `RG-RH-1` | Aucune donnée de santé n'est stockée. Un arrêt maladie est enregistré comme un type d'absence et des dates, sans motif. |
| `RG-RH-2` | Le contenu d'un entretien n'est accessible qu'à l'intéressé, son manager direct et la RH. |
| `RG-RH-3` | Aucune compétence inférée par le système n'est utilisée dans une décision sans validation humaine explicite du collaborateur et de son manager. |
| `RG-RH-4` | Aucun score, classement ou notation automatique de personne n'est produit. |
| `RG-RH-5` | Toute consultation d'une donnée RH sensible est tracée (`HAB-6`). |
| `RG-RH-6` | Les données d'un collaborateur sorti sont conservées selon une durée paramétrable puis anonymisées, en conservant les agrégats historiques. |

## 7. Points ouverts

- `[ARB-14]` — Frontière exacte entre aide à la décision RH acceptable et profilage. **Prérequis bloquant** au lot 4. À instruire avec un conseil juridique et le DPO, et à documenter dans une analyse d'impact.
- `[ARB-15]` — Articulation avec un SIRH existant chez le client. Recommandation AMOA : HotOnes est consommateur de la donnée SIRH (effectif, contrats, absences) et producteur de la donnée compétence et capacité. Le sens de la synchronisation doit être arrêté avant le lot 4.
- `[HYP-9]` — On suppose que la paie reste externe. Aucune exigence de ce module ne doit ouvrir la voie à un calcul de paie.
