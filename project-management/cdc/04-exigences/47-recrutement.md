# 04.7 — Recrutement (`REC`)

**Lot cible :** 4 — **Personas concernés :** RH (P5), direction (P6), resource manager (P3), manager

La valeur différenciante de ce module ne réside pas dans la gestion de candidatures — les ATS du marché le font mieux — mais dans la **boucle entre la projection de charge et la décision de recruter**. C'est ce chaînon qui manque partout et qui justifie que le recrutement figure dans un ERP de production.

## 1. Détection et qualification du besoin

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-REC-1` | M | Le système doit permettre de créer un besoin de renfort qualifié : profil, compétences requises, volume, période, motif (croissance, remplacement, compétence manquante). | Un besoin est créable manuellement ou depuis une tension détectée. |
| `EF-REC-2` | M | **[IA]** Le système doit alimenter automatiquement des besoins candidats à partir des tensions capacitaires détectées (`EF-PLN-25`), sur un horizon supérieur au délai de recrutement du profil. | Le besoin proposé indique le volume manquant, la période, le niveau de confiance et les projets à l'origine de la tension. |
| `EF-REC-3` | M | Le système doit imposer une étape d'**instruction des options** avant validation d'un besoin de recrutement : recrutement, sous-traitance, montée en compétence interne, décalage commercial. | L'option retenue et le motif de l'arbitrage sont enregistrés. Aucun besoin ne passe en poste ouvert sans cette étape. |
| `EF-REC-4` | S | Le système doit chiffrer l'impact prévisionnel de chaque option : coût, délai de disponibilité, risque. | Le comparatif est présenté sur une même vue. |
| `EF-REC-5` | S | Le système doit gérer un circuit de validation du besoin avec budget associé. | Un besoin validé est budgété et daté. |
| `EF-REC-6` | S | Le système doit maintenir un **plan de recrutement** consolidé : besoins validés, en cours, pourvus, avec échéances et impact sur la capacité projetée. | La capacité projetée intègre les recrutements validés avec leur date d'arrivée estimée et leur montée en charge. |

**Sur `EF-REC-3` :** cette exigence est le principal garde-fou du module. Sans elle, un outil qui détecte des tensions produit mécaniquement du sureffectif : chaque pic de charge devient une embauche, et personne ne regarde la vallée qui suit. L'instruction des options est ce qui transforme une alerte en décision.

**Sur `EF-REC-2` :** la détection est une **proposition**. Aucune ouverture de poste automatique. Le niveau de confiance doit être affiché, car une projection de charge à 5 mois repose largement sur du pipeline pondéré, donc sur de l'incertitude.

## 2. Postes et diffusion

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-REC-7` | M | Le système doit gérer les postes ouverts avec fiche de poste, compétences requises, fourchette de rémunération, responsable du recrutement et échéance cible. | — |
| `EF-REC-8` | S | Le système doit générer une fiche de poste à partir du besoin qualifié et du référentiel de compétences. | La fiche est éditable ; les compétences requises sont reprises du besoin sans ressaisie. |
| `EF-REC-9` | S | Le système doit permettre le suivi des canaux de diffusion et de leur performance (candidatures reçues, qualité, coût par recrutement). | L'analyse par canal est disponible après 6 mois d'usage. |
| `EF-REC-10` | C | Le système doit s'interfacer avec un ou plusieurs sites d'emploi pour la diffusion et la réception de candidatures. | `[à préciser]` — dépend des canaux utilisés par les premiers clients. |
| `EF-REC-11` | C | Le système doit gérer la cooptation : recommandation par un collaborateur, suivi, prime éventuelle. | — |

