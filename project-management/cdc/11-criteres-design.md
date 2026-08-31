# 11 — Critères de design et d'expérience

Ce chapitre fixe les critères que le design de HotOnes doit satisfaire. Il ne décrit pas des écrans — c'est le rôle du design system et des maquettes, produits en lot 0 — mais les **règles opposables** auxquelles ces écrans devront se conformer, et les critères mesurables permettant de vérifier qu'ils s'y conforment.

---

## 1. Les deux contraintes fondatrices

Tout ce qui suit découle de deux caractéristiques du produit, énoncées par le sponsor et qui doivent gouverner chaque arbitrage de design.

### C1 — L'appropriation doit être immédiate

Les utilisateurs de HotOnes ne choisissent pas de l'utiliser : leur employeur le leur impose. Ils n'investiront donc aucun effort d'apprentissage volontaire. Un ERP qui exige une formation pour être utilisé sera utilisé au minimum de ce qui est exigible, et contourné partout ailleurs.

**Conséquence opposable :** toute fonctionnalité destinée au persona P1 (collaborateur) doit être utilisable **sans formation ni documentation**, au premier usage. C'est un critère de recette, pas une intention (`ENF-DES-1`).

### C2 — C'est une application du quotidien

HotOnes est ouvert tous les jours, souvent plusieurs fois. Deux conséquences opposées et toutes deux vraies :

- **La vitesse prime sur la découvrabilité.** Un utilisateur quotidien devient expert de son parcours en une semaine. Les affordances pédagogiques qui l'aidaient au jour 1 le ralentissent au jour 30. Le design doit servir l'utilisateur du jour 30, tout en rendant le jour 1 possible.
- **Chaque friction est payée tous les jours.** Une seconde perdue sur l'écran de saisie, c'est 250 secondes par an et par personne, et surtout un signal répété que l'outil coûte plus qu'il ne rend.

**Conséquence opposable :** les actions du quotidien sont accessibles en un geste depuis l'entrée dans l'application, et l'information la plus consultée est affichée sans action préalable (`ENF-DES-2`, `ENF-DES-3`).

---

## 2. Principes directeurs

Six principes, **ordonnés**. En cas de conflit entre deux d'entre eux, celui de rang inférieur l'emporte. Cet ordre est ce qui rend les principes utilisables : des principes non ordonnés ne tranchent rien.

### DP-1 — L'action du jour d'abord

L'écran d'accueil de chaque persona présente **ce qu'il doit faire aujourd'hui**, pas un tableau de bord de son activité. Pour un collaborateur : sa saisie de la veille si elle manque, son planning du jour, une demande de congé en attente. Pour un chef de projet : ses projets en alerte, ses validations à faire.

*Ce que ce principe exclut :* une page d'accueil constituée de graphiques de synthèse. C'est le réflexe par défaut de tous les ERP, et c'est un contresens : personne n'ouvre son outil de gestion pour contempler des courbes, on l'ouvre pour faire quelque chose.

### DP-2 — Rien à apprendre pour la tâche quotidienne

Les parcours quotidiens (saisie, congés, avancement, consultation de planning) utilisent des conventions d'interface universelles et ne comportent aucun vocabulaire propre au produit. Les concepts métier spécifiques — atterrissage, charge probable, capacité nette — sont réservés aux écrans des personas qui les manipulent professionnellement, et systématiquement définis au survol (`EF-PIL-6`).

*Ce que ce principe exclut :* les visites guidées, les tutoriels obligatoires et les infobulles d'accueil. Ce sont des rustines qui traitent le symptôme. Une interface qui a besoin d'une visite guidée pour son parcours quotidien doit être refaite, pas expliquée.

### DP-3 — L'information avant l'action

L'utilisateur voit l'état des choses avant de devoir cliquer. Les compteurs de congés sont affichés sur l'écran de demande, pas derrière un onglet. La consommation du projet est visible depuis l'écran de saisie du chef de projet. Le planning à venir est sur l'écran de saisie du collaborateur (`EF-TMP-27`).

*Ce que ce principe exclut :* les architectures de navigation profondes où chaque information demande deux ou trois clics de contexte.

### DP-4 — Densité assumée, hiérarchie stricte

C'est un outil professionnel utilisé plusieurs heures par semaine par certains personas. La densité d'information y est une qualité, pas un défaut — un plan de charge aéré sur 12 mois est illisible. Mais la densité n'est supportable qu'avec une hiérarchie visuelle très stricte : trois niveaux d'importance maximum par écran, une seule action principale.

*Ce que ce principe exclut :* le design « aéré » emprunté aux applications grand public, qui oblige à faire défiler pour voir une semaine de planning.

