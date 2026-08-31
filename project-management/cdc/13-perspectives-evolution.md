# 13 — Perspectives d'évolution

Ce chapitre décrit des évolutions **postérieures au périmètre des lots 1 à 5**. Leur intérêt n'est pas de planifier ce qui viendra dans trois ans — cette planification serait fictive — mais d'identifier **ce qui doit être préparé maintenant** pour qu'elles restent possibles, et **ce qui ne doit surtout pas être fait maintenant**.

C'est la fonction principale d'un chapitre d'évolutions : produire des contraintes sur le présent, pas des promesses sur l'avenir.

---

## 1. `EVO-1` — Application mobile

### 1.1 Le besoin

Trois usages du persona P1 (collaborateur) sont naturellement mobiles :

| Usage | Fréquence | Pourquoi le mobile |
|---|---|---|
| Saisie des temps | Quotidienne | Se fait dans les interstices : transport, fin de réunion, fin de journée |
| Gestion des congés | Mensuelle | Se décide souvent hors du bureau |
| Actualisation de l'avancement | Hebdomadaire | Concerne aussi le chef de projet, en déplacement ou en clientèle |

Ces trois usages représentent **l'essentiel du temps passé dans le produit par 80 % de ses utilisateurs**. Les rendre mobiles n'est pas un confort : c'est agir directement sur `OBJ-1` (fiabiliser la donnée de temps) et sur `RSQ-1` (le risque n°1 du projet).

### 1.2 Options

| Option | Ce qu'elle apporte | Ce qu'elle coûte |
|---|---|---|
| **Web responsive** | Un seul code. Déploiement immédiat. Couvre les trois usages. Déjà exigé au lot 1 (`ENF-UX-3`, `EF-TMP-6`). | Pas de notification poussée fiable. Pas d'icône sur l'écran d'accueil sans installation manuelle. Hors ligne limité. |
| **Application web progressive (PWA)** | Ajoute l'installation sur l'écran d'accueil, le fonctionnement hors ligne et, selon les plateformes, la notification poussée. Toujours un seul code. | Le support des notifications poussées varie selon les systèmes d'exploitation mobiles et leurs versions — **à vérifier au moment de la décision, cette situation évolue.** Pas de présence sur les magasins d'applications. |
| **Application native ou multiplateforme** | Meilleure fluidité. Notification poussée fiable. Hors ligne robuste. Présence sur les magasins — argument commercial réel pour un produit vendu à des tiers. | **Une seconde base de code, une seconde compétence, deux magasins, deux cycles de publication, deux processus de validation.** |

### 1.3 Position

> **Trajectoire retenue (arbitrée par le sponsor) : web responsive au lot 1 → PWA au lot 2 ou 3 → application native au-delà du lot 5, et pas en solo.**

**Pourquoi ne pas faire le natif tôt.** Une application mobile native est une compétence entière — développement, publication, respect des règles des magasins, cycle de mise à jour désynchronisé du web, support de versions d'OS anciennes. Pour un développeur PHP seul (chapitre 12 § 0), c'est un second métier. Le construire avant que le produit web ne soit adopté, c'est doubler le coût de maintenance d'un produit qui n'a pas encore prouvé son usage.

**Pourquoi la PWA est le bon intermédiaire.** Elle couvre les trois usages identifiés avec le code déjà écrit. La notification poussée — seul vrai manque — sert principalement la relance de saisie (`EF-TMP-21`), qui fonctionne aussi par courriel et par messagerie d'équipe. Le manque est réel mais pas bloquant.

**Ce qui justifierait d'accélérer :** si la mesure sur le pilote montre que le taux de saisie plafonne et que les collaborateurs invoquent l'absence d'application mobile. C'est une hypothèse à tester, pas à supposer — dans la plupart des organisations, le taux de saisie est limité par l'ergonomie et par le sens perçu de la saisie, pas par le support.

### 1.4 Ce que cela impose au présent

C'est la partie opérante de cette section.

