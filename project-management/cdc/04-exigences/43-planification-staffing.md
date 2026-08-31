# 04.3 — Planification et staffing (`PLN`)

**Lot cible :** 2 — **Personas concernés :** resource manager (P3), chef de projet (P2), collaborateur (P1)

C'est le module à plus forte valeur perçue et le plus difficile à réussir. Sa qualité se juge sur un seul critère : **le resource manager abandonne-t-il son tableur ?** Si la réponse est non, le module a échoué, quelle que soit la richesse de ses fonctions.

## 1. Capacité

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PLN-1` | M | Le système doit calculer la **capacité brute** de chaque collaborateur à partir de son contrat, de son calendrier et de son taux d'activité. | Un collaborateur à 80 % sur calendrier français affiche une capacité cohérente semaine par semaine. |
| `EF-PLN-2` | M | Le système doit calculer la **capacité nette** = capacité brute − absences validées − absences prévisionnelles − charge interne récurrente (réunions, gestion, avant-vente), selon un taux d'occupation cible paramétrable par profil. | Le mode de calcul est affiché et décomposable pour tout collaborateur, à toute date. |
| `EF-PLN-3` | M | Le système doit projeter la capacité sur un horizon glissant d'au moins 12 mois, paramétrable. | — |
| `EF-PLN-4` | S | Le système doit intégrer les arrivées et départs connus (embauches signées, préavis) dans la projection de capacité. | Une embauche au 01/10 apparaît dans la capacité d'octobre avec une montée en charge progressive paramétrable. |
| `EF-PLN-5` | S | Le système doit intégrer les ressources externes disponibles (freelances référencés, sous-traitants) en capacité mobilisable distincte. | La capacité interne et la capacité externe mobilisable sont affichées séparément. |

**Sur `EF-PLN-2` :** c'est l'exigence qui décide de la crédibilité du module. Une capacité brute affichée à 5 j/semaine produit un plan de charge que le resource manager sait faux, et qu'il corrigera donc dans son tableur. Le taux d'occupation cible n'est pas un indicateur de performance, c'est un paramètre de calcul.

## 2. Demande et plan de charge

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PLN-6` | M | Le système doit consolider la demande de charge issue des affectations projet (ferme) et des opportunités pondérées (probable), par profil et par période. | Ferme et probable sont toujours distinguées visuellement et dans les exports. |
| `EF-PLN-7` | M | Le système doit présenter un **plan de charge** croisant collaborateurs (ou profils) et périodes, avec le détail des projets affectés. | La vue est disponible à la maille semaine et mois, sur un horizon paramétrable. |
| `EF-PLN-8` | M | Le système doit signaler les situations de **sur-affectation** (charge > capacité) et de **sous-affectation** (charge < seuil paramétrable), par collaborateur et par période. | Les deux situations sont repérables en un coup d'œil sur la vue plan de charge. |
| `EF-PLN-9` | M | Le système doit permettre l'affectation d'un collaborateur à un projet ou un lot, pour une période, avec un volume, par manipulation directe dans le plan de charge. | Une affectation est créable et modifiable sans quitter la vue de planification. |
| `EF-PLN-10` | S | Le système doit permettre l'affectation par **profil** avant d'être nominative, pour planifier une charge dont on ne connaît pas encore le titulaire. | Une charge « 20 j développeur senior en novembre » est planifiable sans nommer personne, et nominalisable ultérieurement. |
| `EF-PLN-11` | S | Le système doit présenter une vue par projet (qui travaille sur ce projet, quand) et une vue par collaborateur (sur quoi travaille cette personne, quand). | Les deux vues sont accessibles depuis un même écran. |
| `EF-PLN-12` | S | Le système doit gérer des affectations à granularité variable : journée, demi-journée, ou pourcentage de temps sur une période. | Le tenant choisit sa granularité de référence. |

**Sur `EF-PLN-10` :** l'affectation par profil est ce qui permet de planifier des affaires non encore staffées. Sans elle, tout ce qui n'est pas nominatif retourne dans le tableur.

## 3. Compétences et adéquation

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PLN-13` | S | Le système doit permettre de rechercher les collaborateurs disponibles sur une période **et** disposant d'un jeu de compétences donné, avec niveau minimum. | La recherche « React niveau confirmé, disponible ≥ 10 j en octobre » retourne une liste ordonnée et justifiée. |
| `EF-PLN-14` | S | Le système doit signaler les affectations pour lesquelles le collaborateur ne dispose pas des compétences requises par le lot. | L'écart de compétence est signalé sans bloquer l'affectation. |
| `EF-PLN-15` | C | Le système doit prendre en compte les **souhaits d'évolution** exprimés par les collaborateurs (issus du module RH) dans les propositions d'affectation. | Une affectation contribuant à un souhait déclaré est signalée comme telle. |

## 4. Simulation et scénarios

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PLN-16` | S | Le système doit permettre de **simuler** l'impact d'une nouvelle affaire, d'un départ ou d'une absence longue sur le plan de charge, sans modifier les données de production. | Une simulation est créable, comparable au plan courant, et applicable ou abandonnée. |
| `EF-PLN-17` | S | Le système doit permettre de comparer plusieurs scénarios d'affectation sur des critères explicites : taux d'occupation, marge, adéquation des compétences, continuité d'équipe. | Le comparatif est présenté sous forme de tableau avec les mêmes indicateurs pour chaque scénario. |
| `EF-PLN-18` | C | Le système doit permettre de figer une version du plan de charge (photo à date) et de comparer plan prévu et plan réalisé. | L'écart entre affectation prévue et temps réellement imputé est calculable par période. |

