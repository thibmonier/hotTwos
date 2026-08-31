# 04.2 — Projets et delivery (`PRJ`)

**Lot cible :** 1 — **Personas concernés :** chef de projet (P2), collaborateur (P1), direction (P6)

Le projet est l'objet pivot du système : il porte le budget, reçoit les temps, structure la facturation et alimente tous les indicateurs. Sa modélisation conditionne tout le reste.

## 1. Structure du projet

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PRJ-1` | M | Le système doit permettre de créer un projet rattaché à un compte client, portant un type de contractualisation, une période, un responsable et un budget. | — |
| `EF-PRJ-2` | M | Le système doit permettre de structurer un projet en **lots** (au moins 2 niveaux de profondeur), chaque lot portant un budget en charge et en montant. | La somme des budgets des lots est réconciliée avec le budget projet ; tout écart est signalé. |
| `EF-PRJ-3` | M | Le système doit permettre de définir des **jalons** datés, rattachés ou non à un lot, avec un statut et un éventuel déclencheur de facturation. | Un jalon « recette validée » déclenche la mise à disposition d'une facture à émettre. |
| `EF-PRJ-4` | M | Le système doit gérer un cycle de vie du projet avec statuts paramétrables (défaut : prospect → cadrage → actif → suspendu → clôturé opérationnel → clôturé financier → archivé). | Chaque statut conditionne les actions permises (imputation, facturation, modification de budget). |
| `EF-PRJ-5` | S | Le système doit gérer des projets internes non facturables (R&D, avant-vente, formation, congés) permettant l'imputation de temps hors production client. | Le taux d'occupation facturable est calculable par exclusion de ces projets. |
| `EF-PRJ-6` | S | Le système doit gérer les projets récurrents (TMA, abonnement, forfait mensuel) avec renouvellement de période et budget périodique. | Un contrat de TMA de 5 j/mois génère les périodes sans recréation manuelle. |
| `EF-PRJ-7` | C | Le système doit permettre de regrouper plusieurs projets en programme, avec consolidation budgétaire et d'avancement. | — |

## 2. Budget et engagement

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PRJ-8` | M | Le système doit distinguer **budget initial**, **avenants** et **budget courant** (= initial + avenants), avec historique daté de chaque modification. | L'évolution du budget est reconstituable dans le temps. |
| `EF-PRJ-9` | M | Le système doit exprimer le budget à la fois en **charge par profil** et en **montant**, les deux étant liés par les taux en vigueur. | Un budget de 60 jours à profil mixte affiche son équivalent en euros de vente et en euros de coût. |
| `EF-PRJ-10` | M | Le système doit gérer les **engagements externes** rattachés au projet (sous-traitance, achats, licences), avec leur montant engagé et facturé. | La marge projet intègre les engagements externes, pas uniquement le temps interne. |
| `EF-PRJ-11` | S | Le système doit permettre de réallouer du budget entre lots, avec traçabilité et sans modification du budget total. | Un transfert de 5 jours du lot A au lot B est tracé avec auteur, date et motif. |

**Sur `EF-PRJ-10` :** les achats externes sont systématiquement oubliés dans les ERP d'agence de première génération, et ils représentent 10 à 30 % du coût des projets. Sans eux, la marge affichée est fausse et l'outil perd sa crédibilité auprès de la direction dès le premier rapprochement comptable.

## 3. Avancement et pilotage opérationnel

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PRJ-12` | M | Le système doit permettre au chef de projet de déclarer un **avancement physique** par lot (en %), indépendamment de la consommation budgétaire. | La saisie d'avancement est possible et son historique conservé. La valeur n'est jamais déduite de la consommation. |
| `EF-PRJ-13` | M | Le système doit permettre de saisir et réviser un **reste à faire** par lot, exprimé en charge. | Le reste à faire est saisissable indépendamment du budget restant, et les deux valeurs sont affichées côte à côte. |
| `EF-PRJ-14` | M | Le système doit calculer et afficher, par lot et par projet : budget, consommé, reste à faire, atterrissage prévisionnel (consommé + reste à faire), écart à budget. | Les cinq valeurs sont visibles sur une même vue et exportables. |
| `EF-PRJ-15` | M | Le système doit détecter et signaler une **dérive** lorsque l'écart entre pourcentage consommé et pourcentage d'avancement dépasse un seuil paramétrable. | L'alerte est émise au chef de projet et, au-delà d'un second seuil, à la direction. Le seuil est paramétrable par type de projet. |
| `EF-PRJ-16` | S | Le système doit historiser l'atterrissage prévisionnel et en présenter la courbe d'évolution dans le temps. | La courbe permet de voir *quand* la projection s'est dégradée, pas seulement sa valeur actuelle. |
| `EF-PRJ-17` | S | Le système doit permettre de suivre les risques et points de blocage du projet, avec responsable et échéance. | — |
| `EF-PRJ-18` | C | Le système doit produire automatiquement un compte rendu d'avancement périodique diffusable au client. | Le document est éditable avant diffusion. |

**Sur `EF-PRJ-12` et `EF-PRJ-13` :** ces deux exigences sont le cœur du dispositif de détection de dérive. Elles imposent au chef de projet un effort de saisie hebdomadaire réel. Si cet effort n'est pas obtenu, aucune détection précoce n'est possible — quelle que soit la sophistication de l'IA par ailleurs. L'ergonomie de cette saisie est donc un enjeu prioritaire du lot 1, pas un détail d'interface.

## 4. Équipe projet

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PRJ-19` | M | Le système doit permettre d'affecter des collaborateurs à un projet avec un rôle, une période et un volume de charge prévisionnel. | L'affectation alimente le plan de charge et ouvre les droits d'imputation. |
| `EF-PRJ-20` | M | Le système doit restreindre l'imputation de temps aux collaborateurs affectés au projet, avec possibilité d'ouverture exceptionnelle tracée. | Un collaborateur non affecté ne voit pas le projet dans sa saisie, sauf ouverture explicite. |
| `EF-PRJ-21` | S | Le système doit permettre d'affecter des ressources externes (freelances, sous-traitants) avec un coût spécifique. | La marge intègre correctement le coût externe. |