## 3. Candidatures et évaluation

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-REC-12` | M | Le système doit gérer les candidatures avec un pipeline à étapes paramétrables et un statut par candidat. | — |
| `EF-REC-13` | M | Le système doit permettre l'enregistrement de comptes rendus d'entretien structurés par les évaluateurs, sur une trame paramétrable. | Chaque évaluateur saisit son avis indépendamment ; les avis sont consolidés pour la décision. |
| `EF-REC-14` | S | **[IA]** Le système doit extraire d'un CV les informations structurées (expériences, compétences, formations) pour pré-remplir la fiche candidat. | L'extraction est éditable et son origine dans le document est indiquée. Elle ne produit **aucune évaluation ni aucun score**. |
| `EF-REC-15` | S | **[IA]** Le système doit produire une synthèse rédigée d'un entretien à partir des notes de l'évaluateur. | La synthèse est validée par l'évaluateur avant enregistrement. Aucune conclusion ni recommandation n'est produite. |
| `EF-REC-16` | S | Le système doit gérer un **vivier** de candidatures non retenues mais intéressantes, avec relance possible sur un futur poste. | La conservation en vivier requiert le consentement explicite du candidat, tracé. |
| `EF-REC-17` | W | Scoring, classement ou présélection automatique de candidats. | **Explicitement hors périmètre.** Cf. `RG-MP6-3` et l'arbitrage `[ARB-4]`. |

**Sur `EF-REC-17` :** restriction délibérée et argumentée. Le tri automatisé de candidatures est un cas d'usage explicitement encadré par la réglementation européenne sur l'IA, il expose à un risque de discrimination indirecte difficile à démontrer comme à réfuter, et son gain réel est faible sur les volumes d'une agence de 10 à 150 personnes (quelques dizaines de candidatures par poste). Le rapport risque/valeur est défavorable. Lever cette restriction relève d'un arbitrage de direction assumé, documenté et validé juridiquement.

## 4. Décision et intégration

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-REC-18` | S | Le système doit gérer la proposition d'embauche et son suivi jusqu'à l'acceptation. | — |
| `EF-REC-19` | S | Le système doit créer automatiquement le dossier collaborateur et déclencher le parcours d'intégration à l'acceptation d'une offre. | Zéro ressaisie entre candidature et dossier collaborateur. |
| `EF-REC-20` | M | Le système doit appliquer une **durée de conservation limitée** aux données de candidature, avec purge ou anonymisation automatique à l'échéance. | La durée est paramétrable par tenant ; la purge est effective et vérifiable. |
| `EF-REC-21` | S | Le système doit mettre à jour la capacité projetée dès qu'une embauche est confirmée, avec une montée en charge progressive paramétrable. | Une arrivée au 01/10 contribue à 50 % de sa capacité en octobre, 100 % en décembre — courbe paramétrable par profil. |
| `EF-REC-22` | C | Le système doit mesurer l'efficacité du recrutement : délai de pourvoi, coût par recrutement, taux de transformation par étape, ancienneté des recrutements. | — |

**Sur `EF-REC-21` :** intégrer une embauche à 100 % de capacité dès son premier jour est une erreur classique qui fausse le plan de charge de plusieurs semaines. La montée en charge progressive doit être un paramètre, pas une approximation.

## 5. Règles de gestion du module

| Réf | Règle |
|---|---|
| `RG-REC-1` | Aucune ouverture de poste n'est créée automatiquement par le système. |
| `RG-REC-2` | Aucun scoring, classement ou écartement automatique de candidat n'est produit. |
| `RG-REC-3` | L'assistance IA au recrutement est bornée à l'extraction et à la structuration d'information. |
| `RG-REC-4` | Les données de candidature sont purgées ou anonymisées à l'échéance de la durée de conservation paramétrée. |
| `RG-REC-5` | La conservation en vivier requiert un consentement explicite et révocable du candidat. |
| `RG-REC-6` | Un besoin de recrutement ne peut passer en poste ouvert sans instruction tracée des quatre options alternatives. |

## 6. Points ouverts

- `[ARB-4]` — Position de principe sur l'assistance IA à l'évaluation des candidatures. Ce CDC retient la position restrictive. Toute évolution est un arbitrage de direction, pas une décision de conception.
- `[ARB-16]` — Positionnement vis-à-vis d'un ATS existant. Recommandation AMOA : ne pas concurrencer un ATS installé. HotOnes détient le besoin (amont) et l'intégration (aval) ; l'ATS gère les candidatures. Une intégration légère suffit et réduit fortement le périmètre du lot 4.
- `[à préciser]` — Canaux de diffusion et sites d'emploi à intégrer : dépend des premiers clients.
