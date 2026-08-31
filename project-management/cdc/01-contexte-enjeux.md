# 01 — Contexte et enjeux

## 1. Le problème métier

Une agence digitale ou une ESN vit d'un équilibre entre trois variables qui se dégradent mutuellement dès qu'on cesse de les regarder ensemble :

- **La charge vendue** (ce que le commerce a engagé),
- **La capacité disponible** (qui est là, quand, avec quelles compétences),
- **La marge réelle** (ce que le projet rapporte une fois la production consommée).

Dans la très grande majorité des structures de 10 à 150 personnes, ces trois variables vivent dans des outils séparés — un CRM, un tableur de staffing, un outil de saisie de temps, une comptabilité — et se réconcilient une fois par mois, à la main, avec trois semaines de retard. Conséquences observées et récurrentes :

1. **La dérive projet est détectée trop tard.** Quand le dépassement apparaît dans le reporting mensuel, 60 à 80 % du budget est déjà consommé et les leviers de correction ont disparu.
2. **Le staffing est réactif.** Les décisions d'affectation se prennent à la semaine, sans visibilité sur le pipeline commercial pondéré, ce qui produit alternativement de la sous-charge coûteuse et de la surcharge destructrice.
3. **Le besoin en recrutement est constaté, pas anticipé.** Le délai entre l'expression du besoin et l'arrivée effective d'un profil (3 à 6 mois) est systématiquement supérieur à l'horizon de visibilité de la production.
4. **Le suivi RH est un coût de friction.** Entretiens, montée en compétences, congés, absences : la charge administrative pèse sur des managers dont ce n'est pas le métier, et la donnée produite n'alimente aucune décision.
5. **La saisie de temps est mal faite parce qu'elle ne sert à rien pour celui qui la fait.** C'est la cause racine de la non-fiabilité de toute la chaîne financière : sans temps fiable, ni la marge ni la capacité ne sont calculables.

Le point 5 mérite un traitement particulier : c'est le **point de défaillance unique** de tout ERP d'agence. Un CDC qui traite la saisie de temps comme un module parmi d'autres produit un outil qui ne fonctionne pas. Elle est traitée ici comme un enjeu d'adoption avant d'être un enjeu fonctionnel (cf. `04-exigences/44-temps-activite.md`).

## 2. Le positionnement de HotOnes

HotOnes se positionne comme l'**ERP du cycle de vie projet** pour les agences et ESN, avec une promesse en trois points :

> **Voir** la performance opérationnelle et financière d'un projet en continu, pas à la clôture.
> **Décider** le staffing à partir d'une capacité et d'un pipeline consolidés, pas d'un tableur.
> **Anticiper** les besoins RH et de recrutement à partir de la projection de charge, pas du constat de surcharge.

### 2.1 Ce qui différencie la refonte

Le MVP existant couvre partiellement la chaîne. La refonte poursuit deux objectifs de rupture :

**a) L'IA comme réducteur de friction de saisie.** L'échec des ERP d'agence est un échec d'adoption, pas de couverture fonctionnelle. Les capacités d'IA sont donc prioritairement affectées à la **suppression de la saisie manuelle** : pré-remplissage des temps à partir des signaux d'activité, extraction structurée depuis les documents (devis, CV, contrats), reformulation en langage naturel des interactions avec l'outil. Cette orientation est un choix explicite : elle prime sur les usages « démonstratifs » de l'IA.

**b) L'IA comme aide à la décision, sous condition d'explicabilité.** Détection précoce de dérive, propositions de staffing, projection de charge et alerte sur les tensions de compétences. Contrainte non négociable : **toute suggestion IA affichée doit exposer les données qui la fondent** (cf. `ENF-IA-1` et suivantes). Une recommandation opaque sur un sujet de rentabilité ou de RH n'est pas utilisable et sera abandonnée par les utilisateurs en quelques semaines.

### 2.2 Ce que HotOnes n'est pas

- **Ce n'est pas un outil de gestion de tâches.** HotOnes suit le projet au niveau du lot et du jalon, pas de la tâche unitaire. Il s'interface avec Jira, Linear ou équivalent, il ne les remplace pas. `[ARB-1]`
- **Ce n'est pas un SIRH complet.** Il couvre le cycle de vie du collaborateur du point de vue de la capacité et de la compétence, pas la paie ni la gestion administrative réglementaire complète.
- **Ce n'est pas un outil comptable.** Il produit la facturation et l'analytique de gestion, il s'interface avec la comptabilité.

