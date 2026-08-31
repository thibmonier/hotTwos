# 07 — Scénarios de refonte et recommandation

Ce chapitre instruit la question posée en tête de projet : **que fait-on de l'existant ?** Il présente trois scénarios, les évalue sur des critères explicites, et formule une recommandation argumentée.

---

## 1. Le cadre de la décision

### 1.1 Ce que l'on sait

- L'existant est un **MVP partiel** : quelques modules développés, usage réel faible ou nul (`HYP-1`).
- Le socle est Symfony.
- La cible est un **produit SaaS multi-clients** (`HYP-2`), ce que le MVP n'a probablement pas été conçu pour être.
- L'ambition fonctionnelle cible est **substantiellement supérieure** à celle du MVP : multi-tenant, paramétrage 10-150 personnes, socle IA transverse.

### 1.2 Ce qu'il faut établir avant de trancher

Trois éléments manquent pour que la décision soit fondée sur des faits plutôt que sur une préférence :

| Réf | Élément à établir | Comment | Charge |
|---|---|---|---|
| `AUD-1` | **Audit technique de l'existant** : couverture réelle, qualité du code, dette, présence ou non d'un modèle de données multi-tenant, couverture de tests, versions et obsolescence des dépendances. | Revue de code et d'architecture par un tiers ou par le futur responsable technique. | 3 à 5 j |
| `AUD-2` | **Cartographie fonctionnelle réelle** du MVP rapportée aux exigences de ce CDC : quel pourcentage des exigences `M` est déjà couvert, et à quel niveau de qualité. | Confrontation module par module. | 2 à 3 j |
| `AUD-3` | **Mesure des situations de référence** (`OBJ-1` à `OBJ-7`) sur l'organisation pilote. | Relevé sur 4 semaines. | 2 j étalés |

**Recommandation méthodologique :** ne pas engager le lot 1 avant `AUD-1` et `AUD-2`. Une semaine d'audit évite une décision d'architecture qui coûte six mois. Ce chapitre propose une recommandation sous réserve explicite du résultat de cet audit.

---

## 2. Les trois scénarios

### Scénario A — Modernisation progressive du socle existant

Le socle Symfony est conservé. Les modules sont refondus l'un après l'autre. Le multi-tenant et le socle IA sont ajoutés au socle existant.

**Conditions de viabilité :** l'audit doit montrer un code structuré, testé, et surtout un modèle de données compatible avec les invariants `INV-1` (tenant), `INV-2` (historisation à date d'effet) et `INV-3` (imputation immuable).

| Avantages | Inconvénients |
|---|---|
| Réutilisation du travail déjà fait | Les invariants `INV-1` à `INV-3` sont rarement présents dans un MVP mono-organisation |
| Progression visible dès les premières semaines | Rétro-adapter le multi-tenant sur un modèle qui ne l'a pas prévu est un chantier lourd et risqué |
| Continuité de la connaissance du code | La dette du MVP se transmet et contamine le nouveau |
| Coût initial plus faible | Contrainte permanente de compatibilité qui ralentit chaque décision |

**Risque principal :** découvrir au lot 2 que le modèle de données ne supporte ni l'historisation à date d'effet ni l'isolation par tenant, et devoir alors réécrire ce qui a déjà été livré — le pire des deux mondes.

---

### Scénario B — Reconstruction complète (greenfield)

Nouveau produit. L'existant sert de spécification vivante et de source d'apprentissage, pas de base de code.

| Avantages | Inconvénients |
|---|---|
| Invariants structurels posés dès le premier jour | Aucune valeur livrée avant plusieurs mois |
| Multi-tenant et socle IA nativement intégrés | Perte de l'investissement réalisé sur le MVP |
| Choix de socle libre, adapté à la cible SaaS | Risque de reproduire les mêmes erreurs sans le garde-fou de l'existant |
| Pas de compromis de compatibilité | Effet tunnel : le risque projet dominant |
| Dette technique nulle au départ | Coût initial le plus élevé |

**Risque principal :** l'effet tunnel. Un greenfield sans jalon de valeur intermédiaire dérive : la cible s'enrichit à mesure que la livraison s'éloigne, et le projet ne sort jamais.

---

### Scénario C — Reconstruction du socle, reprise sélective des acquis

Nouveau socle technique et nouveau modèle de données, conçus pour le multi-tenant et les invariants. Reprise sélective, **module par module et après évaluation**, de ce qui est réutilisable du MVP : règles de gestion, algorithmes, éléments d'interface, et éventuellement des portions de code adaptables.

L'existant n'est pas la base, il est un **stock de composants candidats**.

