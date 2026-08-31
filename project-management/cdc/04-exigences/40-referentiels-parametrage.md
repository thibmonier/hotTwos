# 04.0 — Référentiels et paramétrage (`REF`)

**Lot cible :** 1 — **Personas concernés :** administrateur tenant, direction

Ce module est le socle. Sa sous-estimation est le motif d'échec le plus fréquent d'un ERP configurable : les autres modules ne peuvent pas être plus flexibles que le référentiel qui les alimente.

## 1. Organisation et structure

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-REF-1` | M | Le système doit permettre de définir une structure organisationnelle hiérarchique (entité juridique → pôle / BU → équipe) sur au moins 3 niveaux, avec un nombre de niveaux paramétrable par tenant. | Un tenant de 12 personnes peut n'utiliser qu'un niveau ; un tenant de 140 personnes en utilise 3 sans développement spécifique. |
| `EF-REF-2` | M | Le système doit permettre de rattacher chaque collaborateur à une unité organisationnelle, avec historisation des rattachements. | Un changement d'équipe au 01/03 laisse les données antérieures rattachées à l'ancienne équipe. |
| `EF-REF-3` | S | Le système doit supporter plusieurs entités juridiques au sein d'un même tenant, avec consolidation et vue par entité. | Le reporting est produisible par entité et consolidé. |
| `EF-REF-4` | M | Le système doit gérer un référentiel de **profils** (ex. développeur senior, chef de projet, UX designer) portant un coût de revient et un taux de vente par défaut. | La création d'un profil et l'affectation d'un collaborateur à ce profil est réalisable sans intervention technique. |
| `EF-REF-5` | S | Le système doit permettre d'historiser les profils, coûts et taux avec date d'effet, et de recalculer les valorisations sur la base des valeurs en vigueur à la date de l'événement. | Une augmentation au 01/07 ne modifie pas la valorisation des temps de juin. |

**Point de vigilance sur `EF-REF-5` :** l'historisation à date d'effet est structurante et doit être conçue dès le lot 1. La rétro-adapter après coup implique une reprise complète du moteur de valorisation. C'est un choix d'architecture, pas une option.

## 2. Calendriers et temps

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-REF-6` | M | Le système doit gérer un calendrier de travail par tenant : jours ouvrés, jours fériés, durée journalière de référence. | Le calendrier français est fourni par défaut et modifiable. |
| `EF-REF-7` | S | Le système doit permettre des calendriers différenciés par entité, par pays ou par collaborateur (temps partiel, forfait jours, horaires spécifiques). | Un collaborateur à 80 % voit sa capacité calculée à 4/5. |
| `EF-REF-8` | M | Le système doit gérer les types d'absence (congés payés, RTT, arrêt, formation, sans solde, télétravail si compté) avec leur impact sur la capacité et leur circuit de validation. | Chaque type d'absence a un impact capacité paramétrable et un valideur défini. |
| `EF-REF-9` | C | Le système doit permettre de définir des périodes de fermeture d'entreprise. | Une fermeture d'août est appliquée à toute l'organisation en une action. |

## 3. Référentiel de compétences

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-REF-10` | M | Le système doit gérer un référentiel de compétences structuré en catégories (technique, fonctionnelle, méthodologique, linguistique, sectorielle). | Le référentiel est arborescent sur au moins 2 niveaux. |
| `EF-REF-11` | M | Le système doit gérer une échelle de niveau de maîtrise paramétrable (défaut : 4 niveaux — notion, autonome, confirmé, référent). | L'échelle est modifiable par tenant sans perte des données existantes. |
| `EF-REF-12` | S | Le système doit fournir un référentiel de compétences pré-alimenté pour le secteur du digital, servant de point de départ modifiable. | Un nouveau tenant dispose de ≥ 150 compétences pertinentes à l'ouverture. |
| `EF-REF-13` | S | **[IA]** Le système doit proposer le rattachement d'une compétence saisie en texte libre à une compétence existante du référentiel, pour éviter la prolifération de doublons. | Sur un jeu de test de 50 saisies libres, ≥ 80 % sont correctement rapprochées ; l'utilisateur peut toujours refuser le rapprochement. |
| `EF-REF-14` | C | **[IA]** Le système doit signaler périodiquement les compétences du référentiel devenues redondantes ou obsolètes, et proposer une fusion soumise à validation. | Aucune fusion n'est appliquée sans validation humaine explicite. |

## 4. Référentiel client et commercial

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-REF-15` | M | Le système doit gérer un référentiel de comptes clients avec hiérarchie groupe / filiale. | La marge est consultable au niveau groupe comme au niveau entité. |
| `EF-REF-16` | M | Le système doit gérer les contacts rattachés aux comptes, avec rôle et statut. | — |
| `EF-REF-17` | S | Le système doit gérer un référentiel de conditions commerciales par client (grille tarifaire spécifique, remise, délai de paiement). | Un devis pour un client sous grille spécifique applique automatiquement ses taux. |
| `EF-REF-18` | C | Le système doit gérer un référentiel de sous-traitants et de freelances, avec leurs compétences et leurs conditions. | Un freelance peut être affecté au plan de charge au même titre qu'un salarié, avec un coût distinct. |