| Réf | Prio | Exigence anticipatoire | Lot |
|---|---|---|---|
| `EVO-1.1` | M | La couche applicative est indépendante du transport (`ARC-17`) : l'API mobile sera un adaptateur, pas un projet. | 1 |
| `EVO-1.2` | M | Les parcours collaborateur (saisie, congés, planning, avancement) sont conçus **d'abord pour un écran étroit**, puis élargis. L'inverse ne fonctionne jamais. | 1 |
| `EVO-1.3` | S | L'authentification supporte dès l'origine un mécanisme à jetons adapté à un client mobile, en plus de la session web. | 2 |
| `EVO-1.4` | S | La saisie hors connexion et sa synchronisation (`EF-TMP-7`) sont conçues côté serveur pour être réutilisables par un client mobile : identifiants de saisie générés côté client, résolution de conflit déterministe, idempotence (`ARC-31`). | 2 |
| `EVO-1.5` | S | Les points d'entrée servant les usages mobiles sont conçus pour un aller-retour unique par écran, pas pour une composition de plusieurs appels. | 3 |
| `EVO-1.6` | C | Une PWA installable est livrée, avec fonctionnement hors ligne sur la saisie et le planning. | 2 ou 3 |

**Sur `EVO-1.2` :** c'est la contrainte la plus importante et la moins coûteuse. Concevoir la saisie de temps pour un écran de téléphone d'abord force une économie de moyens qui bénéficie aussi à la version large — et satisfait mécaniquement `EF-TMP-3` (2 minutes) et `DP-1`. Faire l'inverse produit un écran de bureau qu'on tente ensuite de comprimer, avec le résultat habituel.

**Sur `EVO-1.4` :** la synchronisation hors ligne est l'un des rares sujets qu'il est nettement moins coûteux de préparer que de rétro-adapter. L'identifiant généré côté client et l'idempotence coûtent une heure de conception au lot 2 ; les ajouter après coup sur un modèle de saisie déjà en production est une migration de données.

### 1.5 Ce qu'il ne faut pas faire maintenant

- Développer une application native avant que le taux d'adoption web ne soit mesuré et stabilisé.
- Concevoir une API « pour le mobile » distincte de l'API du produit. Une seule API, plusieurs clients (`ADR-2`).
- Promettre l'application mobile dans un argumentaire commercial avant qu'elle ne soit budgétée. `[ARB-22]`

---

## 2. `EVO-2` — Modèles d'IA exécutés localement

### 2.1 Les motivations, par ordre de solidité

| Motivation | Solidité | Commentaire |
|---|---|---|
| **Souveraineté et confidentialité** | **Forte** | Certains prospects — secteur public, santé, défense, grands comptes à politique stricte — refuseront contractuellement que des données RH et financières transitent chez un tiers, y compris européen. C'est un **argument de vente**, pas seulement de conformité. Cf. `ENF-IA-9` (désactivation) : le modèle local est la réponse supérieure au même besoin. |
| **Maîtrise du coût** | **Moyenne** | Vraie sur les fonctions à volume élevé et faible complexité. Fausse tant que le volume est faible : un serveur d'inférence coûte le même prix qu'il serve dix requêtes ou dix mille. Le point de bascule doit être **mesuré**, pas supposé. |
| **Indépendance du fournisseur** | Moyenne | Réelle, mais l'abstraction `ADR-9` la procure déjà en grande partie, sans coût d'exploitation. |
| **Latence** | Faible à ce stade | Les fonctions du produit ne sont pas critiques en latence : le pré-remplissage et les synthèses tolèrent quelques secondes. |
| **Confidentialité vis-à-vis de l'entraînement** | Faible | `ENF-RGPD-8` traite ce point par contrat. Le modèle local le traite par construction, ce qui est plus fort, mais le besoin est déjà couvert. |

### 2.2 Ce qui marche en local, et ce qui ne marche pas

Analyse par fonction. Cette répartition est le cœur du sujet : **la question n'est pas « local ou tiers », elle est « quelle fonction, où ».**