| Avantages | Inconvénients |
|---|---|
| Invariants structurels garantis | Nécessite une discipline d'évaluation module par module |
| Récupère la valeur intellectuelle du MVP (les règles métier, qui sont l'essentiel du travail) | Exige un audit préalable sérieux |
| Permet un lotissement à valeur intermédiaire | Coût intermédiaire |
| Découplé de la dette de l'existant | Tentation permanente de « récupérer un peu plus » et de retomber dans le scénario A |

**Risque principal :** la dérive vers A par accumulation de reprises non évaluées. À contenir par une règle explicite : **toute reprise de code est une décision tracée, jamais un réflexe.**

---

## 3. Évaluation comparée

Notation de 1 (défavorable) à 5 (favorable). Pondération proposée, à valider par le sponsor.

| Critère | Poids | A — Modernisation | B — Greenfield | C — Socle neuf + reprise |
|---|---|---|---|---|
| Compatibilité avec les invariants structurels | 5 | 2 | 5 | 5 |
| Aptitude au multi-tenant | 5 | 2 | 5 | 5 |
| Délai avant première valeur livrée | 4 | 4 | 2 | 3 |
| Coût total à 24 mois | 4 | 3 | 2 | 3 |
| Risque projet | 5 | 3 | 2 | 4 |
| Valorisation de l'investissement déjà réalisé | 2 | 5 | 1 | 4 |
| Aptitude à intégrer le socle IA | 3 | 2 | 5 | 5 |
| Capacité à être exploité par une petite équipe | 3 | 3 | 4 | 4 |
| **Total pondéré (sur 155)** | | **89** | **112** | **131** |

**Lecture :** le scénario C domine sur les deux critères de poids maximal (invariants et multi-tenant) tout en conservant un délai de première valeur acceptable et le meilleur profil de risque.

---

## 4. Recommandation

> **Scénario C — reconstruction du socle avec reprise sélective des acquis du MVP.**

### 4.1 Argumentaire

**Trois raisons de ne pas retenir le scénario A.** Le passage d'un outil mono-organisation à un produit SaaS multi-tenant n'est pas une évolution, c'est un changement de nature. L'historisation à date d'effet (`INV-2`) et l'immuabilité des imputations valorisées (`INV-3`) sont des propriétés de modèle qu'on ne rétro-adapte pas sans réécrire le cœur. Et un MVP est, par construction, un objet conçu pour apprendre vite, pas pour durer : en faire un socle produit revient à industrialiser un prototype.

**Une raison de ne pas retenir le scénario B.** L'existant contient l'essentiel de la valeur du travail réalisé : les règles métier, les arbitrages fonctionnels, les erreurs déjà commises et corrigées. Les jeter par principe est un gaspillage. Ce qui doit être reconstruit, c'est le socle, pas la connaissance.

**Ce qui fait la différence du scénario C.** Il sépare deux questions qu'on confond habituellement : *sur quoi construit-on* (le socle : neuf, non négociable) et *que réutilise-t-on* (les acquis : au cas par cas, sur décision tracée).

### 4.2 Conditions de réussite

Le scénario C n'est pas plus sûr en soi. Il l'est **sous cinq conditions**, sans lesquelles il dérive vers A ou vers B :

| Réf | Condition |
|---|---|
| `CDR-1` | Les invariants `INV-1` à `INV-8` sont posés dans le modèle de données du lot 1 et ne sont pas rediscutés ensuite. |
| `CDR-2` | Toute reprise de code du MVP fait l'objet d'une décision tracée, motivée, prise au niveau du responsable technique. Le réflexe par défaut est de ne pas reprendre. |
| `CDR-3` | Le lot 1 livre de la valeur utilisable en production sur une organisation pilote en 4 à 5 mois. Pas de tunnel. |
| `CDR-4` | Le MVP est **arrêté** à la mise en service du lot 1 sur le périmètre concerné. Faire vivre deux produits en parallèle est le scénario qui consomme toute la capacité de l'équipe. |
| `CDR-5` | L'audit `AUD-1` et `AUD-2` est réalisé avant le démarrage et peut remettre en cause cette recommandation. |

**Sur `CDR-4` :** point de vigilance sous-estimé. Une équipe de trois personnes qui maintient l'ancien produit pendant qu'elle construit le nouveau ne construit rien. Si le MVP a des utilisateurs, il faut soit les faire basculer, soit geler complètement sa maintenance — pas un entre-deux.

### 4.3 Ce qui invaliderait la recommandation

Cette recommandation doit être révisée si l'audit établit l'un des points suivants :

- Le MVP dispose déjà d'un modèle multi-tenant et d'une historisation à date d'effet correctement conçus → le scénario A redevient compétitif.
- Le MVP est en réalité **en production** avec des utilisateurs et des données vivantes (`HYP-1` fausse) → un plan de bascule et de reprise devient nécessaire, et le scénario A gagne en attractivité par le coût de la continuité de service.
- La couverture fonctionnelle réelle du MVP dépasse 50 % des exigences `M` de ce CDC avec une qualité satisfaisante → l'arbitrage coût/bénéfice bascule.

---

## 5. Choix de socle technique

Le socle n'est pas arbitré dans ce document — c'est une décision de MOE, pas de MOA. Trois critères doivent la gouverner, dans cet ordre :

1. **`ARC-3` — exploitabilité par 2 à 4 personnes.** Ce critère élimine plus d'options qu'aucun autre et doit être appliqué en premier.
2. **Compétences de l'équipe qui construira et maintiendra.** Un socle techniquement supérieur mais inconnu de l'équipe est un mauvais choix.
3. **Maturité de l'écosystème** sur les besoins structurants : multi-tenant, historisation, contrôle d'accès fin, intégration de modèles d'IA.

La continuité avec Symfony est un argument légitime au titre du critère 2 si l'équipe cible est celle qui a construit le MVP. Elle n'en est pas un si l'équipe change. `[ARB-18]`

**Avertissement :** le choix du socle est souvent l'endroit où un projet de refonte se transforme en projet technique. Il doit être tranché rapidement, sur ces trois critères, et ne plus être rouvert. Le temps passé à comparer des socles n'est pas du temps passé à livrer.