## 5. Paramétrage financier

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-REF-19` | M | Le système doit gérer les taux de vente par profil, par client et par projet, avec une règle de priorité explicite (projet > client > profil). | La règle appliquée est affichée à l'utilisateur lors du chiffrage. |
| `EF-REF-20` | M | Le système doit gérer le coût de revient par profil et par collaborateur, avec un mode de calcul paramétrable (coût direct, coût chargé, coût complet). | Le mode de calcul retenu est documenté dans l'interface et versionné. |
| `EF-REF-21` | S | Le système doit gérer une clé de répartition des charges indirectes (structure, locaux, licences) paramétrable par tenant. | Le passage de la marge brute à la marge nette est décomposable et vérifiable. |
| `EF-REF-22` | M | Le système doit gérer les devises et un taux de change, avec devise de référence par tenant. | Un projet facturé en CHF est consolidé en EUR au taux paramétré. |
| `EF-REF-23` | S | Le système doit gérer les exercices comptables et les périodes de clôture, avec verrouillage des données clôturées. | Une période clôturée est en lecture seule ; sa réouverture est tracée et réservée à un rôle. |

## 6. Paramétrage des processus

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-REF-24` | M | Le système doit permettre de paramétrer les statuts et les transitions des objets principaux (opportunité, projet, besoin de recrutement) sans développement. | Un tenant ajoute un statut « en attente juridique » à son cycle projet via l'interface d'administration. |
| `EF-REF-25` | M | Le système doit permettre de paramétrer les circuits de validation (temps, absences, devis, factures, besoins RH) : valideurs, seuils, délégation, escalade. | Un devis > 50 k€ requiert une double validation ; le seuil est modifiable. |
| `EF-REF-26` | S | Le système doit permettre de paramétrer les seuils d'alerte (dérive projet, sur-occupation, sous-occupation, retard de saisie) par tenant et par type de projet. | Les valeurs par défaut du chapitre 03 sont modifiables. |
| `EF-REF-27` | S | Le système doit permettre de définir des champs personnalisés sur les objets principaux, avec type, obligation et visibilité par rôle. | Un tenant ajoute un champ « secteur d'activité » sur le compte client, exploitable en filtre et en reporting. |
| `EF-REF-28` | C | Le système doit permettre de définir des modèles de projet (structure de lots, jalons, profils types) réutilisables. | La création d'un projet à partir d'un modèle pré-remplit lots et jalons. |

**Sur `EF-REF-27` :** les champs personnalisés sont une facilité qui devient une dette. Exiger dès la conception que tout champ personnalisé soit exploitable en filtre et en reporting — sinon ils créent des données mortes que personne ne peut analyser.

## 7. Paramétrage tenant et administration

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-REF-29` | M | Le système doit permettre l'ouverture d'un tenant avec un jeu de paramètres par défaut opérationnel, permettant un usage immédiat sans configuration préalable complète. | Un tenant nouvellement créé permet de créer un projet et de saisir un temps en moins de 15 minutes. |
| `EF-REF-30` | M | Le système doit gérer les utilisateurs, leur cycle de vie (invitation, activation, désactivation) et leur licence. | La désactivation d'un utilisateur conserve ses données historiques et libère la licence. |
| `EF-REF-31` | M | Le système doit gérer des rôles composés d'un jeu de permissions et d'un périmètre de données (personnel, équipe, pôle, entité, global), personnalisables par tenant. | La matrice du chapitre 02 est reproductible par paramétrage ; un rôle sur mesure est créable sans développement. |
| `EF-REF-32` | S | Le système doit permettre la personnalisation visuelle légère par tenant (logo, couleur principale) sur l'application et les documents générés. | Un devis PDF porte le logo du tenant. |
| `EF-REF-33` | S | Le système doit fournir un journal d'audit des modifications de paramétrage (qui, quoi, avant/après, quand). | Toute modification de taux, de rôle ou de seuil est retrouvable. |
| `EF-REF-34` | C | **[IA]** Le système doit proposer un assistant de configuration initiale conduisant un nouvel administrateur, par questions en langage naturel, à un paramétrage cohérent avec la taille et le mode de fonctionnement de son organisation. | L'assistant produit un paramétrage complet en ≤ 20 minutes ; chaque proposition est modifiable et son effet est expliqué. |

## 8. Règles de gestion du module

| Réf | Règle |
|---|---|
| `RG-REF-1` | Aucun référentiel utilisé par une donnée existante ne peut être supprimé : il est désactivé et reste lisible en historique. |
| `RG-REF-2` | Toute donnée de paramétrage à impact financier (taux, coût, clé de répartition) est historisée avec date d'effet. |
| `RG-REF-3` | Le paramétrage par défaut d'un tenant doit permettre un usage productif immédiat. Un produit qui exige 3 jours de paramétrage avant la première saisie ne sera pas adopté en cible 10-30 personnes. |
| `RG-REF-4` | Toute modification de paramétrage à effet rétroactif exige une confirmation explicite précisant le volume de données impactées. |

## 9. Points ouverts

- `[ARB-5]` — Profondeur de la personnalisation des statuts et des workflows : chaque degré de liberté accordé au tenant se paie en complexité de maintenance et en coût de support. Une matrice « ce qui est paramétrable / ce qui ne l'est pas » doit être arrêtée avant la conception du lot 1.
- `[HYP-5]` — On suppose qu'un tenant reste mono-devise pour son reporting consolidé. À confirmer si des clients internationaux sont visés.