## 5. Publication et vie quotidienne

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PLN-19` | M | Le système doit permettre à chaque collaborateur de consulter son planning sur un horizon d'au moins 4 semaines. | Le planning individuel est lisible sur mobile. |
| `EF-PLN-20` | S | Le système doit notifier un collaborateur lorsque son affectation change de manière significative (nouveau projet, retrait, variation de volume au-delà d'un seuil). | La politique de notification est paramétrable et ne produit pas plus d'une notification agrégée par jour. |
| `EF-PLN-21` | S | Le système doit distinguer un plan de charge **publié** (visible des collaborateurs) d'un plan **en cours d'arbitrage** (visible du seul resource manager). | Les modifications en cours d'arbitrage ne génèrent aucune notification. |
| `EF-PLN-22` | C | Le système doit exporter le planning individuel vers un agenda personnel (iCal). | — |

**Sur `EF-PLN-21` :** sans distinction entre brouillon et publié, le resource manager ne pourra pas travailler ses arbitrages dans l'outil — chaque essai déclencherait des notifications. Il retournera au tableur. Cette exigence a l'air secondaire ; elle est structurante pour l'adoption.

## 6. Capacités assistées par IA

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PLN-23` | S | **[IA]** Le système doit proposer des affectations pour une charge non staffée, en classant les candidats selon des critères pondérés et **explicités** : disponibilité, adéquation des compétences, continuité sur le projet ou le client, coût, souhait d'évolution. | Chaque proposition affiche le score par critère et la pondération appliquée. La pondération est modifiable par le tenant. |
| `EF-PLN-24` | S | **[IA]** Le système doit proposer des scénarios de résolution des conflits d'affectation détectés (décalage, substitution, renfort externe, réduction de périmètre), avec l'impact chiffré de chacun. | Chaque scénario indique son impact sur la date de livraison, la marge et la charge des collaborateurs concernés. |
| `EF-PLN-25` | S | **[IA]** Le système doit détecter et signaler les **tensions capacitaires à venir** par profil et par compétence, sur un horizon supérieur au délai de recrutement, et alimenter le module Recrutement. | L'alerte précise le profil, le volume manquant, la période et son niveau de confiance. |
| `EF-PLN-26` | C | **[IA]** Le système doit signaler les collaborateurs en situation de **surcharge répétée** sur plusieurs périodes consécutives, comme signal de risque opérationnel et humain. | L'alerte est adressée au manager et à la RH, jamais publiquement. Elle porte sur la charge planifiée et réalisée, sur aucune autre donnée. |

**Sur `EF-PLN-23` :** l'exigence d'explicabilité n'est pas rhétorique. Le staffing est un acte de management qui a des conséquences sur les carrières et les rémunérations. Un resource manager ne peut pas défendre devant un collaborateur une affectation qu'il ne sait pas justifier. Une proposition IA non explicable ne sera pas suivie, ou pire, sera suivie sans être comprise.

**Sur `EF-PLN-26` :** attention à la ligne rouge. Détecter une surcharge de travail planifiée est légitime. Inférer un état de fatigue, de démotivation ou un risque de départ à partir de signaux comportementaux ne l'est pas : c'est du profilage de salarié, juridiquement exposé et éthiquement contestable. L'exigence est volontairement bornée à la charge. `[ARB-8]`

## 7. Règles de gestion du module

| Réf | Règle |
|---|---|
| `RG-PLN-1` | La capacité est toujours nette, jamais brute. |
| `RG-PLN-2` | Charge ferme et charge probable ne sont jamais agrégées dans un même chiffre sans mention explicite. |
| `RG-PLN-3` | Une sur-affectation est signalée, jamais bloquée. |
| `RG-PLN-4` | Les absences validées sont déduites de la capacité immédiatement ; les demandes en attente sont affichées en risque distinct. |
| `RG-PLN-5` | Une proposition IA n'est jamais appliquée automatiquement. Elle est proposée, arbitrée, puis appliquée par un humain. |
| `RG-PLN-6` | Le plan de charge affiché à un collaborateur est le plan publié, jamais le plan en cours d'arbitrage. |

## 8. Points ouverts

- `[ARB-9]` — Maille de planification de référence : la journée, la demi-journée ou le pourcentage ? Le choix a un impact fort sur l'ergonomie et sur le volume de données. Recommandation AMOA : demi-journée par défaut, pourcentage en option pour les profils transverses.
- `[à préciser]` — Traitement des collaborateurs affectés à plusieurs projets simultanément : répartition explicite ou implicite ? À trancher en atelier.
- `[HYP-7]` — On suppose que le rituel de staffing est hebdomadaire. Si l'organisation cible arbitre quotidiennement, l'ergonomie doit être repensée pour un usage plus fréquent et plus court.
