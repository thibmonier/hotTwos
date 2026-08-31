# 04.5 — Finance et rentabilité (`FIN`)

**Lot cible :** 2 — **Personas concernés :** direction (P6), chef de projet (P2), commercial (P4)

Ce module transforme l'activité en résultat. Sa contrainte dominante n'est pas fonctionnelle mais de **crédibilité** : le premier rapprochement avec la comptabilité décide de son adoption. Un écart inexpliqué de 3 % suffit à faire retomber la direction sur ses tableurs.

## 1. Valorisation

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-FIN-1` | M | Le système doit valoriser les temps validés au coût de revient en vigueur à la date de l'imputation. | Une augmentation au 01/07 n'affecte pas la valorisation de juin. |
| `EF-FIN-2` | M | Le système doit valoriser les temps au prix de vente selon la règle de priorité `EF-REF-19` pour les projets en régie. | — |
| `EF-FIN-3` | M | Le système doit intégrer les coûts externes (sous-traitance, achats, licences projet) dans le coût de production. | Le coût projet est décomposable en coût interne / coût externe. |
| `EF-FIN-4` | S | Le système doit permettre l'application de charges indirectes selon la clé paramétrée (`EF-REF-21`), avec présentation distincte de la marge avant et après charges indirectes. | Les deux niveaux de marge sont affichés côte à côte, jamais confondus. |
| `EF-FIN-5` | S | Le système doit permettre de recalculer une valorisation après correction rétroactive (changement de taux à effet rétroactif, réouverture de période), avec traçabilité et volumétrie impactée annoncée avant exécution. | Un recalcul est réversible ou, à défaut, précédé d'une sauvegarde de l'état antérieur. |

## 2. Marge et atterrissage

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-FIN-6` | M | Le système doit calculer, par projet : CA vendu, CA produit à date, coût à date, marge à date en valeur et en pourcentage. | Chaque valeur est traçable jusqu'aux lignes qui la composent. |
| `EF-FIN-7` | M | Le système doit calculer l'**atterrissage prévisionnel** : coût final projeté = coût à date + reste à faire valorisé + engagements externes non consommés ; marge finale projetée = CA vendu − coût final projeté. | Le calcul est décomposable à l'écran, chaque terme cliquable. |
| `EF-FIN-8` | M | Le système doit distinguer explicitement **marge brute** (CA − coût direct de production), **marge après charges indirectes**, et **résultat encaissé**. | Les trois notions portent des libellés distincts et ne sont jamais présentées sous le même terme. |
| `EF-FIN-9` | S | Le système doit consolider la marge par client, par groupe client, par pôle, par type de projet et par période. | La consolidation est disponible sans développement, par filtres. |
| `EF-FIN-10` | S | Le système doit historiser l'atterrissage et présenter sa courbe d'évolution. | Cf. `EF-PRJ-16` — même donnée, restitution financière. |
| `EF-FIN-11` | S | Le système doit calculer le **taux journalier moyen réalisé** par projet et par profil, et l'écart au taux vendu. | L'écart est le principal révélateur de la sur-consommation non refacturée. |

**Sur `EF-FIN-8` :** l'imprécision terminologique sur la marge est la source numéro un des désaccords entre production et direction. Trois notions, trois libellés, jamais d'ambiguïté. Le libellé exact doit être paramétrable par tenant pour coller à son vocabulaire interne.

## 3. Facturation

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-FIN-12` | M | Le système doit gérer un **échéancier de facturation** par projet : à la commande, au jalon, à l'avancement, périodique, ou à la consommation (régie). | Les cinq modes sont supportés et combinables sur un même projet. |
| `EF-FIN-13` | M | Le système doit proposer automatiquement les factures à émettre à partir de l'échéancier, des jalons atteints et de la consommation validée. | Le chef de projet ou la direction reçoit une liste « à facturer » sans recherche manuelle. |
| `EF-FIN-14` | M | Le système doit générer une facture conforme aux mentions légales françaises, avec numérotation continue et non modifiable après émission. | Une facture émise ne peut être modifiée : seul un avoir la corrige. |
| `EF-FIN-15` | M | Le système doit gérer les avoirs, rattachés à la facture d'origine. | — |
| `EF-FIN-16` | S | Le système doit gérer le suivi de l'encaissement : facture émise, échue, réglée, en retard, avec relances. | Le retard moyen de règlement par client est calculable. |
| `EF-FIN-17` | S | Le système doit calculer et présenter le **reste à facturer** par projet (CA vendu − CA facturé). | — |
| `EF-FIN-18` | S | Le système doit gérer la **facturation électronique** conformément à la réglementation française applicable. | **Le calendrier et les modalités de la réforme de la facturation électronique doivent être vérifiés auprès d'une source officielle avant conception.** Cette exigence est identifiée, non spécifiée. `[ARB-12]` |
| `EF-FIN-19` | C | Le système doit gérer la facturation multi-devises avec constatation des écarts de change. | — |

**Réserve explicite sur `EF-FIN-18` :** le dispositif français de facturation électronique et ses échéances ont connu plusieurs reports et évolutions. Ce document ne fournit volontairement aucune date ni aucune modalité technique : **à vérifier auprès d'une source officielle avant tout engagement de conception ou de calendrier.** C'est un sujet à instruire dès le lancement du lot 2, car il peut conditionner l'architecture de facturation.

## 4. Pilotage financier consolidé

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-FIN-20` | M | Le système doit produire un tableau de bord financier consolidé : CA facturé, CA produit, marge, backlog, carnet de commandes, taux d'occupation, par période. | Disponible à la maille mois et cumul, avec comparaison N-1. |
| `EF-FIN-21` | S | Le système doit calculer le **backlog** (reste à produire sur affaires signées) et le **carnet pondéré** (backlog + opportunités pondérées). | Les deux valeurs sont distinctes et leur mode de calcul est affiché. |
| `EF-FIN-22` | S | Le système doit exporter les écritures vers un logiciel de comptabilité, dans un format paramétrable. | Le format d'export est configurable ; les principaux formats du marché sont supportés. `[à préciser]` |
| `EF-FIN-23` | S | Le système doit permettre le rapprochement entre les données HotOnes et les données comptables, en identifiant les écarts. | Un écran de contrôle liste les écarts et leur origine probable. |
| `EF-FIN-24` | C | Le système doit produire une prévision de trésorerie à partir de l'échéancier de facturation et des délais de paiement constatés. | La prévision distingue le certain (facturé) du prévisionnel (à facturer). |

