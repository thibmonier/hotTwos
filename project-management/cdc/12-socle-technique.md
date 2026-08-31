# 12 — Socle technique et décisions d'architecture

Ce chapitre formalise les décisions techniques sous forme de **décisions d'architecture** (`ADR`) : contexte, options, décision, conséquences assumées. Une décision d'architecture non documentée est rouverte tous les six mois, et la rouvrir coûte plus cher que de la prendre.

**Statut de ce chapitre.** Les orientations ci-dessous ont été arrêtées par le sponsor. Le rôle de l'AMOA est de les instruire, d'en expliciter les conséquences, et de signaler les tensions avec une exigence du cahier des charges.

Trois réserves avaient été formulées dans la version précédente. **Les trois sont levées, pour des motifs différents :**

| Réserve | Issue |
|---|---|
| `ADR-8` — dosage Clean Architecture / DDD | **Révisée.** Le développement assisté par agent et l'outillage retenu invalident la prémisse (le coût d'écriture) sur laquelle reposait l'objection. Position remplacée par un dosage par sous-domaine. |
| `ADR-9` — modèle analytique | **Renforcée dans le sens du sponsor.** Le rafraîchissement non incrémental des vues matérialisées rend l'étape intermédiaire que je proposais coûteuse à quitter. Schéma en étoile physique dès le lot 1, avec trois garanties de non-divergence. |
| `ADR-13` — staging | **Levée.** L'usage établi du sponsor règle les deux points (plan Hobby, zone euro). |


**Vérifications effectuées.** Les versions et l'existence des composants mentionnés ont été vérifiées le 30 août 2026 sur les sources officielles listées en fin de chapitre. Elles évoluent : **revérifier au démarrage effectif.**

---

## 0. La contrainte qui gouverne tout le reste

### 0.1 L'écart à traiter avant toute décision technique

Le chapitre 08 chiffre la refonte à **1 020 j·h** (fourchette 795–1 350) pour une équipe de ~4,5 ETP sur 18 à 22 mois. L'équipe réellement disponible au démarrage est **d'une personne**.

Une personne à temps plein produit 200 à 220 jours ouvrés par an, dont il faut retirer le produit, le design, le support, l'exploitation, la conformité et la commercialisation : en pratique **80 à 130 jours de développement effectif par an**. Le périmètre du chapitre 08 représente donc, en solo, **8 à 12 ans**.

| Voie | Description | Conséquence |
|---|---|---|
| **V1 — Réduire le périmètre** | Lots 1 et 2 seulement (~460 j·h) : suivi projet, temps, capacité, marge. | 4 à 5 ans en solo. Produit utile, pas différenciant. |
| **V2 — Réduire l'ambition produit** | Abandonner la cible SaaS multi-clients (`HYP-2`). Outil mono-organisation. | Divise la charge par ~1,8. Change la nature du projet. |
| **V3 — Financer une équipe** | Lever, s'associer, ou financer par une activité de conseil. | Rend le chapitre 08 applicable. |
| **V4 — Accepter un horizon long** | 4 à 6 ans en solo, produit non commercialisable entre-temps. | Viable si le projet est un actif d'optionalité. |

**Recommandation AMOA : `V2` puis `V3`.** Construire un excellent outil mono-organisation, l'exploiter réellement sur une agence, en tirer la preuve d'usage — puis financer l'industrialisation SaaS avec cette preuve en main.

**Effet du développement assisté par agent.** Il modifie cette arithmétique, mais moins qu'il n'y paraît : l'écriture de code représente 55 à 60 % des 1 050 j·h, le reste étant décision produit, design, test auprès d'utilisateurs, exploitation, conformité et support. Même avec un facteur 2 sur la production de code, l'effet global se situe autour de **1,4 à 1,6** : les 8 à 12 ans deviennent **5 à 8 ans**. C'est mieux, ce n'est pas suffisant. Développement complet à l'`ADR-16`.

**Nuance importante introduite par les choix de ce chapitre.** Le multi-tenant retenu (`ADR-6`) et le modèle de clés d'IA fournies par le client (`ADR-10`) sont, l'un et l'autre, **peu coûteux à poser dès le départ et très coûteux à rétro-adapter**. Ils ne sont donc pas concernés par la réduction d'ambition de `V2` : on construit un outil mono-organisation *dont l'architecture est multi-tenant*, ce qui coûte quelques jours, et non un outil mono-organisation qu'il faudra réécrire. Ce qui est reporté par `V2`, c'est le **produit** SaaS — onboarding self-service, gestion des abonnements, supervision de flotte, documentation publique, support — pas son **socle**. `[ARB-20]`

### 0.2 Le principe technique qui en découle

| Réf | Principe |
|---|---|
| `ARC-14` | **Minimiser le nombre de technologies, de langages et de systèmes à maîtriser et à exploiter simultanément.** Toute brique ajoutée doit justifier son coût d'exploitation récurrent, pas seulement son intérêt technique. |

Ce principe explique la plupart des décisions ci-dessous. Une architecture correcte pour une équipe de dix est souvent ingérable pour une personne — non par difficulté intrinsèque, mais par le coût cumulé de maintenance, de mise à jour et de diagnostic de chaque brique.

