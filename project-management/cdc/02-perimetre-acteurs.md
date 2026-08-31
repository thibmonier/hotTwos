# 02 — Périmètre, acteurs et habilitations

## 1. Périmètre fonctionnel

### 1.1 Vue d'ensemble

HotOnes couvre la chaîne de valeur d'une agence, de l'opportunité commerciale à la clôture financière du projet, avec les boucles de rétroaction vers la capacité et le recrutement.

```
   OPPORTUNITÉ ──► DEVIS ──► PROJET ──► PRODUCTION ──► FACTURATION ──► CLÔTURE
        │             │         │            │              │             │
        │             │         │            │              │             │
        └──── pondération ──────┴─► PLAN DE CHARGE ◄────────┴─── réel ────┘
                                        │
                                        ▼
                            CAPACITÉ  ◄──►  COMPÉTENCES
                                        │
                                        ▼
                              BESOIN RH / RECRUTEMENT
```

### 1.2 Modules inclus

| Module | Code | Objet | Lot cible |
|---|---|---|---|
| Référentiels et paramétrage | `REF` | Organisation, rôles, taux, calendriers, paramétrage tenant | 1 |
| Avant-vente et pipeline | `CRM` | Comptes, opportunités, devis, pondération | 3 |
| Projets et delivery | `PRJ` | Structure projet, lots, jalons, budgets, avancement | 1 |
| Planification et staffing | `PLN` | Plan de charge, affectations, capacité, simulation | 2 |
| Temps et activité | `TMP` | Saisie, validation, absences, imputation | 1 |
| Finance et rentabilité | `FIN` | Coûts, marge, facturation, reste à faire, atterrissage | 2 |
| RH et cycle de vie collaborateur | `RH` | Compétences, entretiens, congés, parcours | 4 |
| Recrutement | `REC` | Besoins, postes, candidatures, intégration | 4 |
| Pilotage et reporting | `PIL` | Tableaux de bord, alertes, exploration en langage naturel | 3 |

### 1.3 Périmètre exclu

Exclusions assumées, à ne pas réinterroger sans arbitrage formel :

| Exclu | Justification | Traitement |
|---|---|---|
| Comptabilité générale et déclarations fiscales | Métier réglementé, éditeurs établis | Export vers l'outil comptable (`EF-FIN-22`) |
| Calcul de paie et production de bulletins | Métier réglementé | Export des éléments variables (`EF-RH-18`) |
| Gestion de tâches et suivi de tickets | Outils existants adoptés par les équipes techniques | Intégration Jira/Linear (`EF-PRJ-25`) |
| Signature électronique | Service tiers | Intégration (`EF-CRM-19`) |
| Emailing et marketing automation | Hors proposition de valeur | — |
| Gestion documentaire d'entreprise | Hors proposition de valeur | Stockage contextuel limité aux objets HotOnes |
| Portail client externe | Reporté | `W` — backlog produit, non spécifié dans ce CDC |

## 2. Acteurs et personas

Six personas structurent la conception. Chacun a un **enjeu principal**, une **fréquence d'usage** et un **critère de rejet** — ce dernier est le plus utile en conception.

### P1 — Le collaborateur (Camille, développeuse)

- **Usage :** quotidien, 2 à 5 minutes.
- **Attend :** saisir son temps sans y penser, poser ses congés, voir son planning des deux prochaines semaines.
- **Rejette si :** la saisie dépasse deux minutes, ou si elle sert visiblement à la surveiller plutôt qu'à piloter les projets.
- **Volume :** 80 % des utilisateurs de la plateforme.

### P2 — Le chef de projet (Marc)

- **Usage :** quotidien à hebdomadaire, 20 à 40 minutes.
- **Attend :** l'état de consommation de ses projets, le reste à faire, les alertes de dérive, la disponibilité de ses ressources.
- **Rejette si :** il doit ressaisir dans HotOnes ce qui existe ailleurs, ou si les chiffres de consommation sont faux.

### P3 — Le resource manager / directeur de production (Sophie)

- **Usage :** hebdomadaire, 1 à 2 heures.
- **Attend :** arbitrer les affectations sur 4 à 12 semaines, voir les conflits, simuler l'impact d'une nouvelle affaire.
- **Rejette si :** le plan de charge affiché ne correspond pas à ce qu'elle sait de la réalité terrain, ou si le moteur de suggestion ne s'explique pas.

### P4 — Le commercial / directeur de clientèle (Yann)

- **Usage :** quotidien, 15 à 30 minutes.
- **Attend :** gérer son pipeline, produire un devis cohérent avec la capacité réelle, s'engager sur une date de démarrage.
- **Rejette si :** l'outil l'empêche d'avancer sur une affaire tant que le paramétrage n'est pas parfait.

### P5 — Le responsable RH (Nadia)

