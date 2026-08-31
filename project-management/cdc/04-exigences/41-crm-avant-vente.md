# 04.1 — Avant-vente et pipeline commercial (`CRM`)

**Lot cible :** 3 — **Personas concernés :** commercial (P4), chef de projet (P2), direction (P6)

Ce module n'a pas vocation à concurrencer un CRM généraliste. Sa raison d'être est le **lien entre l'engagement commercial et la capacité de production** : c'est ce lien qui manque dans une stack composée d'un CRM du marché et d'un tableur de staffing.

## 1. Comptes et opportunités

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-CRM-1` | M | Le système doit permettre de créer et suivre des opportunités rattachées à un compte client, avec montant estimé, probabilité, date de démarrage souhaitée et durée prévisionnelle. | Une opportunité complète est créable en moins de 2 minutes. |
| `EF-CRM-2` | M | Le système doit gérer un pipeline à étapes paramétrables, avec probabilité par défaut associée à chaque étape. | Le déplacement d'une opportunité d'une étape à l'autre met à jour la probabilité, modifiable manuellement. |
| `EF-CRM-3` | M | Le système doit permettre de qualifier une opportunité par les **profils et volumes de charge** attendus, et non seulement par un montant. | Une opportunité de 80 k€ est décomposable en « 40 j développeur senior + 20 j UX + 15 j CP ». |
| `EF-CRM-4` | S | Le système doit historiser les changements d'étape, de montant et de date, et permettre l'analyse des cycles de vente. | La durée moyenne par étape est calculable. |
| `EF-CRM-5` | S | Le système doit gérer les motifs de perte, avec un référentiel paramétrable et une analyse par motif. | — |
| `EF-CRM-6` | C | Le système doit gérer des activités commerciales (rendez-vous, relances) rattachées à l'opportunité, avec rappel. | — |

**Sur `EF-CRM-3` :** c'est l'exigence centrale du module. Une opportunité qui ne porte qu'un montant est inexploitable pour la planification. Si une seule exigence du module doit être tenue, c'est celle-là.

## 2. Chiffrage et devis

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-CRM-7` | M | Le système doit permettre de construire un chiffrage structuré en lots, chaque lot portant une charge par profil et un prix. | Le total du devis est la somme calculée des lots, jamais une saisie libre isolée. |
| `EF-CRM-8` | M | Le système doit appliquer automatiquement les taux de vente selon la règle de priorité `EF-REF-19`, en autorisant une surcharge manuelle tracée. | Une remise appliquée est visible, motivée et remonte au reporting commercial. |
| `EF-CRM-9` | M | Le système doit calculer et afficher la **marge prévisionnelle** du devis (prix − coût de revient prévisionnel) avant émission. | La marge est affichée en valeur et en pourcentage, avec le détail par lot. |
| `EF-CRM-10` | M | Le système doit gérer le versionnement des devis, avec conservation de l'historique et comparaison entre versions. | Le passage de la V2 à la V3 est comparable ligne à ligne. |
| `EF-CRM-11` | M | Le système doit générer un document de devis au format PDF, personnalisable par modèle et aux couleurs du tenant. | Le modèle est modifiable sans développement. |
| `EF-CRM-12` | S | Le système doit gérer plusieurs modèles de contractualisation : forfait, régie, régie plafonnée, abonnement récurrent. | Chaque modèle produit une structure de projet et de facturation adaptée en aval. |
| `EF-CRM-13` | S | Le système doit alerter lorsque la marge prévisionnelle d'un devis passe sous un seuil paramétrable, et conditionner l'émission à une validation. | Un devis à 12 % de marge quand le seuil est à 25 % déclenche le circuit de validation. |
| `EF-CRM-14` | C | Le système doit permettre de construire un devis à partir d'un modèle ou d'un devis antérieur. | — |