**Il faut noter que la stack retenue est, sur plusieurs points, une stack d'avant-garde.** C'est un choix assumé par le sponsor (« au plus haut niveau des dernières innovations de l'écosystème »). Il a un coût réel, chiffré au § 14 : composants jeunes, documentation moins abondante, moins de réponses disponibles en cas de blocage, ruptures possibles entre versions. Ce coût est acceptable **si** les disciplines des `ADR-2`, `ADR-5` et `ADR-8` sont tenues. Il devient un piège si elles ne le sont pas.

---

## 1. `ADR-1` — Monolithe modulaire à cœur API-first

### Contexte

L'exposition d'une API est jugée primordiale, une application mobile native est prévue (chapitre 13), et l'équipe est d'une personne avec dix ans d'expertise Symfony.

### Options

| Option | Coût pour une personne |
|---|---|
| **A — Monolithe classique** | Le plus faible. Mais l'API rétro-ajoutée sur un monolithe dont la logique vit dans les contrôleurs est un chantier de réécriture. |
| **B — Headless strict** (API + application front autonome) | **Le plus élevé.** Deux bases de code, deux cycles de déploiement, authentification par jeton, validation dupliquée, gestion d'état client, perte des formulaires, du CSRF intégré et du rendu serveur. |
| **C — Monolithe modulaire, cœur API-first** | Faible. L'API est un second adaptateur sur un cœur déjà écrit pour cela. |

### Décision

> **Option C.** Un seul déploiement, un seul dépôt. Toute la logique métier vit dans une **couche applicative** (cas d'usage) indépendante du transport. Trois adaptateurs la consomment : contrôleurs web (rendu serveur), contrôleurs d'API, commandes en ligne.

| Réf | Règle — non négociable |
|---|---|
| `ARC-15` | Aucune logique métier dans un contrôleur. Un contrôleur traduit une requête en appel de cas d'usage, et un résultat en réponse. |
| `ARC-16` | Aucune logique métier dans un gabarit. Un gabarit affiche des données préparées, il ne calcule pas. |
| `ARC-17` | Tout cas d'usage est invocable sans HTTP — commande, test, message asynchrone. C'est le test qui prouve que le découplage est réel. |
| `ARC-18` | Aucune entité de persistance n'est exposée directement par l'API ni par un gabarit. Objets de transfert explicites. |
| `ARC-19` | **La validation et le contrôle d'habilitation vivent dans la couche applicative**, jamais dans l'adaptateur. |

**`ARC-19` est le point critique du produit sur le plan de la sécurité.** C'est exactement là que se produisent les fuites : une règle d'habilitation implémentée dans le contrôleur web, oubliée dans le contrôleur d'API. Un seul point d'application, dans le cas d'usage, testé une fois.

### Conséquences assumées

- Surcoût initial de 10 à 15 % sur le lot 1 par rapport à un monolithe classique.
- L'API mobile et les intégrations tierces deviennent l'ajout d'un adaptateur, pas un projet.
- Discipline requise : en solo, personne ne fait la revue de code. Le garde-fou est automatique (`ADR-8`, § Deptrac) et par les tests appelant les cas d'usage directement.
- Ce qu'on ne fait pas : ni microservices, ni déploiements séparés, ni découplage physique du front.

---

## 2. `ADR-2` — Runtime : FrankenPHP, Caddy, Ember

### Décision

> **FrankenPHP en mode *worker*, servi par Caddy, supervisé par Ember.**

| Composant | Rôle | Statut vérifié (30/08/2026) |
|---|---|---|
| **FrankenPHP** | Serveur applicatif PHP moderne, bâti sur Caddy. Mode *worker* : l'application reste chargée en mémoire entre les requêtes. | Projet sous l'organisation `php/` sur GitHub. Expose des métriques Prometheus. |
| **Caddy** | Serveur HTTP, TLS automatique, reverse proxy. Intégré à FrankenPHP. | — |
| **Ember** | Tableau de bord temps réel en terminal pour Caddy, avec introspection FrankenPHP : état par fil d'exécution, profondeur de file du *worker*, plantages, latences P50/P90/P95/P99, expiration TLS, journaux filtrés. Export Prometheus. Mode démon sans interface. | **MIT, gratuit**, officiellement recommandé par FrankenPHP. Métriques enrichies à partir de FrankenPHP 1.12.2+ — **version à revérifier au démarrage**. |

### Ce que le mode *worker* apporte

Gain de performance substantiel : le noyau applicatif n'est plus reconstruit à chaque requête. C'est le levier le plus direct sur `ENF-PERF-2` (saisie de temps < 500 ms au 95e centile), qui est un critère de recette bloquant.

### Ce que le mode *worker* impose — le piège à connaître

L'application ne repart plus d'un état vierge à chaque requête. Trois conséquences, à traiter en conception et non en correctif :

| Réf | Règle |
|---|---|
| `ARC-47` | Aucun service ne conserve d'état entre deux requêtes : ni propriété mutable dépendant de la requête, ni variable statique, ni cache applicatif non borné. Un service qui mémorise le tenant courant est un défaut de sécurité, pas un défaut de performance. |
| `ARC-48` | Le gestionnaire d'entités et le contexte de sécurité sont réinitialisés entre les requêtes par le mécanisme de *runtime* prévu à cet effet. |
| `ARC-49` | La consommation mémoire des processus *worker* est supervisée (Ember) et un seuil de recyclage est configuré. Une fuite mémoire lente est le mode de défaillance caractéristique du mode *worker*. |
| `ARC-50` | Les tests d'intégration s'exécutent au moins une fois en configuration *worker*, pas uniquement en mode requête classique. Sinon les fuites d'état ne sont jamais détectées avant la production. |

**`ARC-47` est le point de vigilance majeur de cette décision.** Une fuite d'état entre requêtes dans une application multi-tenant est un incident d'isolation (`ENF-SEC-4`), pas un simple bogue. La double barrière de l'`ADR-6` en limite la portée, mais ne la supprime pas : le contexte de tenant positionné en base est lui-même porté par la requête.

**`ARC-50` mérite une insistance particulière.** C'est la seule mesure qui transforme `ARC-47` d'une intention en une garantie. Un jeu de tests exécuté deux fois — une fois en mode classique, une fois en mode *worker* — révèle les fuites d'état, et rien d'autre ne les révèle avant les utilisateurs.

---

## 3. `ADR-3` — Version de Symfony et politique de mise à jour

### Fait vérifié — et il change la décision

| Version | Type | Sortie | PHP requis | Fin de support |
|---|---|---|---|---|
| **Symfony 8.1** | Standard | Mai 2026 | **8.4+** | **Janvier 2027** |
| Symfony 7.4 | **LTS** | Novembre 2025 | 8.2+ | Correctifs nov. 2028, sécurité nov. 2029 |
| Symfony 6.4 | LTS | Novembre 2023 | 8.1+ | Sécurité nov. 2027 |

> **Symfony 8.1 sort de support en janvier 2027, soit environ cinq mois après la rédaction de ce document.** Elle ne survivra pas au lot 1.

Ce n'est pas un argument contre le choix — c'est le fonctionnement normal des versions standard de Symfony (8 mois de support, sortie en mai et novembre). Mais cela impose une politique explicite, sans quoi le produit se retrouvera en production sur une version non supportée.

### Options

| Option | Description | Coût |
|---|---|---|
| **A — Rester sur la LTS** (7.4) | Support jusqu'en novembre 2029. Aucune montée de version avant trois ans. | Renonce aux apports de la branche 8 et à une partie de l'écosystème récent. Contredit l'orientation du sponsor. |
| **B — Suivre la branche stable** | 8.1 → 8.2 (nov. 2026) → 8.3 (mai 2027) → … Montée tous les six mois. | 1 à 3 jours par montée **si** la discipline de dépréciation est tenue. 1 à 3 **semaines** si elle ne l'est pas. |
| **C — Suivre la stable, se poser sur la prochaine LTS** | Développer sur la branche stable, et faire de la prochaine version LTS la base de production de long terme. | Combine les deux. |

### Décision

> **Option C — suivre la branche stable pendant la construction, se poser sur la prochaine version LTS comme socle de production.**

| Réf | Règle |
|---|---|
| `ARC-51` | **Tolérance zéro aux dépréciations.** Toute dépréciation déclenchée par le code applicatif fait échouer la chaîne d'intégration. C'est l'unique mesure qui rend la montée semestrielle indolore. |
| `ARC-52` | La montée de version mineure est planifiée dans le mois suivant la sortie, traitée comme une tâche récurrente et non comme un projet. |
| `ARC-53` | L'outillage de réécriture automatique de code (`rector/rector` avec ses jeux de règles Symfony et PHP) est intégré dès le lot 1 et utilisé à chaque montée. |
| `ARC-54` | La version de production ne doit à aucun moment être une version hors support. |

**Point à vérifier avant l'arbitrage `ARB-18` :** quelle version portera le prochain cycle LTS et à quelle date. Le rythme historique de Symfony est d'une LTS tous les deux ans, portée par la dernière mineure d'une branche majeure. **À confirmer sur `symfony.com/releases` au moment de la décision** — les informations trouvées sur ce point le 30 août 2026 étaient contradictoires et je ne les reprends pas ici.

**`ARC-51` est la décision opérante de cet ADR.** Elle coûte quelques heures par mois. Sans elle, chaque montée devient un chantier, les montées sont repoussées, et le produit se retrouve trois ans plus tard sur une version non supportée — c'est-à-dire exactement la situation dont ce projet cherche à sortir.

---

## 4. `ADR-4` — API Platform

### Contexte

API Platform est retenu au motif qu'une API publique sera exposée. Sa version stable en date du 30 août 2026 est la branche **4.3.x** ; une branche **5.0 est en alpha** et supporte Symfony 7.4 et 8.0+. `[à vérifier au démarrage]`

### Décision

> **API Platform retenu, exclusivement en mode objets de transfert avec fournisseurs et processeurs d'état sur mesure. Jamais en exposition directe d'entités.**

| Réf | Règle |
|---|---|
| `ARC-55` | Les ressources d'API sont des objets de transfert dédiés, jamais des entités de persistance (`ARC-18`). |
| `ARC-56` | Chaque opération est servie par un *state provider* ou un *state processor* qui **appelle un cas d'usage** de la couche applicative. Aucune logique dans le fournisseur ou le processeur lui-même. |
| `ARC-57` | Les filtres, tris et paginations exposés sont déclarés explicitement, jamais dérivés automatiquement du modèle de persistance. |
| `ARC-58` | La spécification OpenAPI est générée depuis le code et publiée (`ENF-MAINT-4`). Elle n'est jamais rédigée à la main. |
| `ARC-59` | Les tests d'isolation multi-tenant (`ARC-36`) couvrent chaque opération d'API, au même titre que les écrans. |

### Réserve à énoncer clairement

Le principal argument commercial d'API Platform — exposer une entité Doctrine en API complète avec une annotation — est **exactement ce qui est interdit ici** par `ARC-18` et `ARC-19`. Dans le mode retenu, on utilise environ 40 % de ce que l'outil sait faire : génération OpenAPI, négociation de contenu, pagination, filtres, gestion des erreurs, validation. C'est déjà beaucoup, et cela justifie le choix. Mais il faut le savoir : **API Platform n'est pas ici un accélérateur, c'est une convention outillée.**

Le risque est celui du raccourci sous pression : exposer « juste une entité, pour un cas simple ». En solo, sans revue de code, ce raccourci se prend et ne se rattrape pas. Le garde-fou est mécanique : un test d'architecture (`ADR-8`) qui interdit à toute classe marquée comme ressource d'API d'être une entité de persistance.

**Note sur la version :** ne pas engager la production sur une branche en alpha. Développer sur la stable, évaluer la 5.0 lorsqu'elle sera stabilisée.

### Périmètre de l'API interne et de l'API publique

Une API **interne** (servant les interfaces du produit) et une API **publique** (contractuelle, versionnée, exposée aux clients) sont deux objets différents, avec des coûts très différents.

> **L'API interne existe dès le lot 1 comme adaptateur de la couche applicative. L'API publique est un produit distinct, livré au lot 3.**

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ARC-20` | M | Toute fonctionnalité du produit est accessible par un cas d'usage invocable hors HTTP (`ARC-17`). | Vérifié par des tests appelant les cas d'usage sans couche HTTP. |
| `ARC-21` | S | Une API REST publique est exposée au lot 3, documentée par une spécification générée depuis le code, versionnée, avec politique d'obsolescence. | La documentation ne diverge jamais du comportement réel : elle est générée, pas rédigée (`ARC-58`). |
| `ARC-22` | S | L'API publique applique le même contrôle d'habilitation et la même isolation de tenant que l'interface web (`ARC-19`, `ARC-59`, `ENF-SEC-4`). | Test d'intrusion incluant l'API. |
| `ARC-23` | S | L'API publique dispose d'une limitation de débit par client et d'une journalisation des appels. | — |
| `ARC-24` | C | Un mécanisme de rappel HTTP (webhooks) notifie les systèmes tiers des événements métier. | Utile aux intégrations ; à instruire au lot 3 (`EVO-6`). |

**Avertissement.** Une API publique est un engagement contractuel : une fois qu'un client l'utilise, sa surface ne peut plus changer sans préavis. En solo, cela devient une contrainte permanente sur toute évolution du modèle. Recommandation : exposer une API **délibérément étroite** au lot 3 — lecture des projets, temps et affectations, écriture des temps — plutôt qu'une exposition large. Une API étroite s'élargit ; une API large ne se rétracte jamais. `[ARB-21]`

### Identifiants retirés

Conformément à la convention du chapitre 00 § 3.1, les identifiants abandonnés ne sont jamais réattribués.

| Réf | Statut | Motif |
|---|---|---|
| `ARC-39` | **Abandonnée** | Prescrivait une couche d'abstraction IA écrite sur mesure. Remplacée par `ARC-73` à `ARC-79` (`ADR-10`). |
| `ARC-40` | **Abandonnée** | Fusionnée dans `ARC-79` (chemin manuel équivalent pour toute fonction IA). |

---

## 5. `ADR-5` — Couche de présentation et gestion des assets

### Décision

> **Rendu serveur Twig, enrichi par Stimulus et Turbo. Assets construits via Symfony Reprise. Composants riches ciblés uniquement là où la complexité d'interaction le justifie.**

### Symfony Reprise — fait vérifié

**Reprise** est la couche d'intégration Symfony pour les *bundlers* modernes (Vite, Rsbuild), successeur de Webpack Encore désormais en maintenance. Elle fournit `entrypoints.json`, le versionnement par empreinte, la copie de fichiers statiques, l'intégration du serveur de développement pour le rechargement à chaud, les balises Twig, l'intégration Stimulus, le support CDN et les empreintes d'intégrité (SRI).

> **Statut : expérimental, version 0.x.** La documentation officielle annonce des changements possiblement importants tant que la conception n'est pas stabilisée. La parité fonctionnelle avec Encore est annoncée atteinte depuis la 0.6.

**Conséquence à assumer.** C'est le composant le plus jeune de la stack. Le risque n'est pas fonctionnel — la parité est atteinte — mais celui d'une rupture d'API demandant une reprise de configuration. L'impact est borné : Reprise ne touche que la chaîne de construction des assets, pas le code métier. Une rupture coûte une demi-journée, pas une réécriture.

| Réf | Règle |
|---|---|
| `ARC-25` | Le rendu serveur est le mode par défaut. Un écran ne devient un composant client riche que sur justification écrite d'une contrainte d'interaction. |
| `ARC-26` | Les composants riches consomment l'API interne, jamais un point d'entrée ad hoc. |
| `ARC-27` | Aucune règle métier n'est réimplémentée côté client. Un calcul affiché provient du serveur, ou est trivial et non normatif. |
| `ARC-28` | Un composant riche dégrade proprement : sans JavaScript, la fonction reste accessible. |
| `ARC-60` | La configuration de construction des assets est isolée et documentée, de manière à absorber une rupture d'API de Reprise sans toucher au reste du projet. |

**Où il faut céder sur `ARC-25` :** le plan de charge (grille dense, manipulation directe, 150 collaborateurs × 52 semaines) est un cas légitime de composant riche. C'est **un** écran, pas une architecture.

**Vigilance sur `ARC-27` :** la tentation de recalculer côté client la capacité restante après une affectation est forte. Deux implémentations d'un calcul de capacité produiront deux valeurs différentes, et c'est le plan de charge qui perdra sa crédibilité (`RSQ-12`).

---

## 6. `ADR-6` — Base de données et isolation multi-tenant

### Décision — base de données

> **PostgreSQL.**

| Motif | Détail |
|---|---|
| Analytique | Fonctions de fenêtrage, agrégats, vues matérialisées : le socle du modèle dimensionnel de l'`ADR-9`, sans brique dédiée. |
| Champs personnalisés | JSONB indexable : implémente `EF-REF-27` sans table pivot ni schéma dynamique. |
| Isolation | Sécurité au niveau des lignes (RLS) : seconde barrière indépendante du code applicatif. |
| Historisation | Types intervalles et contraintes d'exclusion : `INV-2` (données à date d'effet) garanti au niveau de la base, pas seulement du code. |
| Vecteurs | Extension `pgvector`, supportée nativement par le composant Store de Symfony AI (`ADR-10`). Aucune base vectorielle dédiée. |
| Exploitation | Disponible en service géré chez tous les hébergeurs européens. |

### Décision — isolation

> **Discriminant de tenant partagé, avec double barrière : filtre applicatif automatique **et** sécurité au niveau des lignes en base.**

Les options « base par tenant » et « schéma par tenant » sont écartées : migrations à répliquer sur N cibles, exploitation multipliée, requêtes transverses coûteuses. Rédhibitoire à une personne (`ARC-3`, `ARC-14`).

| Réf | Règle |
|---|---|
| `ARC-33` | Le filtre de tenant est appliqué automatiquement par l'ORM. Une requête qui l'oublie ne doit pas pouvoir exister. |
| `ARC-34` | PostgreSQL applique en outre une politique RLS, avec le tenant courant positionné en début de transaction. Une requête applicative fautive est bloquée par la base. |
| `ARC-35` | Toute requête SQL écrite à la main (rapports, agrégats) passe une revue de sécurité explicite. C'est le principal contournement du filtre ORM. |
| `ARC-36` | Un jeu de tests automatisés vérifie l'isolation sur chaque entité et chaque opération d'API : un utilisateur du tenant A n'atteint aucune donnée du tenant B, par aucun chemin — écran, API, export, assistant IA. |
| `ARC-37` | L'accès de supervision de l'éditeur passe par un mécanisme distinct, tracé et notifié (`ENF-SEC-8`), jamais par la désactivation du filtre. |
| `ARC-61` | Le tenant courant est positionné à partir du contexte de sécurité en début de requête et **effacé en fin de requête**, en cohérence avec `ARC-47` (mode *worker*). |

**`ARC-34` est ce qui rend le discriminant partagé acceptable en solo.** Le filtre applicatif seul repose sur le pari que personne n'écrira jamais une requête qui le contourne — pari intenable sur cinq ans, seul, sous pression de délai. La RLS transforme une faute de code en erreur bloquante plutôt qu'en fuite silencieuse.

**`ARC-61` est la jonction des deux points les plus dangereux de cette stack :** le mode *worker* conserve l'état entre requêtes, et l'isolation repose sur une variable de session positionnée par requête. Cette combinaison doit être testée explicitement (`ARC-50` + `ARC-36`), avec un scénario dédié : deux requêtes successives de deux tenants différents sur le même processus *worker*.

---

## 7. `ADR-7` — Traitements asynchrones

### Décision

> **Bus de messages du cadriciel, transport en base de données au démarrage.**

| Réf | Règle |
|---|---|
| `ARC-29` | Tout traitement de plus de 3 secondes est asynchrone (`ENF-DES-14`). |
| `ARC-30` | Le transport est la base de données tant que le volume ne l'exige pas. Aucun courtier de messages dédié à exploiter avant que la mesure ne le justifie. |
| `ARC-31` | Tout message est rejouable et idempotent. Un recalcul rejoué deux fois produit le même résultat. |
| `ARC-32` | Les échecs de traitement sont visibles d'un administrateur, avec possibilité de rejeu (`INT-4`). |
| `ARC-62` | Les traitements planifiés (relances, rafraîchissement des vues d'analyse, purges) utilisent le composant de planification du cadriciel, versionné avec le code — pas une table de tâches système de l'hébergeur. |

**Sur `ARC-62` :** une tâche planifiée définie dans la configuration de l'hébergeur n'est ni versionnée, ni testée, ni reproductible entre environnements. C'est une source classique d'écart entre staging et production, et de traitement qui s'arrête silencieusement après une migration.

---

## 8. `ADR-8` — Clean Architecture et DDD, dosés par sous-domaine

> **Cet ADR révise la position de la version précédente du document.** Il y était recommandé d'adopter la couche applicative et de refuser l'essentiel du reste, au motif que Clean Architecture et DDD multiplient le volume de code par 2 à 3 et qu'un développeur seul ne peut pas l'absorber. **Deux faits nouveaux invalident la prémisse de ce raisonnement** : le développement sera assisté par un agent, et l'outillage retenu impose ces conventions mécaniquement. L'argument portait sur le coût d'écriture ; ce coût n'est plus le facteur limitant. La recommandation change.

### Ce qui a changé

| Fait | Effet sur l'objection initiale |
|---|---|
| **Le volume de code n'est plus le coût dominant.** Un agent produit la structure, les objets de transfert, les correspondances, les tests. | L'objection « 2 à 3 fois plus de code » perd l'essentiel de sa force. |
| **claude-craft impose les conventions par des agents de revue et des contrôles de pré-commit**, pour la pile Symfony 8.1, avec des modules dédiés à l'architecture Clean/DDD et au TDD. | L'objection « en solo, une règle non outillée n'est pas une règle » — qui fondait `ARC-63` — est traitée à la source. |
| **Une architecture explicitement structurée est plus lisible par un agent qu'un monolithe implicite.** Des frontières nommées, des cas d'usage nommés, un vocabulaire stable : ce sont exactement les repères dont un agent a besoin pour travailler à contexte limité. | La structure devient un **atout de productivité**, pas seulement un investissement de maintenabilité. |

### Le calcul honnête

Il faut néanmoins nommer ce qui n'a pas changé :

> **Un agent réduit le coût d'écriture du code. Il ne réduit ni le coût de le lire, ni de le comprendre, ni de le déboguer, ni de le migrer à chaque montée de version.**

Deux fois plus de code, c'est deux fois plus de surface à relire, à réviser, à faire passer par `ARC-51` tous les six mois. Ce coût-là subsiste intégralement.

L'arithmétique nette est donc à peu près neutre : le surcoût d'écriture de Clean/DDD est absorbé par l'assistance, le surcoût de lecture demeure, et le bénéfice de maintenabilité est réel. **C'est une décision défendable, et je la retiens.** Ce qu'elle n'est pas, c'est gratuite — et il faut en tirer une conséquence sur le goulot d'étranglement, traitée à l'`ADR-16`.

### Décision

> **Clean Architecture et DDD adoptés, avec une intensité modulée par sous-domaine.**

L'application uniforme est ce qui rend ces approches coûteuses sans contrepartie. Le DDD fournit lui-même l'outil de dosage : la distinction entre domaine cœur, sous-domaines de support et sous-domaines génériques. On ne met pas la même énergie de modélisation sur le calcul de capacité et sur la gestion des types d'absence.

| Sous-domaine | Classement | Traitement |
|---|---|---|
| **Planification** — capacité nette, plan de charge, conflits | **Cœur** | Traitement complet. Modèle de domaine riche, éventuellement distinct de la persistance. Objets-valeurs, invariants, événements. C'est là que se trouve la différenciation du produit. |
| **Temps** — imputation, validation, clôture | **Cœur** | Idem. Règles d'immuabilité (`INV-3`), périodes, valorisation figée : les invariants y sont nombreux et coûteux à violer. |
| **Finance** — valorisation, marge, atterrissage, facturation | **Cœur** | Idem. Les objets-valeurs monétaires et les règles d'arrondi justifient à eux seuls le traitement complet. |
| **Projet** — structure, lots, jalons, avancement | Support | Traitement intermédiaire. Entités riches, objets-valeurs sur les grandeurs, mais pas de séparation domaine/persistance systématique. |
| **Commercial** — opportunités, devis | Support | Idem. |
| **RH** et **Recrutement** | Support | Idem, avec une attention particulière aux règles d'habilitation, qui sont ici la complexité réelle. |
| **Référentiels** — organisation, calendriers, types, paramétrage | **Générique** | Traitement minimal. C'est du CRUD paramétré. Y appliquer un modèle de domaine séparé produit de la cérémonie pure. |
| **Pilotage** — restitution | **Générique (lecture)** | Modèle de lecture dédié (`ADR-9`). Aucun modèle de domaine : ce module ne décide de rien, il agrège. |

**Cette modulation est orthodoxe, pas une dilution.** Elle est exactement ce que la méthode prescrit : concentrer l'effort de modélisation là où le métier est complexe et différenciant, acheter ou simplifier ailleurs. Appliquer le traitement complet aux types d'absence n'est pas du DDD rigoureux, c'est du DDD mal appliqué.

| Réf | Règle |
|---|---|
| `ARC-100` | Le classement de chaque module (cœur / support / générique) est décidé et **documenté au lot 0**, et il gouverne l'intensité de traitement attendue. Un module non classé est traité comme générique. |
| `ARC-101` | La séparation entre modèle de domaine et modèle de persistance est **réservée aux modules cœur**. Ailleurs, les entités de persistance portent le comportement. |
| `ARC-102` | Le classement est révisable : un module qui accumule des règles métier remonte de générique à support, ou de support à cœur. La révision est tracée. |

### Ce qui reste adopté sans réserve

| Pratique | Portée |
|---|---|
| **Couche applicative / cas d'usage** | Tous les modules. `ARC-15` à `ARC-19`. |
| **Modules à frontières explicites** | Tous. Un module par domaine du chapitre 04. |
| **Langage partagé** | Tous. Le vocabulaire du glossaire (annexe 10.1) est celui du code. |
| **Objets-valeurs sur les grandeurs** | Tous. `Money`, `Duration`, `Period`, `TenantId`, `Charge`, `Taux`. C'est là que se logent les bogues coûteux. |
| **Invariants portés par le modèle** | Tous. Une entité ne doit pas pouvoir exister dans un état invalide. |
| **Événements de domaine** | Cœur et support. Ils sont désormais **structurants** : ils alimentent le modèle analytique (`ADR-9`). |
| **TDD** | Tous. Imposé par l'outillage, et c'est la contrepartie indispensable du développement assisté (`ADR-16`). |

### Ce qui reste refusé

| Pratique refusée | Motif |
|---|---|
| **Sourçage d'événements** | `INV-7` s'obtient par un journal applicatif. Les événements de domaine servent aux projections analytiques, pas de source de vérité. |
| **Couche anti-corruption entre modules internes** | Pertinente face à un système externe hérité, pas entre deux modules du même dépôt. |
| **Ports pour ce que le cadriciel abstrait déjà** | Horloge, courriel, système de fichiers, journalisation. |
| **Modèle de domaine séparé sur les modules génériques** | `ARC-101`. |

### La garantie mécanique reste indispensable

| Réf | Règle |
|---|---|
| `ARC-63` | Les frontières de modules et de couches sont vérifiées automatiquement par un outil d'analyse de dépendances (`deptrac` ou équivalent) en intégration continue. Une violation fait échouer la construction. |
| `ARC-64` | La couche applicative ne dépend d'aucune classe du cadriciel liée au transport HTTP. |
| `ARC-65` | Aucun module ne dépend d'un autre autrement que par un contrat explicitement déclaré. |
| `ARC-66` | Aucune classe exposée comme ressource d'API n'est une entité de persistance. |
| `ARC-67` | Analyse statique au niveau maximal, sans exception non justifiée. |

**Pourquoi conserver `ARC-63` alors que l'outillage impose déjà les conventions.** Les agents de revue sont probabilistes : ils détectent la plupart des écarts, pas tous, et pas de manière reproductible. Un analyseur de dépendances est déterministe : il détecte tous les écarts qu'on lui a déclarés, à chaque construction, identiquement. **Les deux sont complémentaires et aucun ne remplace l'autre.** Un agent de revue attrape ce qu'on n'avait pas pensé à déclarer ; l'analyseur garantit ce qu'on a déclaré.

---

## 8bis. `ADR-16` — Conventions de développement assisté par agent

> Le développement assisté est désormais un **fait structurant du projet**, au même titre que le choix du cadriciel. Il change le facteur limitant, et donc les mesures qui protègent la qualité.

### Le déplacement du goulot d'étranglement

| Avant | Après |
|---|---|
| Le facteur limitant est la **capacité d'écriture**. | Le facteur limitant est la **capacité de relecture et de décision**. |
| Le risque est de ne pas produire assez de code. | Le risque est de produire du code correct, testé, bien structuré — **que personne n'a vraiment lu**, et qui fait autre chose que ce qu'on voulait. |
| La qualité se protège par la discipline personnelle. | La qualité se protège en rendant l'intention **explicite et vérifiable par machine**. |

Ce déplacement a une conséquence directe : **tout ce qui rend l'intention machine-vérifiable devient rentable au-delà de son coût habituel.** Un test qui exprime une règle de gestion, une règle d'architecture déclarée, une contrainte de base de données : ce sont les seuls dispositifs qui tiennent quand le volume de code produit dépasse la capacité de relecture exhaustive.

### Décision

| Réf | Règle |
|---|---|
| `ARC-103` | Les **règles de gestion du chapitre 04** (`RG-*`) sont chacune couvertes par au moins un test nommé d'après elle. Le test est la spécification exécutable de la règle ; le code en est une implémentation parmi d'autres. |
| `ARC-104` | Les **invariants du chapitre 06** (`INV-1` à `INV-8`) sont, chaque fois que c'est possible, garantis par une contrainte de base de données et pas seulement par du code applicatif. Une contrainte de base ne peut pas être contournée par une génération de code maladroite. |
| `ARC-105` | Les **conventions du projet** — classement des modules (`ARC-100`), vocabulaire du glossaire, patrons imposés, patrons interdits (chapitre 11 § 5) — sont écrites dans un fichier de conventions versionné, à la racine du dépôt, et tenues à jour. C'est le contrat entre l'auteur et l'agent. |
| `ARC-106` | Le **périmètre de sécurité** ne délègue pas : les règles d'habilitation (`HAB-1` à `HAB-6`), l'isolation multi-tenant (`ARC-33` à `ARC-37`) et la construction de contexte IA (`ARC-73`) sont écrites, relues ligne à ligne, et couvertes par des tests écrits à la main. **Aucune de ces règles n'est acceptée sur la seule foi d'une génération.** |
| `ARC-107` | Toute génération de code est accompagnée de ses tests **dans le même incrément**. Du code sans test n'est pas intégré, quelle que soit son origine. |
| `ARC-108` | Le taux de couverture n'est jamais atteint par génération de tests sur du code existant. Les tests des règles critiques sont écrits à partir de l'exigence, pas à partir de l'implémentation. |

**`ARC-106` est la limite à ne pas franchir.** Le développement assisté est excellent sur le code structurel, répétitif, et sur les tests. Il est dangereux sur les règles dont la violation est silencieuse : une habilitation manquante ne produit aucune erreur, elle produit un accès. Les deux risques de sécurité de criticité maximale du produit — la fuite inter-tenant (`RSQ-2`) et l'exposition indirecte par une fonction IA (`ENF-SEC-6`) — sont exactement de cette nature. Ils se traitent à la main, se relisent à la main, et se testent par un test d'intrusion humain.

**`ARC-108` traite un piège spécifique.** Générer des tests à partir du code produit une couverture élevée qui ne prouve rien : les tests décrivent ce que le code fait, y compris ses bogues. Sur les règles critiques (valorisation, capacité, habilitations), le test doit être écrit depuis l'exigence du chapitre 04, avant ou indépendamment de l'implémentation. `ENF-MAINT-1` doit être lu dans ce sens.

### Ce que l'outillage retenu apporte — fait vérifié

**claude-craft** (`@the-bearded-bear/claude-craft`) est un cadre de développement assisté installant règles, agents, commandes et modules de bonnes pratiques dans le dépôt. Il annonce le support de **Symfony 8.1**, des modules dédiés à l'architecture Clean/DDD et au TDD, et l'application des conventions par des agents de revue et des contrôles de pré-commit. `[à vérifier au démarrage]`

C'est un choix cohérent avec la stack et il traite directement l'objection de discipline en solo. Deux points de vigilance :

| Réf | Vigilance |
|---|---|
| `ARC-109` | Les conventions installées par l'outillage sont **génériques**. Celles qui sont propres à HotOnes — classement des modules, invariants, règles d'habilitation, patrons interdits du chapitre 11 — doivent être ajoutées explicitement (`ARC-105`). Un cadre générique ne connaît pas `HAB-5` ni `INV-2`. |
| `ARC-110` | Les contrôles apportés par l'outillage ne dispensent pas des étapes bloquantes de la chaîne d'intégration (`ADR-12`). Un contrôle de pré-commit local peut être contourné ; une étape d'intégration continue ne le peut pas. |

### La conséquence sur le § 0.1

L'assistance au développement modifie l'arithmétique de la contrainte de ressource, mais moins qu'il n'y paraît. Sur les 1 050 j·h du chapitre 08, l'écriture de code représente de l'ordre de 55 à 60 % ; le reste est de la décision produit, du design, du test auprès d'utilisateurs, de l'exploitation, de la conformité et du support — postes sur lesquels l'assistance ne change rien ou peu.

Même avec un facteur 2 sur la production de code, l'effet global se situe autour de **1,4 à 1,6**. Les 8 à 12 ans en solo du § 0.1 deviennent **5 à 8 ans**. C'est mieux, ce n'est pas suffisant : **`ARB-20` reste le premier arbitrage à trancher.**

## 9. `ADR-9` — Modèle analytique en étoile, dès le lot 1

> **Cet ADR révise la position de la version précédente.** Il y était recommandé de concevoir le modèle dimensionnel dès le lot 1 mais de l'implémenter en vues matérialisées, en ne matérialisant des tables de faits que sur mesure. **L'objection du sponsor est fondée sur un point que j'avais sous-estimé** : une vue matérialisée PostgreSQL ne se rafraîchit pas de manière réellement incrémentale — le rafraîchissement recalcule l'ensemble. À faible volumétrie c'est indolore ; avec l'accumulation de l'historique, ça se dégrade continûment. Le niveau intermédiaire que je proposais est donc une étape qu'il faudra quitter, et la quitter est plus coûteux que de ne pas y passer.

### Décision

> **Schéma en étoile physique dès le lot 1, alimenté exclusivement par projection d'événements de domaine, avec commande de reconstruction complète vérifiée en intégration continue.**

Aucune brique décisionnelle dédiée : tout tient dans PostgreSQL (`ADR-6`), conformément à l'orientation du sponsor.

### Le vrai bénéfice de cette décision

La justification par la performance est correcte mais secondaire : à la volumétrie du chapitre 05, un modèle transactionnel bien indexé tiendrait plusieurs années. **Le bénéfice principal est ailleurs, et il est plus solide** :

> **Un schéma en étoile impose que chaque indicateur ait exactement une définition.**

Le risque `RSQ-5` — les chiffres ne se réconcilient pas, la direction perd confiance — ne vient presque jamais d'un problème de performance. Il vient de ce que « la marge » est calculée à trois endroits avec trois conventions légèrement différentes. Un modèle dimensionnel, avec des grains documentés et des dimensions conformes, rend cette divergence structurellement impossible : il n'y a qu'un endroit où la mesure existe. C'est aussi ce qui rend `EF-PIL-5` (traçabilité de tout indicateur) et `EF-PIL-6` (définition de calcul exposée) tenables plutôt qu'aspirationnels.

### Les trois garanties qui rendent la décision sûre

Un schéma en étoile alimenté incrémentalement **divergera** de la réalité transactionnelle. Ce n'est pas une hypothèse, c'est une certitude : un événement manqué, un traitement rejoué, une correction rétroactive, une migration. La question n'est pas de l'éviter mais de le **détecter automatiquement**. Trois dispositifs, non négociables :

| Réf | Garantie |
|---|---|
| `ARC-111` | **Alimentation par projection uniquement.** Les tables de faits sont écrites exclusivement par des gestionnaires de projection réagissant aux événements de domaine. Aucun code métier n'écrit jamais directement dans une table de faits. |
| `ARC-112` | **Commande de reconstruction complète.** Une commande reconstruit intégralement le modèle analytique à partir du seul modèle transactionnel. C'est elle qui prouve que `ARC-70` (source de vérité unique) est vrai plutôt que déclaré. |
| `ARC-113` | **Test de non-divergence en intégration continue.** Sur un jeu de données de référence : exécuter les projections incrémentales, puis la reconstruction complète, puis comparer. **Toute différence fait échouer la construction.** |
| `ARC-114` | **Réconciliation périodique en production.** Un contrôle compare, sur un échantillon de mesures et de périodes, les agrégats du modèle analytique et ceux calculés directement sur le modèle transactionnel. Toute divergence déclenche une alerte (`ARC-94`). |
| `ARC-115` | La reconstruction complète doit s'exécuter en un temps compatible avec une fenêtre de maintenance, sur un tenant de taille maximale. Mesuré, pas supposé. |

**`ARC-113` est la contribution décisive de cet ADR.** Sans elle, la divergence se découvre lorsqu'un dirigeant compare deux chiffres en comité — c'est-à-dire trop tard, et de la pire manière. Avec elle, elle se découvre à la construction suivante. Le coût est d'une journée de mise en place ; c'est le meilleur rapport de tout le chapitre.

**`ARC-112` a un second usage :** elle est le chemin de reprise après tout incident de projection, toute correction rétroactive (`EF-FIN-5`) et toute réouverture de période clôturée (`EF-REF-23`, `RG-FIN-6`). Sans elle, ces cas se traitent à la main en SQL, en production, sur des données financières.

### Le modèle dimensionnel

**Dimensions conformes** — partagées par tous les faits, ce qui est la propriété qui donne sa valeur au modèle :

| Dimension | Nature | Historisation |
|---|---|---|
| `D_Temps` | Jour, semaine ISO, mois, trimestre, exercice, jour ouvré | Pré-remplie sur 10 ans |
| `D_Tenant` | — | Fixe |
| `D_Collaborateur` | Identité professionnelle, rattachement, statut | **Évolution lente historisée** |
| `D_Profil` | Profil, coût, taux de vente | **Évolution lente historisée** |
| `D_Projet` | Projet, type, mode de contractualisation, statut | Évolution lente historisée |
| `D_Lot` | Lot, rattachement au projet | Évolution lente historisée |
| `D_Client` | Compte, groupe, secteur | Évolution lente historisée |
| `D_UniteOrganisationnelle` | Entité, pôle, équipe | **Évolution lente historisée** |
| `D_TypeActivite` | Facturable, interne, absence, formation | Fixe |
| `D_Competence` | Référentiel de compétences | Évolution lente |

**Faits :**

| Fait | Grain | Mesures principales |
|---|---|---|
| `F_Imputation` | Une imputation validée | durée, coût valorisé, prix valorisé, marge |
| `F_Affectation` | Un collaborateur × un lot × une semaine | charge planifiée, nature (ferme / probable) |
| `F_Capacite` | Un collaborateur × une semaine | capacité brute, absences, charge interne, capacité nette |
| `F_AvancementProjet` | Un lot × une date de relevé | budget, consommé, avancement déclaré, reste à faire, atterrissage |
| `F_Facturation` | Une ligne de facture | montant HT, TVA, échéance, date d'encaissement |
| `F_Opportunite` | Une opportunité × une date de relevé | montant, probabilité, montant pondéré, charge par profil |

`F_AvancementProjet` et `F_Opportunite` sont des faits **photographiques** : ils capturent un état à une date, ce qui est précisément ce qui permet `EF-PRJ-16` et `EF-FIN-10` (courbe d'évolution de l'atterrissage). Les autres sont transactionnels.

### Règles de modélisation

| Réf | Règle |
|---|---|
| `ARC-68` | Le grain de chaque fait est **défini et documenté avant toute implémentation**. Un grain ambigu produit des doubles comptages irrattrapables. |
| `ARC-69` | Les dimensions à évolution lente sont historisées par versions datées, en cohérence avec `INV-2`. Un fait est rattaché à la **version de dimension en vigueur à sa date**, jamais à la version courante. |
| `ARC-70` | Le modèle analytique est **dérivé** du modèle transactionnel, jamais saisi indépendamment. Garanti par `ARC-111` et prouvé par `ARC-112`. |
| `ARC-71` | Tout indicateur du chapitre 04 doit être exprimable comme une agrégation sur ce modèle. Un indicateur qui ne s'y exprime pas signale un manque du modèle, pas un cas particulier à coder à part. |
| `ARC-72` | Les projections sont incrémentales et déclenchées par événement, jamais par recalcul périodique global. Cf. `ENF-PERF-5` (15 minutes). |
| `ARC-116` | Les faits portent des clés de substitution vers les versions de dimension, jamais les identifiants métier directement. C'est ce qui rend `ARC-69` possible. |
| `ARC-117` | Les faits arrivant en retard (validation tardive, correction rétroactive) sont rattachés à leur **date métier**, pas à leur date de traitement. Le cas est traité explicitement, pas découvert. |
| `ARC-118` | Les dates sont stockées en temps universel et restituées dans le fuseau du tenant. La dimension temps est construite dans le fuseau du tenant. Un décalage d'un jour sur une clôture mensuelle est un incident financier. |
| `ARC-119` | Le modèle analytique porte le discriminant de tenant et est soumis à la même double barrière d'isolation que le modèle transactionnel (`ARC-33`, `ARC-34`). |

**`ARC-119` mérite une insistance.** Un modèle analytique est la cible la plus attractive d'une fuite inter-tenant : il concentre des données agrégées de marge et de rémunération, et il est fréquemment interrogé par du SQL écrit à la main (`ARC-35`) — c'est-à-dire hors du filtre ORM. L'isolation doit y être aussi rigoureuse qu'ailleurs, et testée par `ARC-36`.

**`ARC-71` est un test de complétude gratuit.** Passer les indicateurs du chapitre 04 au crible de ce modèle, sur le papier, au lot 0, révèle les manques avant qu'ils ne coûtent une migration.

### Coût et calendrier

| Étape | Contenu | Charge | Lot |
|---|---|---|---|
| Conception | Grains, dimensions, règles d'historisation, passage au crible de `ARC-71` | 4 à 6 j | **0** |
| Socle | Dimensions, `D_Temps`, mécanisme de projection, commande de reconstruction, test de non-divergence | 8 à 12 j | 1 |
| Faits du lot 1 | `F_Imputation`, `F_Capacite`, `F_AvancementProjet` | 5 à 8 j | 1 |
| Faits du lot 2 | `F_Affectation`, `F_Facturation` | 4 à 6 j | 2 |
| Faits du lot 3 | `F_Opportunite` | 2 à 3 j | 3 |
| Réconciliation en production | `ARC-114` | 2 j | 2 |

**Surcoût par rapport à l'approche en vues matérialisées : de l'ordre de 12 à 18 jours, concentrés sur les lots 0 et 1.** C'est un investissement réel et il est justifié : il achète la garantie d'unicité de définition des indicateurs, et il évite une migration ultérieure sur des données financières en production — opération dont le coût et le risque sont sans commune mesure.

### Quand une brique dédiée deviendrait justifiée

Cette décision tient tant que PostgreSQL suffit. Les seuils qui feraient reconsidérer, à mesurer et non à supposer :

- Reconstruction complète (`ARC-115`) dépassant la fenêtre de maintenance sur le plus gros tenant.
- Requêtes de tableau de bord dépassant `ENF-PERF-3` malgré l'indexation et l'agrégation.
- Besoin d'analyse transverse à tous les tenants (`EVO-4`), qui change la volumétrie d'un ordre de grandeur.

Aucun de ces seuils n'est atteignable avant plusieurs années à la volumétrie du chapitre 05.

## 10. `ADR-10` — Socle d'accès aux modèles d'IA

> **Cet ADR révise la position formulée dans la version précédente de ce document.** Il y était recommandé d'écrire une couche d'abstraction interne plutôt que d'employer un cadriciel d'IA tiers, au motif que ces cadriciels sont jeunes, larges et exposés à un risque d'abandon. La vérification effectuée le 30 août 2026 invalide ce raisonnement : **Symfony AI est un ensemble de composants de premier rang de l'écosystème Symfony**, documenté sur le site officiel, et il couvre précisément les besoins du produit. L'objection ne tient plus. La recommandation change.

### Fait vérifié

| Composant | Objet |
|---|---|
| **Platform** | Interface unifiée vers les fournisseurs de modèles : OpenAI, Anthropic, Google Gemini, Azure OpenAI, AWS Bedrock, Mistral, **Ollama** (local / auto-hébergé). |
| **Agent** | Appel d'outils, mémoire, flux de travail agentiques. |
| **Chat** | Interaction et historisation des conversations. |
| **Store** | Abstraction de stockage vectoriel. Supporte **PostgreSQL avec pgvector**, ainsi qu'une trentaine d'autres cibles. |
| **AI Bundle** | Intégration Symfony de l'ensemble. |
| **MCP Bundle** | Intégration du protocole MCP. |

Capacités annoncées : multi-fournisseur, appel d'outils, RAG, sortie structurée, multimodal, flux continu, gestion de mémoire, outils de test (agents et plateformes simulés).

### Décision

> **Symfony AI (composants Platform, Agent, Store et le bundle d'intégration) comme socle d'accès aux modèles. Une fine couche interne au-dessus, pour ce que le produit exige et que le composant ne porte pas.**

Ce que la couche interne ajoute — et qu'aucun composant générique ne fournira :

| Réf | Règle |
|---|---|
| `ARC-38` | Aucun appel direct à un fournisseur depuis le code métier. Point de passage unique (`ARC-5`). |
| `ARC-73` | **Construction de contexte sous habilitations** : les données transmises à un modèle sont filtrées à la source par les droits de l'utilisateur (`ARC-9`, `ENF-SEC-6`, `HAB-5`). C'est spécifique au produit, aucun composant ne peut le porter. |
| `ARC-74` | **Comptage et plafonnement par tenant et par fonction** (`ENF-IA-5`), avec dégradation gracieuse au plafond. |
| `ARC-75` | **Journalisation** : fonction appelante, utilisateur, tenant, périmètre de données mobilisé, modèle employé, jetons consommés, latence, coût (`ENF-IA-4`, `EVO-2.3`). |
| `ARC-76` | **Citation des sources** : les enregistrements ayant alimenté une réponse sont conservés et restituables (`ENF-IA-1`, `ARC-10`). |
| `ARC-77` | **Assemblage séparé** : les valeurs chiffrées sont calculées par le système et insérées dans le texte produit par le modèle, jamais générées par lui (`ENF-IA-3`, `ARC-11`). |
| `ARC-78` | **Commutateur par tenant et par fonction** (`ENF-IA-9`, `ARC-13`). |
| `ARC-79` | Toute fonction IA a un chemin manuel équivalent, testé (`ENF-DISPO-5`). |
| `ARC-41` | Les vecteurs sémantiques sont stockés dans PostgreSQL via `pgvector`, à travers le composant Store. Aucune base vectorielle dédiée. |

### Le modèle de clés fournies par le client

> **Chaque tenant paramètre ses propres clés d'API (Anthropic, Google, OpenAI, au choix) dans son espace d'administration. Une entrée « modèle local » (point d'accès compatible, type Ollama) est prévue dès la conception.**

C'est une bonne décision, et elle a des conséquences précises qu'il faut assumer.

**Ce qu'elle apporte :**

| Bénéfice | Portée |
|---|---|
| Le coût d'inférence disparaît du compte de l'éditeur | Neutralise `CTR-4` et une partie de `RSQ-7`. À une personne sans trésorerie, c'est décisif. |
| L'éditeur n'est plus donneur d'ordre du traitement chez le fournisseur | Le client contractualise directement. Simplifie la chaîne de sous-traitance `ENF-RGPD-6`. |
| Souveraineté par le client | Le client choisit son fournisseur, sa région, ses conditions. Répond à `CTR-5` et `ARB-3` sans que l'éditeur ait à trancher. |
| Le modèle local devient une entrée de configuration | `EVO-2` n'est plus un chantier mais un paramètre. |

**Ce qu'elle coûte — à traiter, pas à découvrir :**

| Réf | Conséquence | Traitement |
|---|---|---|
| `ARC-80` | Un tenant sans clé n'a aucune fonction IA. Toutes les fonctions IA doivent donc être **strictement optionnelles**, et le produit pleinement utilisable sans elles. | Déjà exigé par `ENF-IA-9`. Cohérent — cette décision le rend structurel. |
| `ARC-81` | La qualité varie selon le fournisseur et le modèle choisis par le client. Une invite réglée pour l'un se comporte différemment chez l'autre. | Jeu de cas de test par fonction (`EVO-2.5`), exécuté contre chaque fournisseur supporté. **Sans cela, la qualité est ingérable et le support devient impossible.** |
| `ARC-82` | Les clés sont des secrets de haute valeur : chiffrées au repos, jamais journalisées, jamais réaffichées après saisie, révocables, avec test de validité à l'enregistrement. | Exigence de sécurité de premier rang. Une fuite de clés clients est un incident majeur. |
| `ARC-83` | Le client doit être informé, dans le produit, de **quelles données sont transmises à quel fournisseur, pour quelle fonction**. | `ENF-RGPD-10`. C'est aussi ce qui rend la décision défendable devant un DPO client. |
| `ARC-84` | Le support éditeur doit pouvoir distinguer un défaut du produit d'un problème de clé, de quota ou de disponibilité du fournisseur, sans accéder à la clé. | Diagnostic exposé à l'administrateur du tenant : dernier appel, code de retour, quota. |

**La contrepartie commerciale à ne pas ignorer.** Ce modèle transfère au client une friction d'installation (obtenir une clé, la payer, la renouveler) et rend la promesse produit conditionnelle. « HotOnes pré-remplit votre saisie de temps » devient « … si vous fournissez une clé ». Sur un segment de 10 à 30 personnes sans DSI, cette friction est réelle. **Recommandation : prévoir, à terme, une offre optionnelle avec inférence incluse** pour les tenants qui ne veulent pas gérer de clé — l'architecture le permet sans modification, il ne s'agit que d'une clé détenue par l'éditeur et refacturée. À arbitrer avec le modèle tarifaire. `[ARB-24]`

---

## 11. `ADR-11` — Environnement de développement

### Décision

> **Environnement conteneurisé, reproductible en une commande.**

| Réf | Règle |
|---|---|
| `ARC-85` | L'environnement complet (application, base, cache, courrier de test, worker) démarre en une commande depuis un dépôt fraîchement cloné. |
| `ARC-86` | L'image applicative de développement est **la même base** que celle de production (FrankenPHP), afin que le mode *worker* soit exercé en développement (`ARC-50`). |
| `ARC-87` | Les données de test représentatives des trois tailles de tenant sont régénérables par une commande (`ENF-MAINT-5`). |
| `ARC-88` | Aucun secret réel n'est présent dans le dépôt ni dans les images. Les secrets locaux sont des valeurs factices. |

**Sur `ARC-86` :** développer en mode requête classique et déployer en mode *worker* est la meilleure façon de découvrir les fuites d'état (`ARC-47`) en production. La parité des environnements n'est pas un confort, c'est la condition de validité de tout ce qui est écrit à l'`ADR-2`.

---

## 12. `ADR-12` — Intégration et déploiement continus

### Décision

> **GitHub Actions.** Le plan gratuit inclut un quota mensuel de minutes pour les dépôts privés, très largement suffisant pour un projet solo. **Quota exact à vérifier au démarrage** — les conditions évoluent.

### Étapes obligatoires de la chaîne

| Étape | Bloquant | Réf associée |
|---|---|---|
| Analyse statique niveau maximal | Oui | `ARC-67` |
| Vérification des frontières d'architecture | Oui | `ARC-63` à `ARC-66` |
| Style de code | Oui | — |
| Tests unitaires et applicatifs, seuil de couverture ≥ 80 % sur les règles critiques | Oui | `ENF-MAINT-1` |
| Tests d'isolation multi-tenant | Oui | `ARC-36` |
| **Tests exécutés une seconde fois en mode *worker*** | Oui | `ARC-50` |
| Détection de dépréciations — tolérance zéro | Oui | `ARC-51` |
| Audit des dépendances | Oui | `ENF-SEC-11` |
| Détection de secrets exposés | Oui | `ARC-92` |
| Tests de bout en bout sur les parcours critiques | Oui | — |
| Tests de performance sur jeu de volumétrie cible | Périodique | `ENF-PERF-*` |

| Réf | Règle |
|---|---|
| `ARC-89` | Aucune fusion sur la branche principale sans chaîne verte. Y compris en solo — surtout en solo. |
| `ARC-90` | Le déploiement en staging est automatique depuis la branche principale ; le déploiement en production est déclenché explicitement. |

---

## 13. `ADR-13` — Hébergement et environnements

> **Réserve levée.** La version précédente signalait deux risques sur Railway : un plan gratuit inexploitable en staging permanent, et une région européenne non confirmée. Le sponsor utilise Railway en plan Hobby, en zone euro, depuis longtemps. **Les deux points sont réglés par l'usage établi.** L'objection tombe.

### Décision

> **Staging sur Railway, plan Hobby, région européenne. Production sur hébergement européen en services gérés. Aucune donnée réelle en staging.**

| Réf | Règle |
|---|---|
| `ARC-42` | Hébergement et traitements dans l'Union européenne (`ENF-RGPD-7`, `CTR-5`). |
| `ARC-43` | Base de données, cache et stockage d'objets en services gérés. Aucune sauvegarde, mise à jour ou bascule à exploiter soi-même. |
| `ARC-44` | Pas d'orchestrateur de conteneurs. Une plateforme applicative ou un déploiement conteneurisé simple suffit à la volumétrie cible. |
| `ARC-45` | Environnements distincts : développement, staging, production (`ENF-MAINT-2`). |
| `ARC-46` | Sauvegardes automatiques avec restauration testée trimestriellement (`ENF-DISPO-2`). Une sauvegarde jamais restaurée n'est pas une sauvegarde. |
| `ARC-91` | **Le staging ne contient jamais de données réelles.** Uniquement des jeux générés (`ARC-87`) ou anonymisés de manière irréversible. Cette règle vaut indépendamment de la région : elle découle de `ENF-RGPD-4` (minimisation), pas de la localisation. |
| `ARC-120` | La tarification étant à l'usage, la consommation du staging est vérifiée mensuellement. Un environnement qui consomme au repos est une dérive silencieuse. |

### Deux points restés ouverts

**La production est une décision distincte du staging.** Railway convient à un staging ; l'arbitrage de production doit intégrer les engagements du chapitre 05 — `ENF-DISPO-1` (99,5 % en heures ouvrées), `ENF-DISPO-2` (RPO ≤ 1 h), `ENF-DISPO-3` (RTO ≤ 4 h) — qui sont contractualisés envers les tenants. À instruire au lot 2, quand la mise en service pilote approche, pas maintenant. `[ARB-25]`

**Le support natif de FrankenPHP en mode *worker*** sur la plateforme de staging est à vérifier concrètement, pas en principe : c'est la configuration qui porte `ARC-50` (tests en mode *worker*) et `ARC-86` (parité des environnements). Un staging qui exécute l'application autrement que la production annule l'essentiel du bénéfice de `ARC-86`. Vérification à faire au lot 0, en une demi-journée.

### Sur la chaîne d'intégration

Le choix de GitHub est bon. Le quota gratuit pour dépôts privés est largement suffisant pour un projet solo — **quota exact à vérifier au démarrage**, les conditions évoluent. Une alternative ne se justifierait que si la souveraineté du code devenait une exigence contractuelle d'un client, ce qui n'est pas le cas aujourd'hui.

## 14. `ADR-14` — Observabilité

### Décision

> **Quatre niveaux, tous disponibles en version gratuite ou libre.**

| Niveau | Outil | Statut | Objet |
|---|---|---|---|
| **Serveur applicatif** | **Ember** | MIT, gratuit | État par fil d'exécution, file du *worker*, plantages, latences par centile, TLS, journaux. Export Prometheus. Recommandé par FrankenPHP. |
| **Erreurs applicatives** | Suivi d'erreurs avec palier gratuit et résidence de données européenne | Palier gratuit | `ENF-SAAS-5`. Vérifier le volume inclus et la disponibilité de la région UE. |
| **Métriques et tableaux de bord** | Prometheus + Grafana (auto-hébergé ou palier gratuit géré) | Libre / palier gratuit | Alimenté par les métriques de FrankenPHP et de l'application. |
| **Développement** | Profileur du cadriciel | Inclus | Diagnostic en développement, jamais activé en production. |

| Réf | Règle |
|---|---|
| `ARC-93` | Les métriques métier sont exposées au même format que les métriques techniques : consommation IA par tenant et par fonction (`ARC-75`, `EVO-2.3`), taux de saisie, échecs de messages, latence des vues d'analyse. |
| `ARC-94` | Une alerte est configurée sur chaque seuil du chapitre 05 (`ENF-PERF-*`, `ENF-DISPO-*`). Un seuil sans alerte n'est pas un engagement. |
| `ARC-95` | La supervision de la mémoire des processus *worker* est active dès la première mise en production (`ARC-49`). |
| `ARC-96` | Une supervision externe de disponibilité, indépendante de l'hébergement, vérifie le service depuis l'extérieur. |

**Sur `ARC-93` :** exposer la consommation IA par fonction dès la première mise en production est ce qui rendra possible, dans deux ans, l'arbitrage sur les modèles locaux (`EVO-2.3`). Cet historique ne se reconstitue pas après coup.

**Sur le profilage :** un outil de profilage applicatif dédié est utile mais non prioritaire. Le profileur du cadriciel en développement et les latences par centile d'Ember en production couvrent l'essentiel du besoin. À réévaluer si un problème de performance résiste au diagnostic. `[à préciser]`

---

## 15. `ADR-15` — Sécurité automatisée

### Décision

> **Défense en profondeur outillée, intégrée à la chaîne d'intégration, en s'appuyant exclusivement sur des outils gratuits ou libres.**

| Couche | Outil | Statut | Objet |
|---|---|---|---|
| Dépendances — vulnérabilités connues | `composer audit` + service de mise à jour automatisée du dépôt | Inclus / gratuit | `ENF-SEC-11` : correction des vulnérabilités critiques sous 15 jours |
| Dépendances — blocage préventif | Paquet de conseils de sécurité empêchant l'installation de versions vulnérables | Libre | Barrière à l'installation, pas seulement à l'audit |
| Analyse statique de sécurité | Analyseur statique au niveau maximal + analyse de teinte (*taint analysis*) | Libre | Injection SQL, XSS, chemins non filtrés |
| Analyse de motifs | Analyseur de motifs de sécurité (règles PHP/Symfony) | Palier gratuit / CLI libre | Complète l'analyse statique sur les schémas connus |
| Secrets exposés | Détecteur de secrets sur l'historique et à chaque validation | Libre | `ARC-92` |
| Conteneurs | Scanner de vulnérabilités d'images | Libre | Images de base et dépendances système |
| Dynamique | Scanner dynamique en préproduction, sur les parcours principaux | Libre | Périodique, pas à chaque validation |
| Intrusion | Test d'intrusion externe | Payant, externe | `ENF-SEC-9` — annuel et à chaque évolution du modèle d'habilitation ou des fonctions IA |

| Réf | Règle |
|---|---|
| `ARC-92` | Aucun secret ne figure dans le dépôt, l'historique, les images ou les journaux. Vérifié automatiquement à chaque validation et sur l'historique complet. |
| `ARC-97` | Les vulnérabilités critiques et élevées bloquent la construction. Les autres ouvrent un ticket daté. |
| `ARC-98` | Les tests d'isolation multi-tenant (`ARC-36`) et le test d'habilitation de l'assistant conversationnel (`EF-PIL-19`) sont exécutés à chaque intégration, pas seulement avant les mises en production majeures. |
| `ARC-99` | Les clés d'API des tenants (`ARC-82`) font l'objet d'un contrôle spécifique : chiffrement au repos vérifié par test, absence dans les journaux vérifiée par test. |

**Réserve à énoncer.** L'analyse de code sophistiquée intégrée aux forges est généralement gratuite pour les dépôts publics et payante pour les dépôts privés. Le dépôt de HotOnes sera privé. Les outils libres listés ci-dessus couvrent le besoin, mais leur mise en place demande **2 à 3 jours** de configuration initiale. À budgéter au lot 0, pas à découvrir au lot 2.

**Ce qu'aucun outil ne remplace.** Les deux risques de sécurité les plus graves de ce produit — la fuite inter-tenant (`ENF-SEC-4`) et l'exposition indirecte de données par une fonction IA (`ENF-SEC-6`, `EF-PIL-19`) — ne sont détectés par **aucun** analyseur automatique. Ils relèvent de la conception (`ARC-33` à `ARC-37`, `ARC-73`), de tests écrits à la main (`ARC-36`), et d'un test d'intrusion humain. L'outillage automatique traite les vulnérabilités connues ; il ne traite pas les défauts de conception d'un modèle d'habilitation.

---

## 16. Synthèse : technologies actuelles et retenues

| Domaine | Existant (MVP) | Retenu | Nature |
|---|---|---|---|
| Langage | PHP | PHP 8.4+ (imposé par Symfony 8.1) | Montée de version |
| Cadriciel | Symfony | Symfony 8.1+, branche stable suivie (`ADR-3`) | Continuité + politique de version |
| Serveur applicatif | À confirmer (`AUD-1`) | FrankenPHP mode *worker* + Caddy (`ADR-2`) | **Nouveau, structurant** |
| Architecture applicative | Logique en contrôleurs (à confirmer) | Couche de cas d'usage, cœur API-first (`ADR-1`) | **Changement structurant** |
| Modularité et modélisation | À confirmer | Clean Architecture + DDD dosés par sous-domaine, frontières vérifiées (`ADR-8`) | **Nouveau, structurant** |
| Méthode de développement | — | Assisté par agent, TDD imposé, conventions versionnées (`ADR-16`) | **Nouveau, structurant** |
| Base de données | À confirmer | PostgreSQL + `pgvector` (`ADR-6`) | Migration probable |
| Isolation multi-tenant | Absente | Discriminant partagé + RLS (`ADR-6`) | **Nouveau, structurant** |
| API | Absente ou marginale | API Platform en mode DTO (`ADR-4`) | **Nouveau** |
| Rendu web | Twig + Stimulus / Live Components | Idem + Turbo (`ADR-5`) | Continuité |
| Construction des assets | Encore ou AssetMapper (à confirmer) | Symfony Reprise + Vite (`ADR-5`) — **expérimental 0.x** | **Nouveau, à surveiller** |
| Design system | Absent | Jetons + bibliothèque accessible (`ADR-6` ch. 11) | **Nouveau** |
| Asynchrone | À confirmer | Bus de messages, transport base (`ADR-7`) | Nouveau ou consolidé |
| Modèle analytique | Absent | Schéma en étoile physique, projections d'événements (`ADR-9`) | **Nouveau, structurant** |
| Accès aux modèles d'IA | Absent | Symfony AI + couche produit (`ADR-10`) | **Nouveau, structurant** |
| Vecteurs sémantiques | — | Store + `pgvector` (`ARC-41`) | Nouveau |
| Environnement de développement | À confirmer | Conteneurisé, parité *worker* (`ADR-11`) | Nouveau ou consolidé |
| Intégration continue | À confirmer | GitHub Actions, 11 étapes bloquantes (`ADR-12`) | **Nouveau** |
| Staging | Absent | Railway Hobby, zone euro, sans données réelles (`ADR-13`) | **Nouveau** |
| Observabilité | Absente | Ember + suivi d'erreurs + métriques (`ADR-14`) | **Nouveau** |
| Sécurité automatisée | Absente | 8 couches outillées (`ADR-15`) | **Nouveau** |

Les mentions « à confirmer » relèvent de l'audit `AUD-1` du lot 0.

---

## 17. Le coût de l'avant-garde — à budgéter, pas à découvrir

La stack retenue emploie plusieurs composants récents. C'est un choix assumé, et il est cohérent avec le positionnement du produit. Son coût doit être chiffré plutôt que subi.

| Composant | Maturité | Risque | Provision |
|---|---|---|---|
| Symfony 8.1 | Stable, mais **fin de support janvier 2027** | Montée semestrielle obligatoire | 1-3 j × 2/an, sous condition de `ARC-51` |
| FrankenPHP mode *worker* | Mature, mais change le modèle d'exécution | Fuites d'état, fuites mémoire | 3-5 j de mise au point + `ARC-50` |
| Symfony Reprise | **Expérimental 0.x** | Rupture d'API | 0,5-1 j par rupture ; impact borné aux assets (`ARC-60`) |
| Symfony AI | Récent, périmètre large | Évolution d'API | Couche produit tampon (`ARC-73` à `ARC-79`) |
| API Platform 5.0 | **Alpha** | Ne pas l'employer | Rester sur la branche stable |
| Ember | Jeune mais MIT et simple | Faible | Négligeable |
| Modèle dimensionnel | Conception maison | Grain mal défini, divergence silencieuse | `ARC-68` (grain documenté), `ARC-112` à `ARC-114` (reconstruction et non-divergence) |
| Clean Architecture + DDD | Conventions établies | Volume de code à relire, cérémonie sur les modules génériques | `ARC-100` à `ARC-102` (dosage par sous-domaine), `ARC-63` (frontières outillées) |
| Développement assisté | Outillage récent | Code correct que personne n'a lu | `ADR-16`, en particulier `ARC-106` (le périmètre de sécurité ne délègue pas) |

**Provision globale recommandée : 27 à 43 jours sur les lots 0 et 1** — 15 à 25 j de mise au point de la stack, plus 12 à 18 j de surcoût du schéma en étoile physique (`ADR-9`). C'est le prix de l'avant-garde. Il est raisonnable au regard du bénéfice — mais il doit figurer au chiffrage, faute de quoi il sera pris sur le temps de l'ergonomie de la saisie de temps, qui est le seul poste où ce projet ne peut pas se permettre d'économiser (`RSQ-1`).

---

## 18. Ce qui est explicitement écarté

Refusé au titre de `ARC-3` et `ARC-14`. Chaque exclusion est opposable et ne peut être rouverte que par une décision motivée traitant la contrainte de ressource.

| Écarté | Motif |
|---|---|
| Microservices | Ingérable en solo ; aucun besoin d'échelle le justifiant |
| Front découplé en SPA complète | Double la charge de chaque écran (`ADR-1`) |
| Second langage backend | Contredit `ARC-14` |
| Orchestrateur de conteneurs | Charge d'exploitation sans contrepartie à cette échelle |
| Courtier de messages dédié | Idem, tant que la mesure ne l'exige pas |
| Base vectorielle dédiée | `pgvector` via le composant Store suffit |
| Entrepôt de données ou brique décisionnelle dédiée | Le schéma en étoile dans PostgreSQL suffit à la volumétrie cible (`ADR-9`) |
| Modèle de domaine séparé sur les modules **génériques** | Cérémonie pure sur du CRUD paramétré (`ARC-101`) |
| Sourçage d'événements | `INV-7` s'obtient par un journal applicatif |
| CQRS généralisé sur les modules d'écriture | Le modèle de lecture est celui du pilotage (`ADR-9`) |
| Règles d'habilitation ou d'isolation acceptées sur une génération non relue | `ARC-106` |
| Exposition directe d'entités en API | `ARC-18`, `ARC-55`, vérifié par `ARC-66` |
| API Platform 5.0 en production | Branche alpha |
| Données réelles en staging | `ARC-91` |

---

## 19. Points ouverts

| Réf | Objet | Recommandation | Échéance |
|---|---|---|---|
| `[ARB-20]` | **Trajectoire de ressource** (§ 0.1) | `V2` puis `V3` — mais conserver le socle multi-tenant, qui ne se rétro-adapte pas | **Avant tout le reste** |
| `[ARB-18]` | Version Symfony de départ et cible LTS | Suivre la branche stable, se poser sur la prochaine LTS. **Vérifier le calendrier LTS sur la source officielle** | Lot 0, bloquant |
| `[ARB-24]` | Offre optionnelle avec inférence incluse, pour les tenants ne voulant pas gérer de clé | La prévoir : l'architecture le permet sans modification | Lot 5, avec le modèle tarifaire |
| `[ARB-25]` | **Hébergement de production** (distinct du staging, réglé) | À instruire au lot 2 au regard de `ENF-DISPO-1` à `-3`, qui sont contractualisés envers les tenants | Lot 2 |
| `[à préciser]` | Classement cœur / support / générique de chaque module (`ARC-100`) | À arrêter au lot 0 : il gouverne l'intensité de modélisation | Lot 0 |
| `[à préciser]` | Support de FrankenPHP en mode *worker* sur la plateforme de staging | À vérifier concrètement, une demi-journée. Conditionne `ARC-86` | Lot 0 |
| `[à préciser]` | Bibliothèque de composants du design system | Décision conjointe design / technique | Lot 0 |
| `[à préciser]` | Outil de profilage applicatif en production | Non prioritaire ; à réévaluer sur besoin réel | Lot 2 |
| `[HYP-12]` | L'existant n'a ni modèle multi-tenant ni couche applicative découplée | À confirmer par `AUD-1` | Lot 0 |

---

## 20. Sources des vérifications

Vérifications effectuées le 30 août 2026. **Ces informations évoluent : revérifier au démarrage effectif du projet.**

- [Symfony 8.1 — page de version](https://symfony.com/releases/8.1) — sortie mai 2026, PHP 8.4+, fin de support janvier 2027
- [Symfony — versions maintenues](https://symfony.com/releases) — 8.1 stable, 7.4 LTS, 6.4 LTS
- [Symfony AI — documentation](https://symfony.com/doc/current/ai/index.html) — composants Platform, Agent, Chat, Store, Mate ; bundles AI et MCP
- [Symfony AI — composant Store](https://symfony.com/doc/current/ai/components/store.html) — support de PostgreSQL avec pgvector
- [Introducing Symfony Reprise](https://symfony.com/blog/introducing-symfony-reprise-the-symfony-integration-layer-for-modern-bundlers) — statut expérimental 0.x, Vite et Rsbuild
- [Ember — dépôt du projet](https://github.com/alexandre-daubois/ember) — MIT, tableau de bord Caddy et FrankenPHP
- [Ember — documentation FrankenPHP](https://frankenphp.dev/docs/observability/) — observabilité, métriques, journaux
- [FrankenPHP](https://frankenphp.dev/) — serveur applicatif PHP
- [api-platform/core sur Packagist](https://packagist.org/packages/api-platform/core) — branche 4.3 stable, 5.0 en alpha, support Symfony 7.4 et 8.0+
- [Railway — plans et tarifs](https://docs.railway.com/pricing/plans) — plan gratuit à 1 $ de crédit mensuel, Hobby à 5 $/mois
- [claude-craft](https://github.com/TheBeardedBearSAS/claude-craft) — cadre de développement assisté ; support annoncé de Symfony 8.1, modules Clean/DDD et TDD, agents de revue et contrôles de pré-commit
- [claude-craft — module architecture-clean-ddd Symfony](https://github.com/TheBeardedBearSAS/claude-craft/tree/main/Dev/i18n/en/Symfony/skills/architecture-clean-ddd) — module dédié aux conventions Clean Architecture et DDD