| Fonction | Exigences | Aptitude au local | Commentaire |
|---|---|---|---|
| **Vecteurs sémantiques** (rapprochement de compétences, doublons, projets comparables) | `EF-REF-13`, `-14` ; `EF-CRM-24` ; `EF-PLN-13` | **Excellente** | Les modèles de plongement sont petits, rapides, et tournent sur un processeur ordinaire. **À internaliser dès le départ** : aucune raison d'envoyer un référentiel de compétences entier à un tiers. |
| **Extraction structurée** (CV, cahier des charges, documents) | `EF-CRM-23` ; `EF-RH-12` ; `EF-REC-14` | **Bonne** | Tâche bornée, format de sortie contraint. Les modèles de taille moyenne s'en acquittent correctement. C'est aussi la fonction traitant les **données les plus sensibles** (CV de candidats). Candidat prioritaire à l'internalisation. |
| **Classification et détection d'anomalie** | `EF-TMP-25` ; `EF-PRJ-29` | **Bonne** | Tâches simples, souvent mieux servies par des règles et de la statistique que par un modèle de langage. À réexaminer : certaines de ces fonctions n'ont pas besoin d'IA du tout. |
| **Pré-remplissage de saisie** | `EF-TMP-9`, `-10`, `-12` | **Bonne** | La logique est majoritairement déterministe (plan de charge + agenda). Le modèle n'intervient qu'en arbitrage. Volume quotidien élevé × tenant : c'est ici que le coût d'inférence tiers s'accumule le plus vite. |
| **Rédaction de synthèses** | `EF-PRJ-28` ; `EF-FIN-25` ; `EF-RH-19` ; `EF-REC-15` ; `EF-PIL-20` | **Moyenne** | Un modèle local produit un texte correct mais plus plat. Acceptable pour une synthèse interne, moins pour un document destiné au client (`EF-CRM-26`). |
| **Interrogation en langage naturel** | `EF-FIN-26` ; `EF-PIL-18` | **Faible** | Traduire une question floue en requête correcte sur un schéma complexe, avec habilitations, est la tâche la plus exigeante du produit. C'est aussi la plus visible et la plus risquée (`ENF-SEC-6`). **À conserver chez un fournisseur de premier plan.** |

### 2.3 Position — révisée par les choix du chapitre 12

Deux décisions techniques prises depuis la première rédaction de ce chapitre **changent la nature de cette évolution**. Elle passe d'un chantier à un paramétrage.

| Décision | Effet |
|---|---|
| **Symfony AI comme socle** (`ADR-10`) | Le composant Platform expose un pont **Ollama** au même titre que les fournisseurs commerciaux. Basculer une fonction vers un modèle local est un changement de configuration, pas un développement. Le composant Store gère déjà `pgvector` : les vecteurs sont locaux par construction. |
| **Clés fournies par le tenant** (`ADR-10`) | L'entrée « point d'accès local » est prévue **dès la conception** dans l'écran d'administration. Un tenant qui héberge son propre modèle le déclare comme il déclarerait une clé commerciale. L'éditeur n'a rien à exploiter. |

**Conséquence majeure : le coût d'exploitation d'un serveur d'inférence — objection principale du § 2.5 — ne pèse plus sur l'éditeur.** C'est le tenant qui l'assume s'il le souhaite. L'éditeur fournit la compatibilité, pas l'infrastructure. Cela lève la tension avec `ARC-3` et `ARC-14`.

> **Position retenue : compatibilité locale par configuration dès le lot 2, jamais d'infrastructure d'inférence exploitée par l'éditeur avant qu'une demande commerciale ne la finance.**

Trajectoire :

| Étape | Contenu | Horizon |
|---|---|---|
| **1** | Vecteurs sémantiques dans PostgreSQL via le composant Store (`ARC-41`). Locaux dès le départ, coût nul, aucune donnée de compétence transmise à un tiers. | Lot 1-2 |
| **2** | Entrée « point d'accès compatible » (type Ollama) dans l'administration du tenant, au même rang que les fournisseurs commerciaux. Testée sur au moins un modèle ouvert. | Lot 2 |
| **3** | Mesure continue du volume, du coût et de la qualité **par fonction, par tenant et par modèle** (`ARC-75`, `EVO-2.3`). C'est cette mesure qui documentera les recommandations faites aux clients. | Lots 1-4 |
| **4** | Publication d'une **matrice de compatibilité** : quelle fonction fonctionne correctement avec quelle catégorie de modèle. Argument commercial et outil de support. | Post-lot 4 |
| **5** | Offre optionnelle avec inférence incluse, gérée par l'éditeur, pour les tenants ne voulant gérer ni clé ni serveur (`ARB-24`). L'inverse de cette évolution — et le vrai besoin du segment 10-30 personnes. | Post-lot 5 |