### DP-5 — L'erreur est réversible, jamais punitive

L'utilisateur doit pouvoir agir sans crainte. Les actions se défont. Les validations bloquantes sont réduites au minimum réglementaire. Les messages d'erreur disent quoi faire, jamais ce qui a été mal fait.

*Ce que ce principe exclut :* les formulaires qui refusent l'enregistrement partiel, les confirmations en cascade, et les messages de type « Champ obligatoire non renseigné ».

### DP-6 — Cohérence avant élégance

Un même objet se présente de la même façon partout : un projet a la même carte, les mêmes couleurs de statut, les mêmes actions, sur tous les écrans. La cohérence est ce qui permet l'apprentissage par transfert, donc l'appropriation rapide (C1).

*Ce que ce principe exclut :* les écrans conçus indépendamment, chacun optimisé localement. C'est le mode de dégradation naturel d'un produit construit module par module — exactement le mode de construction retenu au chapitre 08. Ce risque est donc structurellement présent et doit être contré par un design system contraignant.

---

## 3. Structure de l'expérience

### 3.1 Architecture de navigation

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-DES-1` | M | Un collaborateur n'ayant jamais vu le produit doit accomplir sa première saisie de temps sans aide, documentation ni formation. | Test sur 5 personnes n'ayant jamais vu le produit : ≥ 4 réussissent en moins de 3 minutes, sans intervention. **Critère bloquant du lot 1.** |
| `ENF-DES-2` | M | Les actions quotidiennes de chaque persona sont accessibles en **un geste** depuis l'entrée dans l'application. | Saisir son temps, poser un congé, consulter son planning : une action chacun depuis l'accueil. |
| `ENF-DES-3` | M | L'écran d'accueil de chaque persona affiche l'information la plus consultée **sans action préalable**, ni filtre à régler, ni sélection à faire. | Vérifié pour les six personas. |
| `ENF-DES-4` | M | La profondeur de navigation n'excède pas 3 niveaux. Toute information doit être atteignable en 3 clics depuis l'accueil. | Audit de l'arborescence à chaque fin de lot. |
| `ENF-DES-5` | S | Une recherche globale permet d'atteindre directement un projet, un client ou un collaborateur, avec accès au clavier. | Accessible par un raccourci depuis n'importe quel écran. |
| `ENF-DES-6` | S | Le contexte de travail est persistant : l'utilisateur retrouve la période, le filtre et le tri qu'il utilisait à sa visite précédente. | Vérifié sur les écrans de planification, de reporting et de saisie. |

**Sur `ENF-DES-6` :** un utilisateur quotidien travaille presque toujours sur le même périmètre. Lui faire régler ses filtres à chaque connexion est la friction la plus fréquente et la moins visible des outils de gestion — et l'une des plus faciles à supprimer.

### 3.2 Hiérarchie de l'information

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-DES-7` | M | Chaque écran comporte **une seule action principale**, visuellement dominante et identique dans son placement à travers le produit. | Revue de design ; aucun écran ne présente deux actions de même poids visuel. |
| `ENF-DES-8` | M | Trois niveaux de hiérarchie visuelle maximum par écran. | Revue de design. |
| `ENF-DES-9` | S | Les alertes et signaux d'attention utilisent un code visuel unique et limité à trois états (information, attention, critique), jamais plus. | Cohérent entre dérive projet, sur-affectation, retard de saisie et facture échue. |
| `ENF-DES-10` | M | Aucun signal visuel ne repose sur la seule couleur. | Cf. `ENF-UX-4` ; vérifié en simulation de déficience de perception des couleurs. |

**Sur `ENF-DES-9` :** un produit qui accumule les codes couleur par module devient illisible. Trois états, un vocabulaire visuel, appliqué partout. Un chef de projet doit reconnaître « ça va mal » sans avoir à se demander ce que signifie l'orange sur cet écran-ci.

### 3.3 Performance perçue