- **Usage :** hebdomadaire, 1 à 3 heures.
- **Attend :** piloter les entretiens, la cartographie des compétences, les besoins de recrutement, sans relancer individuellement.
- **Rejette si :** le module duplique le SIRH, ou si les données d'évaluation sont accessibles à des personnes qui ne devraient pas les voir.

### P6 — Le dirigeant / associé (Élodie)

- **Usage :** hebdomadaire à mensuel, 30 minutes.
- **Attend :** marge par projet, atterrissage, taux d'occupation, tension capacitaire, en une vue.
- **Rejette si :** elle ne peut pas expliquer d'où vient un chiffre, ou si l'écart avec la comptabilité est inexpliqué.

### Acteurs secondaires

| Acteur | Rôle |
|---|---|
| Administrateur tenant | Paramètre l'instance de son organisation |
| Éditeur (équipe HotOnes) | Supervision multi-tenant, support, mise en service |
| Systèmes tiers | Comptabilité, SIRH, outils de tâches, calendrier, messagerie, signature, job boards |

## 3. Rôles et habilitations

### 3.1 Principe

Le modèle d'habilitation combine **rôle fonctionnel** et **périmètre de données**. Un même rôle n'a pas la même visibilité selon son périmètre : un chef de projet voit ses projets, un directeur de production voit son pôle.

`EF-REF-31` définit le modèle. Les rôles ci-dessous sont les **rôles fournis par défaut** ; chaque tenant peut les redéfinir.

### 3.2 Matrice de rôles par défaut

Légende : **C** = créer, **L** = lire, **M** = modifier, **V** = valider, **—** = aucun accès.

| Objet | Collaborateur | Chef de projet | Resource mgr | Commercial | RH | Direction | Admin tenant |
|---|---|---|---|---|---|---|---|
| Son propre temps | C L M | C L M | C L M | C L M | C L M | C L M | C L M |
| Temps de l'équipe | — | L V (ses projets) | L (son périmètre) | — | L (agrégé) | L | L |
| Ses affectations | L | L M (ses projets) | C L M V | L | L | L | L |
| Plan de charge global | — | L (ses projets) | C L M | L (lecture seule) | L (agrégé) | L | L |
| Projet — fiche | L (ses projets) | C L M | L | L | — | L | L |
| Projet — budget et marge | — | L M (ses projets) | L | L (ses affaires) | — | L M | L |
| Taux de vente | — | L | L | L | — | L M | L M |
| Coût collaborateur | — | — | — | — | L | L | L |
| Opportunité / devis | — | L (lié) | L | C L M | — | L V | L |
| Facturation | — | L (ses projets) | — | L (ses affaires) | — | C L M V | L |
| Fiche collaborateur — pro | L (la sienne) | L (restreinte) | L (restreinte) | — | C L M | L | L |
| Compétences | L M (les siennes) | L | L | — | C L M V | L | L |
| Entretien / évaluation | L (les siens) | C L M (son équipe) | — | — | C L M | L (agrégé) | — |
| Absences / congés | C L (les siens) | L V (son équipe) | L | — | C L M V | L | L |
| Besoin de recrutement | — | C (proposer) | C | — | C L M V | L V | L |
| Candidature | — | L (si évaluateur) | — | — | C L M | L | — |
| Paramétrage tenant | — | — | — | — | — | L | C L M |

### 3.3 Règles transverses d'habilitation

| Réf | Règle |
|---|---|
| `HAB-1` | Le **coût** d'un collaborateur n'est jamais visible d'un chef de projet. La marge projet lui est présentée, le détail des coûts individuels non. |
| `HAB-2` | Le contenu d'un **entretien** n'est visible que de l'intéressé, de son manager direct et de la RH. Aucun accès par la direction au contenu détaillé, uniquement à l'agrégat et au statut de réalisation. |
| `HAB-3` | Les données de **santé** (arrêts maladie) sont réduites au strict minimum : type d'absence « arrêt » et dates, jamais de motif médical. |
| `HAB-4` | L'accès aux données d'un **tenant** est strictement cloisonné. Aucun rôle applicatif ne permet de traverser les tenants ; la supervision éditeur passe par un canal distinct, tracé et limité (cf. `ENF-SEC-8`). |
| `HAB-5` | Toute **suggestion IA** est soumise aux mêmes habilitations que la donnée qui la fonde : une IA ne peut pas exposer indirectement une donnée que l'utilisateur n'a pas le droit de voir. Cette règle est une exigence de conception, pas un filtre d'affichage. |
| `HAB-6` | Toute lecture d'une donnée RH sensible ou d'une donnée de coût est **tracée** (qui, quoi, quand), avec une piste d'audit consultable par l'administrateur tenant. |

`HAB-5` est le point de vigilance principal du projet sur le plan de la sécurité. Un assistant conversationnel qui interroge librement la base et restitue en langage naturel est le moyen le plus direct de créer une fuite de données transverse. La conception doit filtrer **à la source de la donnée**, pas à la génération de la réponse.