**L'étape 4 est celle qui a le plus de valeur et personne ne la fait.** Un client qui déclare son propre modèle découvrira que l'extraction de CV fonctionne bien et que l'interrogation en langage naturel se dégrade. Documenter cela, fonction par fonction, transforme un problème de support récurrent en argument de transparence.

### 2.4 Ce que cela impose au présent

| Réf | Prio | Exigence anticipatoire | Lot |
|---|---|---|---|
| `EVO-2.1` | M | La couche produit au-dessus de Symfony AI est définie par les besoins de HotOnes, jamais calquée sur l'API d'un fournisseur (`ARC-73` à `ARC-79`). | 1 |
| `EVO-2.2` | M | Le choix du fournisseur et du modèle est un **paramètre par fonction et par tenant**, pas une constante globale. Une fonction bascule sans toucher au code métier (`ADR-10`). | 1 |
| `EVO-2.3` | M | Volume, coût, latence et indicateur de qualité sont mesurés **par fonction** dès la première mise en production (`ENF-IA-4`, `-7`). Sans cette mesure, l'arbitrage de l'étape 3 sera pris à l'aveugle. | 1 |
| `EVO-2.4` | S | Les invites et les formats de sortie attendus sont versionnés et testés indépendamment du modèle, avec un jeu d'exemples de référence. | 2 |
| `EVO-2.5` | S | Chaque fonction IA dispose d'un jeu de cas de test permettant de comparer objectivement deux modèles avant bascule. | 3 |
| `EVO-2.6` | S | L'écran d'administration du tenant accepte un **point d'accès compatible auto-hébergé** au même rang qu'un fournisseur commercial, avec test de validité à l'enregistrement (`ARC-82`). | 2 |
| `EVO-2.7` | C | L'architecture permet un déploiement complet chez le client, sans dépendance à un service externe. | Post-lot 5 |

**Sur `EVO-2.3` :** c'est l'exigence opérante de toute cette section. La question « faut-il passer au local ? » n'a pas de réponse théorique — elle dépend du volume réel par fonction, du coût constaté et de la qualité mesurée, trois grandeurs qu'on ne connaîtra qu'après usage. Instrumenter dès le premier jour coûte peu ; reconstituer l'historique a posteriori est impossible.

**Sur `EVO-2.5` :** sans jeu de test comparatif, la bascule vers un modèle local se décide au ressenti, et la dégradation de qualité n'est constatée que par les utilisateurs — trop tard.

### 2.5 Les coûts cachés à ne pas sous-estimer

Ils sont la raison pour laquelle cette évolution est classée en perspective et non en exigence.

| Coût | Détail |
|---|---|
| **Exploitation** | Un serveur d'inférence est une brique à installer, superviser, mettre à jour et dimensionner. **Ce coût est désormais porté par le tenant qui le choisit, plus par l'éditeur** (`ADR-10`). Il reste réel pour le client, et il conditionne l'adoption effective de l'option. |
| **Matériel** | L'inférence performante suppose du matériel spécialisé, en location ou à l'achat. Coût fixe, indépendant de l'usage. |
| **Veille** | Les modèles ouverts évoluent vite. Rester à jour est un travail récurrent, pas une action ponctuelle. |
| **Qualité** | L'écart de qualité avec les meilleurs modèles propriétaires reste réel sur les tâches complexes, même s'il se réduit. **À réévaluer au moment de la décision** — cette situation évolue rapidement et toute affirmation datée sur ce point est à vérifier. |
| **Licences** | Les licences des modèles ouverts ne sont pas toutes compatibles avec une exploitation commerciale en SaaS. **À vérifier modèle par modèle, avant tout engagement.** |

**Le point de licence est celui qu'on oublie le plus souvent.** Un modèle « ouvert » n'est pas nécessairement utilisable dans un produit commercial. Cette vérification est juridique et doit précéder tout travail d'intégration.