## 5. Clôture

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PRJ-22` | M | Le système doit permettre la clôture opérationnelle d'un projet, fermant les imputations tout en maintenant le suivi financier ouvert. | Aucune imputation n'est possible après clôture opérationnelle sans réouverture tracée. |
| `EF-PRJ-23` | S | Le système doit produire un **bilan de projet** à la clôture : vendu vs réalisé par lot, marge finale, écarts d'estimation par profil, respect des jalons. | Le bilan est généré automatiquement et enrichissable par un commentaire du chef de projet. |
| `EF-PRJ-24` | S | Le système doit alimenter une base d'historique de projets exploitable pour le chiffrage futur (cf. `EF-CRM-24`). | Les écarts d'estimation par type de lot et par profil sont agrégeables. |

## 6. Intégrations

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PRJ-25` | S | Le système doit s'interfacer avec un outil de gestion de tâches (Jira, Linear, ou équivalent) pour récupérer l'avancement et les temps passés, sans imposer de ressaisie. | Le mapping projet/lot ↔ projet/épic est paramétrable ; la synchronisation est unidirectionnelle par défaut. |
| `EF-PRJ-26` | C | Le système doit permettre d'attacher des documents au projet et à ses lots. | — |
| `EF-PRJ-27` | C | Le système doit s'interfacer avec un espace de stockage documentaire (Drive, SharePoint) plutôt que de dupliquer les fichiers. | — |

## 7. Capacités assistées par IA

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-PRJ-28` | S | **[IA]** Le système doit produire une **synthèse hebdomadaire** de l'état du projet, en langage naturel, à partir des données de consommation, d'avancement, d'affectation et de jalons. | La synthèse cite les données chiffrées qui la fondent. Aucun chiffre n'y figure sans provenir du système. |
| `EF-PRJ-29` | S | **[IA]** Le système doit détecter les **signaux faibles de dérive** au-delà des seuils simples : accélération de consommation, glissement répété de jalon, rotation anormale de l'équipe, écart croissant entre reste à faire déclaré et rythme observé. | Chaque alerte nomme le ou les signaux détectés et la période concernée. Le taux de faux positifs est mesuré sur le pilote et doit rester sous 30 % pour que la fonction soit maintenue. |
| `EF-PRJ-30` | C | **[IA]** Le système doit proposer, lors du cadrage, une structure de lots et de jalons à partir de projets historiques comparables. | La proposition est éditable et cite ses projets de référence. |
| `EF-PRJ-31` | C | **[IA]** Le système doit proposer une réestimation du reste à faire à partir du rythme observé, présentée **à côté** de l'estimation du chef de projet sans s'y substituer. | Les deux valeurs sont affichées ensemble ; seule celle du chef de projet est utilisée dans les calculs officiels. |

**Sur `EF-PRJ-29` :** un critère de faux positifs est indispensable. Un système d'alerte qui crie trop souvent est désactivé mentalement par ses utilisateurs en trois semaines et devient pire que l'absence d'alerte. Le seuil de 30 % est indicatif et doit être calibré sur le pilote.

## 8. Règles de gestion du module

| Réf | Règle |
|---|---|
| `RG-PRJ-1` | Un projet ne peut être créé sans client, sans responsable et sans budget, même provisoire. |
| `RG-PRJ-2` | L'avancement physique n'est jamais calculé à partir de la consommation budgétaire. |
| `RG-PRJ-3` | Le reste à faire par défaut à la création d'un lot est égal à son budget ; il diverge dès la première réestimation. |
| `RG-PRJ-4` | La modification du budget d'un projet actif exige un motif et relève d'un circuit de validation paramétrable. |
| `RG-PRJ-5` | Un projet clôturé opérationnellement reste consultable et ses données restent agrégées dans les indicateurs historiques. |
| `RG-PRJ-6` | Les projets internes non facturables sont exclus du calcul de marge mais inclus dans le calcul de capacité consommée. |

## 9. Points ouverts

- `[ARB-7]` — Sens de la synchronisation avec l'outil de tâches. La bidirectionnalité est séduisante et coûteuse : elle crée des conflits de source de vérité difficiles à arbitrer. Recommandation AMOA : unidirectionnel (outil de tâches → HotOnes) au lot 3, bidirectionnel jamais sans un besoin démontré.
- `[à préciser]` — Granularité de l'avancement : par lot uniquement, ou également par jalon ? À trancher en atelier avec deux chefs de projet en conditions réelles.