Ces trois exclusions sont structurantes. Les remettre en cause multiplie le périmètre par deux ou trois et fait sortir le produit de sa proposition de valeur.

## 3. Objectifs et indicateurs de succès

Les objectifs sont formulés de manière mesurable. Un objectif sans indicateur ni cible ne figure pas dans ce tableau.

| Réf | Objectif | Indicateur | Situation de référence | Cible à 12 mois d'usage |
|---|---|---|---|---|
| `OBJ-1` | Fiabiliser la donnée de temps | Taux de saisie complète à J+2 | *[à mesurer sur le pilote]* | ≥ 90 % |
| `OBJ-2` | Détecter la dérive projet tôt | Part des dépassements > 10 % détectés avant 50 % de consommation budget | *[à mesurer]* | ≥ 75 % |
| `OBJ-3` | Réduire le coût du suivi | Temps hebdomadaire passé par un chef de projet au reporting et au suivi | *[à mesurer]* | −40 % |
| `OBJ-4` | Améliorer le taux d'occupation | Taux d'occupation facturable moyen | *[à mesurer]* | +5 points, sans dépassement du seuil de surcharge |
| `OBJ-5` | Anticiper le recrutement | Délai moyen entre détection du besoin et ouverture du poste | *[à mesurer]* | ≤ 15 jours |
| `OBJ-6` | Fiabiliser la prévision financière | Écart entre marge prévisionnelle à mi-projet et marge réelle à la clôture | *[à mesurer]* | ≤ 5 points |
| `OBJ-7` | Obtenir l'adhésion | Taux d'utilisateurs actifs hebdomadaires / utilisateurs déclarés | — | ≥ 85 % |

**Point d'attention méthodologique :** les situations de référence sont inconnues à ce jour. Elles doivent être mesurées sur l'organisation pilote **avant** le démarrage du lot 1, sans quoi aucune des cibles n'est démontrable et le ROI du projet restera déclaratif. C'est une action à programmer immédiatement, indépendante du développement. `[ARB-2]`

## 4. Enjeux par population

| Population | Ce qu'elle attend | Ce qui la fera rejeter l'outil |
|---|---|---|
| Direction / CODIR | Visibilité consolidée marge, capacité, pipeline | Des chiffres qu'elle ne peut pas expliquer à son commissaire aux comptes ou à ses associés |
| Chef de projet | Voir la dérive avant qu'elle soit irrattrapable | Devoir ressaisir dans HotOnes ce qui existe déjà dans Jira |
| Collaborateur | Que la saisie prenne moins de deux minutes par jour | Une saisie de temps ressentie comme du flicage sans contrepartie |
| Manager / Resource manager | Arbitrer les affectations sans tableur | Un moteur de staffing qui propose des affectations absurdes sans expliquer pourquoi |
| RH | Suivre les entretiens et les compétences sans relancer 40 personnes | Un module RH qui duplique le SIRH existant |
| Commerce | Savoir si on peut s'engager sur une date de démarrage | Une capacité affichée qui ne reflète pas la réalité |

## 5. Contraintes générales

| Réf | Contrainte | Nature | Impact |
|---|---|---|---|
| `CTR-1` | Le produit doit être opérable par une petite équipe (produit + 2 à 4 développeurs). | Organisationnelle | Interdit une architecture nécessitant une équipe d'exploitation dédiée |
| `CTR-2` | Conformité RGPD, avec traitement de données RH et d'évaluation. | Réglementaire | Cf. `05`, chapitre conformité |
| `CTR-3` | Règlement européen sur l'IA (AI Act) : les usages RH et d'évaluation des personnes relèvent de catégories à risque encadrées. | Réglementaire | **Vérifier auprès d'un conseil juridique avant toute conception du module RH.** Cette qualification n'est pas établie dans ce document et conditionne les exigences `EF-RH-*` et `EF-REC-*` |
| `CTR-4` | Le coût d'inférence IA doit rester compatible avec un modèle SaaS par abonnement. | Économique | Impose un budget d'inférence par tenant et une dégradation gracieuse (cf. `ENF-IA-5`) |
| `CTR-5` | Souveraineté des données : hébergement et traitement IA dans l'UE. | Contractuelle / commerciale | Restreint le choix des fournisseurs de modèles `[ARB-3]` |

**Réserve explicite sur `CTR-3` :** la qualification AI Act d'un outil qui produit des recommandations de staffing, d'évaluation de performance ou de tri de candidatures est un sujet juridique réel et non tranché ici. Ce document identifie le risque, il ne le résout pas. Faire valider par un conseil spécialisé avant d'engager la conception des modules RH et Recrutement.