---

## 3. Autres perspectives identifiées

Ces pistes sont tracées sans engagement. Elles ne portent pas d'exigence anticipatoire, sauf mention.

| Réf | Perspective | Intérêt | Ce qui doit être préparé |
|---|---|---|---|
| `EVO-3` | **Portail client** : accès en lecture au projet, aux jalons, aux comptes rendus et aux factures | Fort en différenciation ; réduit la charge de reporting du chef de projet | Le modèle d'habilitation doit pouvoir accueillir un rôle externe à l'organisation, avec un périmètre strictement borné (`EF-REF-31`) |
| `EVO-4` | **Comparaison inter-organisations anonymisée** : positionner ses indicateurs face à un ensemble d'agences comparables | Très fort commercialement — c'est ce qu'un éditeur multi-tenant peut offrir et qu'un outil interne ne peut pas | Consentement explicite par tenant, anonymisation démontrable, seuil minimum de participants. **Sujet juridiquement sensible : à instruire avant toute conception.** `[ARB-23]` |
| `EVO-5` | **Modèles prédictifs propres au tenant** : probabilité de dérive, de dépassement, de départ de projet, appris sur l'historique du tenant | Fort, mais suppose plusieurs années de données fiables | `INV-3` et la qualité de la donnée de temps. Sans `OBJ-1` atteint, sans objet. Attention à la limite `ARB-8` : rien qui profile une personne |
| `EVO-6` | **Boutique d'intégrations** ouverte à des tiers | Réduit le coût d'intégration à mesure que la base de clients croît | `ADR-2` (API publique) et `ARC-24` (rappels HTTP) |
| `EVO-7` | **Déploiement chez le client** pour les organisations refusant le SaaS | Ouvre des marchés fermés (public, défense, santé) | Cf. `EVO-2.6` ; suppose de renoncer à une partie des bénéfices d'exploitation du SaaS |
| `EVO-8` | **Gestion de la sous-traitance et des freelances** approfondie (contractualisation, facturation entrante, évaluation) | Réel pour les ESN | `EF-REF-18`, `EF-PRJ-10`, `EF-PLN-5` posent les bases |

---

## 4. Règle de gouvernance des évolutions

| Réf | Règle |
|---|---|
| `RG-EVO-1` | Une perspective d'évolution ne justifie **jamais** d'ajouter de la complexité au présent, sauf si l'exigence anticipatoire est explicitement listée dans ce chapitre et de coût faible. |
| `RG-EVO-2` | Toute évolution est réévaluée à la lumière de l'usage réel avant d'être engagée. Aucune n'est acquise du fait de figurer ici. |
| `RG-EVO-3` | Les exigences anticipatoires `EVO-1.1` à `EVO-2.5` sont intégrées au périmètre des lots indiqués et suivies comme telles. Elles ne sont pas des intentions. |
| `RG-EVO-4` | Aucune évolution de ce chapitre n'est annoncée commercialement avant d'être budgétée et planifiée. |

**Sur `RG-EVO-1` :** c'est la règle qui donne sa valeur au chapitre. Un document de perspectives sert habituellement à justifier des sur-conceptions au présent — « on prévoit large, on en aura besoin plus tard ». Les exigences anticipatoires listées ici ont été sélectionnées sur un critère unique : **elles coûtent peu maintenant et beaucoup plus tard.** Tout le reste attend d'être un besoin démontré.

---

## 5. Points ouverts

| Réf | Objet | Recommandation AMOA | Échéance |
|---|---|---|---|
| `[ARB-22]` | Communication commerciale sur l'application mobile | Ne rien annoncer avant budgétisation | Lot 5 |
| `[ARB-23]` | Comparaison inter-organisations anonymisée | Instruire juridiquement avant toute conception ; consentement explicite et seuil de participants | Post-lot 5 |
| `[HYP-13]` | On suppose que le taux d'adoption de la saisie est limité par l'ergonomie et le sens perçu, pas par l'absence d'application mobile. **À vérifier sur le pilote** — si l'hypothèse est fausse, l'application native remonte fortement en priorité. | — | Lot 1-2 |
