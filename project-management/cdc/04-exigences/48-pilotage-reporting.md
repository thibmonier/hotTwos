# 04.8 — Pilotage et reporting (`PIL`)

**Lot cible :** 3 — **Personas concernés :** direction (P6), resource manager (P3), chef de projet (P2), RH (P5)

Ce module ne produit pas de donnée : il restitue celle des autres. Sa qualité dépend donc entièrement de la fiabilité amont. Le construire trop tôt, sur des données non consolidées, produit un outil qui affiche des chiffres faux avec autorité — le pire résultat possible.

## 1. Tableaux de bord

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PIL-1` | M | Le système doit fournir un tableau de bord **par persona**, présentant les indicateurs pertinents pour son rôle et son périmètre. | Six tableaux de bord livrés par défaut, correspondant aux personas P1 à P6. |
| `EF-PIL-2` | M | Le tableau de bord direction doit présenter : CA produit et facturé, marge, backlog, taux d'occupation, tension capacitaire, projets en alerte. | Chargement en moins de 3 secondes sur un tenant de 150 collaborateurs et 5 ans d'historique. |
| `EF-PIL-3` | M | Le tableau de bord chef de projet doit présenter, pour ses projets : consommation, avancement, atterrissage, alertes, équipe affectée, jalons à venir. | — |
| `EF-PIL-4` | S | Le système doit permettre à chaque utilisateur de personnaliser son tableau de bord (choix, ordre et taille des blocs). | La personnalisation est persistante. |
| `EF-PIL-5` | M | Tout indicateur affiché doit être **explorable jusqu'à la donnée élémentaire** qui le compose. | Depuis une marge projet, on atteint en trois clics maximum les lignes de temps et de facture qui la constituent. |
| `EF-PIL-6` | S | Tout indicateur doit exposer sa **définition de calcul** à la demande. | Une icône ou un survol affiche la formule et les données sources. |

**Sur `EF-PIL-5` et `EF-PIL-6` :** ce sont les deux exigences qui décident si les chiffres seront utilisés en comité de direction. Un indicateur non explorable et non défini est un indicateur discuté à chaque réunion au lieu d'être utilisé.

## 2. Analyses et rapports

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PIL-7` | M | Le système doit fournir un jeu de rapports standards : rentabilité par projet, par client, par pôle ; taux d'occupation par collaborateur et par profil ; écarts vendu/réalisé ; complétude de saisie ; pipeline commercial. | Chaque rapport est filtrable par période, périmètre organisationnel et type de projet. |
| `EF-PIL-8` | S | Le système doit permettre la construction de rapports personnalisés par l'utilisateur, sans développement, à partir d'un modèle de données exposé et documenté. | Un utilisateur non technique construit un croisement simple en moins de 10 minutes. |
| `EF-PIL-9` | S | Le système doit permettre l'export de tout rapport en tableur et en PDF. | — |
| `EF-PIL-10` | S | Le système doit permettre la diffusion automatique et périodique de rapports par courriel à des destinataires définis. | La diffusion respecte les habilitations du destinataire, pas celles du créateur du rapport. |
| `EF-PIL-11` | C | Le système doit permettre la comparaison de périodes (N vs N-1) et le suivi de tendances sur tout indicateur. | — |
| `EF-PIL-12` | C | Le système doit exposer une interface d'accès aux données pour un outil de BI externe. | L'accès respecte les habilitations et est tracé. |

**Sur `EF-PIL-10` :** un rapport diffusé qui applique les droits de son créateur est une fuite de données organisée. Le filtrage doit être appliqué au destinataire, à chaque envoi.