**Sur `EF-FIN-23` :** cette exigence a l'air technique et secondaire. Elle est en réalité le dispositif qui installe la confiance de la direction dans l'outil. Sans elle, chaque écart devient une remise en cause globale du système.

## 5. Capacités assistées par IA

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-FIN-25` | S | **[IA]** Le système doit produire une **synthèse commentée** de la clôture mensuelle : évolutions notables, projets contributeurs et destructeurs de marge, écarts au mois précédent, avec chiffres sourcés. | Chaque affirmation de la synthèse renvoie à l'indicateur qui la fonde. Aucun chiffre n'est généré par le modèle. |
| `EF-FIN-26` | S | **[IA]** Le système doit permettre l'interrogation des données financières en langage naturel, dans les limites d'habilitation de l'utilisateur. | La réponse expose la requête effectuée et les données utilisées. Aucune donnée hors périmètre d'habilitation n'est mobilisée, même indirectement (`HAB-5`). |
| `EF-FIN-27` | C | **[IA]** Le système doit identifier les projets dont la trajectoire financière ressemble à celle de projets historiquement déficitaires. | L'alerte nomme les projets de référence et les facteurs de similarité. |
| `EF-FIN-28` | C | **[IA]** Le système doit proposer une explication des écarts entre marge prévue et marge réalisée, en décomposant les contributions (sur-consommation, remise, coût externe, absence de refacturation). | La décomposition est arithmétiquement vérifiable : la somme des contributions égale l'écart total. |

**Sur `EF-FIN-26` :** c'est l'exigence la plus risquée du CDC sur le plan de la sécurité des données. Un assistant conversationnel sur des données financières et RH multi-tenant est un vecteur de fuite de premier ordre. Le filtrage doit être appliqué **à la construction de la requête**, jamais à la mise en forme de la réponse. Cette exigence ne doit pas être développée avant que `ENF-SEC-6` et `HAB-5` ne soient conçus et testés.

## 6. Règles de gestion du module

| Réf | Règle |
|---|---|
| `RG-FIN-1` | Une facture émise est immuable. |
| `RG-FIN-2` | Tout indicateur financier doit être traçable jusqu'aux lignes élémentaires qui le composent. |
| `RG-FIN-3` | Les temps non validés n'entrent pas dans les indicateurs financiers officiels ; ils peuvent apparaître dans une vue « en attente de validation » distincte. |
| `RG-FIN-4` | Les projets internes non facturables sont exclus du calcul de marge et inclus dans le calcul de coût de structure. |
| `RG-FIN-5` | Le reste à faire utilisé dans l'atterrissage est celui déclaré par le chef de projet, jamais une estimation automatique. |
| `RG-FIN-6` | Une donnée financière consolidée d'une période clôturée ne change plus, sauf réouverture formelle tracée. |

## 7. Points ouverts

- `[ARB-12]` — Facturation électronique : périmètre, calendrier et modalités techniques. **À vérifier auprès d'une source officielle.** Peut conditionner l'architecture du module.
- `[ARB-13]` — Niveau d'ambition du rapprochement comptable : simple export, ou réconciliation bidirectionnelle ? Recommandation AMOA : export + écran de contrôle des écarts au lot 2 ; la réconciliation automatique est un projet à part entière.
- `[HYP-8]` — On suppose que HotOnes est la source de vérité de la facturation et que la comptabilité est en aval. Si le client facture depuis son outil comptable, le module se réduit fortement et le suivi de marge se dégrade.