La performance ressentie est un sujet de design autant que de technique. Les seuils techniques sont au chapitre 05 (`ENF-PERF-1` à `-6`) ; voici les règles de design qui les accompagnent.

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-DES-11` | M | Toute action utilisateur produit un retour visuel en moins de 100 ms, même si le traitement est plus long. | Aucune interaction sans retour immédiat. |
| `ENF-DES-12` | S | Les chargements affichent la **structure** du contenu attendu, jamais un indicateur d'attente générique, sur les écrans de plus de 300 ms. | Applique aux tableaux de bord, plans de charge et listes de projets. |
| `ENF-DES-13` | S | Les actions fréquentes et à faible risque d'échec (saisie de temps, changement de statut) s'affichent comme réussies immédiatement, avec correction visible en cas d'échec serveur. | Testé en conditions de réseau dégradé. |
| `ENF-DES-14` | S | Aucun écran ne bloque l'utilisateur pendant un traitement long : les traitements de plus de 3 secondes sont asynchrones et notifiés à leur achèvement. | Applique aux exports, imports, recalculs et générations de documents. |

### 3.4 États et cas limites

Les états dégradés sont ce que l'utilisateur voit le plus souvent au démarrage d'un tenant, et ce qui est spécifié le plus tard. Ils sont ici traités comme du périmètre, pas comme du détail.

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-DES-15` | M | Tout écran susceptible d'être vide dispose d'un **état vide utile** : ce que c'est, pourquoi c'est vide, quelle est l'action suivante. | Aucun écran ne présente une zone vide sans explication ni action proposée. |
| `ENF-DES-16` | M | Les messages d'erreur indiquent la marche à suivre. Aucun message technique, aucun code d'erreur seul, aucune formulation accusatoire. | Revue systématique des messages avant chaque mise en production. |
| `ENF-DES-17` | S | Le premier usage d'un tenant nouvellement créé propose un chemin explicite de mise en route, sans bloquer l'accès au reste du produit. | Cf. `EF-REF-29` ; le parcours est ignorable. |
| `ENF-DES-18` | S | Les états de chargement, vide, erreur, et « données partielles » sont spécifiés pour chaque écran au même titre que l'état nominal. | Présents dans les maquettes livrées ; absence = livrable incomplet. |

**Sur `ENF-DES-18` :** exiger les quatre états dans le livrable de design est le moyen le plus efficace d'éviter que l'équipe de développement les improvise. Ce sont eux qui font qu'un produit paraît fini ou bâclé.

### 3.5 Présentation des suggestions d'IA

L'IA introduit une catégorie d'information nouvelle : ce que le système propose sans certitude. Sa présentation obéit à des règles propres.

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-DES-19` | M | Toute information produite ou suggérée par une fonction d'IA est visuellement distinguable d'une donnée établie, par un traitement unique et constant. | Un utilisateur distingue en un coup d'œil une valeur saisie d'une valeur suggérée. |
| `ENF-DES-20` | M | Toute suggestion expose ses fondements en une action, sans changement d'écran. | Cf. `ENF-IA-1` ; l'explication est consultable sur place. |
| `ENF-DES-21` | M | Accepter, modifier ou refuser une suggestion se fait en un geste, avec un coût identique pour les trois. | Une interface qui rend l'acceptation plus facile que le refus fabrique du consentement, pas de la décision. |
| `ENF-DES-22` | S | Le signalement d'une suggestion inexacte est possible en un geste depuis l'endroit où elle apparaît. | Cf. `ENF-IA-8`. |
| `ENF-DES-23` | S | Aucune suggestion n'interrompt l'utilisateur. Elles se présentent dans le flux de travail, jamais en superposition modale. | Aucune fenêtre modale déclenchée par une suggestion. |

**Sur `ENF-DES-21` :** c'est une règle d'éthique de conception autant que d'ergonomie. Une suggestion qu'on accepte d'un clic et qu'on refuse en trois n'est pas une proposition, c'est une orientation. Sur des sujets de staffing et d'évaluation, la différence est significative.

---

## 4. Design system

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-DES-24` | M | Un design system est produit en lot 0 et constitue la référence contraignante : jetons (couleur, typographie, espacement, élévation), composants, états, règles de composition. | Aucune valeur graphique en dur dans le code applicatif. |
| `ENF-DES-25` | M | Les objets métier récurrents (projet, collaborateur, client, lot, alerte) disposent d'une représentation unique, réutilisée partout. | Cf. `DP-6`. |
| `ENF-DES-26` | S | Le design system supporte la personnalisation légère par tenant (logo, couleur principale) sans rupture de contraste ni d'accessibilité. | Cf. `EF-REF-32` ; les couleurs de tenant sont validées automatiquement en contraste. |
| `ENF-DES-27` | S | Le design system est documenté et consultable par l'équipe de développement, avec des exemples exécutables. | — |
| `ENF-DES-28` | C | Thème sombre disponible. | Traité comme un jeu de jetons alternatif, pas comme un second design. |

**Sur `ENF-DES-26` :** laisser un tenant choisir sa couleur principale sans contrôle de contraste garantit qu'un client finira avec une interface illisible et vous en tiendra responsable. La validation automatique est une exigence, pas un raffinement.

---

## 5. Anti-patterns explicitement interdits

Ces pratiques sont proscrites. Elles ne relèvent pas de la préférence esthétique : chacune a un effet documenté sur l'adoption d'un outil de gestion.