## 3. Alertes et notifications

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PIL-13` | M | Le système doit émettre des alertes sur des conditions paramétrables : dérive projet, marge sous seuil, sur/sous-occupation, retard de saisie, jalon en retard, facture échue, tension capacitaire. | Chaque type d'alerte a un destinataire, un seuil et un canal paramétrables. |
| `EF-PIL-14` | M | Le système doit **agréger** les notifications d'un même utilisateur pour ne pas produire plus d'une notification par canal et par jour, sauf urgence explicitement qualifiée. | Un utilisateur reçoit au maximum un courriel de synthèse quotidien, hors alertes critiques. |
| `EF-PIL-15` | S | Le système doit permettre à chaque utilisateur de régler ses préférences de notification par type et par canal. | Le désabonnement d'un type d'alerte est possible sans intervention de l'administrateur. |
| `EF-PIL-16` | S | Le système doit gérer un centre de notifications dans l'application, avec historique et statut lu/non lu. | — |
| `EF-PIL-17` | S | Le système doit permettre l'escalade d'une alerte non traitée après un délai paramétrable. | — |

**Sur `EF-PIL-14` :** l'agrégation n'est pas un confort. Un ERP qui envoie 15 notifications par jour est mis en filtre automatique par ses utilisateurs en deux semaines, et toutes ses alertes deviennent invisibles, y compris les critiques. La politique de notification doit être conçue comme un budget d'attention limité, pas comme une fonctionnalité.

## 4. Exploration assistée

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PIL-18` | S | **[IA]** Le système doit permettre l'interrogation des données en **langage naturel**, dans les strictes limites d'habilitation de l'utilisateur. | La réponse expose systématiquement : la requête interprétée, le périmètre de données mobilisé, et un accès aux données brutes. |
| `EF-PIL-19` | M | Toute réponse produite par l'assistant doit être construite à partir de requêtes **filtrées à la source** par les habilitations de l'utilisateur, jamais filtrées après génération. | Test de sécurité obligatoire : un utilisateur de rôle collaborateur ne peut obtenir aucune information sur les coûts, marges ou entretiens, y compris par formulation détournée, agrégée ou par recoupement. **Critère bloquant de recette.** |
| `EF-PIL-20` | S | **[IA]** Le système doit produire des **synthèses périodiques** commentées (hebdomadaire projet, mensuelle direction), dont chaque chiffre provient du système et non du modèle. | Chaque affirmation renvoie à sa source. Une synthèse contenant un chiffre non traçable est un défaut bloquant. |
| `EF-PIL-21` | C | **[IA]** Le système doit proposer, face à un indicateur dégradé, une décomposition des facteurs contributifs. | La décomposition est arithmétiquement vérifiable. |
| `EF-PIL-22` | C | **[IA]** Le système doit suggérer des explorations pertinentes en fonction du contexte consulté. | Suggestions désactivables. |

**Sur `EF-PIL-19` :** c'est l'exigence de sécurité la plus critique du produit. Un assistant conversationnel multi-tenant sur des données financières et RH est le vecteur de fuite le plus direct qu'un SaaS puisse exposer. Deux conséquences : (a) le filtrage se fait à la construction de la requête, jamais en post-traitement de la réponse ; (b) cette exigence doit faire l'objet d'un test d'intrusion dédié avant toute mise en production, incluant des tentatives d'injection de consigne et d'extraction par recoupement.

## 5. Règles de gestion du module

| Réf | Règle |
|---|---|
| `RG-PIL-1` | Aucun indicateur n'est affiché sans possibilité d'accéder aux données qui le composent. |
| `RG-PIL-2` | Tout affichage applique les habilitations de l'utilisateur connecté, y compris dans les agrégats et les réponses de l'assistant. |
| `RG-PIL-3` | Un rapport diffusé applique les habilitations de son destinataire. |
| `RG-PIL-4` | Aucun chiffre affiché n'est produit par un modèle de langage. Le modèle rédige, le système calcule. |
| `RG-PIL-5` | Les données d'une période clôturée sont figées dans le reporting. |
| `RG-PIL-6` | Toute alerte est agrégée dans le budget de notification quotidien de l'utilisateur, hors criticité explicite. |

## 6. Points ouverts

- `[ARB-17]` — Périmètre de l'assistant en langage naturel au lot 3. Recommandation AMOA : restreindre au départ à un ensemble borné de questions pré-outillées (requêtes paramétrées), et n'ouvrir l'exploration libre qu'après validation du dispositif de filtrage par un test d'intrusion. Ouvrir large dès le départ est le scénario de l'incident de sécurité.
- `[HYP-10]` — On suppose qu'un outil de BI externe reste possible pour les besoins d'analyse avancée. Si HotOnes doit couvrir seul tous les besoins analytiques, le périmètre de `EF-PIL-8` s'élargit fortement.