## 3. Interface avec la capacité de production

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-CRM-15` | M | Le système doit afficher, lors du chiffrage, la **disponibilité prévisionnelle** des profils requis à la date de démarrage envisagée. | Un feu (disponible / tendu / indisponible) est affiché par profil, avec le détail de la charge concurrente. |
| `EF-CRM-16` | M | Le système doit intégrer les opportunités au-delà d'un seuil de probabilité dans le plan de charge prévisionnel, en charge **probable** distincte de la charge ferme. | Le seuil est paramétrable (défaut 60 %) ; la distinction ferme/probable est visible partout où la charge est affichée. |
| `EF-CRM-17` | S | Le système doit permettre de demander formellement un avis capacitaire au resource manager depuis le devis, avec réponse tracée. | La demande, la réponse et l'éventuel passage outre sont conservés et remontent au reporting direction. |
| `EF-CRM-18` | S | Le système doit signaler au commercial les opportunités dont la date de démarrage devient incompatible avec la capacité, à mesure que le plan de charge évolue. | Une notification est émise en cas de bascule d'un profil de « disponible » à « indisponible » sur une opportunité active. |

## 4. Signature et bascule en projet

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-CRM-19` | S | Le système doit s'interfacer avec une solution de signature électronique et refléter le statut de signature. | Le devis passe automatiquement en « signé » au retour du service tiers. |
| `EF-CRM-20` | M | Le système doit créer automatiquement le projet à partir du devis accepté : lots, budget par lot, charge par profil, jalons, client, conditions de facturation. | **Zéro ressaisie.** Le projet créé est cohérent ligne à ligne avec le devis signé. |
| `EF-CRM-21` | M | Le système doit conserver le lien entre le projet et le devis d'origine, et permettre la comparaison vendu / réalisé à tout moment. | Depuis un projet, le devis source est accessible en un clic et l'écart est calculé. |
| `EF-CRM-22` | S | Le système doit gérer les avenants : nouveau devis rattaché à un projet existant, mettant à jour le budget après validation. | Le budget projet distingue budget initial et avenants, avec historique. |

## 5. Capacités assistées par IA

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-CRM-23` | S | **[IA]** Le système doit extraire d'un document entrant (cahier des charges client, appel d'offres) une proposition de décomposition en lots avec charges estimées. | La proposition est éditable ; sa source dans le document est indiquée ; l'utilisateur valide chaque lot. |
| `EF-CRM-24` | S | **[IA]** Le système doit proposer une estimation de charge par lot à partir des **projets historiques comparables du tenant**, en affichant les projets utilisés comme référence. | La proposition cite au moins 3 projets sources avec leur écart vendu/réalisé. En dessous de 10 projets comparables dans l'historique, la fonction est désactivée et l'indique. |
| `EF-CRM-25` | C | **[IA]** Le système doit signaler les devis dont le profil de risque ressemble à celui de projets historiques ayant dérapé, en explicitant les similarités. | L'alerte nomme les projets de référence et les facteurs de similarité. Elle ne bloque rien. |
| `EF-CRM-26` | C | **[IA]** Le système doit générer une première version rédigée de la proposition commerciale à partir du chiffrage structuré et d'un modèle de tenant. | Le texte produit est éditable ; aucun chiffre n'est généré par le modèle, tous proviennent du chiffrage structuré. |

**Sur `EF-CRM-24` et `EF-CRM-26` :** deux règles non négociables. (a) Une estimation IA sans base historique suffisante est une invention : la fonction doit se désactiver plutôt que produire un chiffre non fondé. (b) Aucun chiffre affiché dans un document contractuel ne doit être produit par un modèle de langage — le modèle rédige le texte, le système calcule les montants.

## 6. Règles de gestion du module

| Réf | Règle |
|---|---|
| `RG-CRM-1` | Un devis émis est immuable. Toute modification crée une nouvelle version. |
| `RG-CRM-2` | Une opportunité ne peut être marquée « gagnée » sans devis accepté associé. |
| `RG-CRM-3` | La charge probable d'une opportunité disparaît du plan de charge dès qu'elle est perdue ou qu'elle devient obsolète (date de démarrage dépassée de X jours, paramétrable). |
| `RG-CRM-4` | La marge prévisionnelle d'un devis utilise les coûts de revient en vigueur à la date d'émission, et est figée avec la version du devis. |
| `RG-CRM-5` | Le passage outre un avis capacitaire défavorable est autorisé, tracé, et notifié à la direction. |

## 7. Points ouverts

- `[ARB-6]` — Position vis-à-vis d'un CRM existant chez le client (HubSpot, Pipedrive, Salesforce) : HotOnes remplace-t-il ou se synchronise-t-il ? La réponse conditionne le périmètre des exigences 1 à 6 et l'effort d'intégration. Recommandation AMOA : **se synchroniser**, en positionnant HotOnes en aval de la qualification et en amont du chiffrage. Concurrencer un CRM installé est un combat perdu qui coûte cher.
- `[HYP-6]` — On suppose que le chiffrage est réalisé dans HotOnes et non dans un tableur externe. Si les commerciaux chiffrent hors outil, `EF-CRM-7` doit être complété par un import structuré.