| Réf | Anti-pattern | Effet |
|---|---|---|
| `AP-1` | Tableau de bord de graphiques comme page d'accueil du collaborateur | Contredit `DP-1` ; l'utilisateur doit chercher son action |
| `AP-2` | Visite guidée ou tutoriel obligatoire au premier usage | Traite le symptôme d'une interface trop complexe |
| `AP-3` | Formulaire long sans enregistrement partiel | Perte de saisie, abandon, ressentiment durable |
| `AP-4` | Fenêtre modale pour une tâche de plus de trois champs | Perte du contexte, impossibilité de consulter l'information nécessaire |
| `AP-5` | Notification à chaque événement | Cf. `EF-PIL-14` ; conduit au filtrage automatique de toutes les alertes |
| `AP-6` | Vocabulaire propre au produit dans les parcours quotidiens | Contredit `DP-2` |
| `AP-7` | Indicateur affiché sans possibilité d'exploration | Cf. `EF-PIL-5` ; l'indicateur est discuté au lieu d'être utilisé |
| `AP-8` | Confirmation demandée pour une action réversible | Dresse l'utilisateur à confirmer sans lire |
| `AP-9` | Écran nécessitant un défilement horizontal sur un usage courant | Rend le plan de charge inutilisable |
| `AP-10` | Suggestion d'IA présentée comme une donnée établie | Contredit `ENF-DES-19` et `ENF-IA-1` |
| `AP-11` | Message d'erreur nommant un champ technique ou un code | Contredit `ENF-DES-16` |
| `AP-12` | Filtres à re-régler à chaque connexion | Contredit `ENF-DES-6` |

---

## 6. Critères de validation du design

Le design se valide par la mesure, pas par l'avis. Ces indicateurs sont relevés sur l'organisation pilote.

| Indicateur | Cible | Quand |
|---|---|---|
| Temps de saisie hebdomadaire (`EF-TMP-3`) | < 2 min | Lot 1, bloquant |
| Réussite de la première saisie sans aide (`ENF-DES-1`) | ≥ 4 personnes sur 5, < 3 min | Lot 1, bloquant |
| Nombre de clics pour les 5 actions quotidiennes les plus fréquentes | ≤ 3 chacune | Chaque lot |
| Taux de saisie complète à J+2 | ≥ 90 % à 3 mois | Lot 1 puis continu |
| Taux d'utilisateurs actifs hebdomadaires (`OBJ-7`) | ≥ 85 % | Continu |
| Nombre de tickets de support portant sur « je ne trouve pas » | Décroissant | Continu |
| Taux d'acceptation des suggestions d'IA (`ENF-IA-7`) | Croissant sur 8 semaines | Continu |
| Temps de validation d'une semaine d'équipe de 10 personnes | < 5 min | Lot 1 |

**Sur l'avant-dernier indicateur :** le nombre de tickets « je ne trouve pas » est le meilleur indicateur de qualité d'une architecture de navigation, et personne ne le mesure. Il suffit d'une catégorie dans l'outil de support.

---

## 7. Méthode de conception

| Réf | Règle |
|---|---|
| `MD-1` | Le parcours de saisie de temps est conçu et **testé auprès de vrais collaborateurs avant d'être développé** (lot 0). Cf. chapitre 08. |
| `MD-2` | Chaque écran majeur fait l'objet d'un test d'usage sur au moins 3 utilisateurs représentatifs avant mise en production. |
| `MD-3` | Le design system précède les écrans. Un écran conçu hors design system est une dette immédiate. |
| `MD-4` | Les quatre états (nominal, vide, chargement, erreur) sont livrés avec chaque écran (`ENF-DES-18`). |
| `MD-5` | Les tests d'usage portent sur des tâches réelles (« saisis ta semaine »), jamais sur une appréciation (« que penses-tu de cet écran ? »). |

**Sur `MD-5` :** demander à quelqu'un ce qu'il pense d'un écran produit une opinion polie et sans valeur. Lui demander d'accomplir une tâche et l'observer produit une information exploitable. C'est la différence entre une revue de design et un test d'usage, et seule la seconde prédit l'adoption.

---

## 8. Points ouverts

- `[ARB-19]` — Internalisation ou externalisation du design. La contrainte d'équipe (cf. chapitre 12) rend l'internalisation peu réaliste. Recommandation AMOA : faire produire le design system et les parcours clés par un designer externe en lot 0, puis maintenir en interne sur cette base. C'est le poste où l'externalisation a le meilleur rendement.
- `[à préciser]` — Choix de la bibliothèque de composants servant de base au design system. Décision conjointe design/technique, cf. `ADR-6` au chapitre 12.
