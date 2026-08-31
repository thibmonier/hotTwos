# 10 — Annexes

---

## 1. Glossaire

| Terme | Définition retenue dans ce document |
|---|---|
| **ADR** | *Architecture Decision Record*. Décision d'architecture documentée : contexte, options, décision, conséquences. Format du chapitre 12. |
| **AMOA** | Assistance à maîtrise d'ouvrage. Rôle qui traduit le besoin métier en exigences. |
| **API-first (cœur)** | Logique métier écrite dans une couche applicative indépendante du transport, consommée indifféremment par le web, une API ou une commande. Cf. `ADR-1`. |
| **Atterrissage** | Projection de la marge et du coût à la clôture du projet : coût à date + reste à faire valorisé + engagements non consommés. |
| **Avancement physique** | Pourcentage d'achèvement d'un lot déclaré par le chef de projet. **Jamais déduit de la consommation budgétaire.** |
| **Backlog (financier)** | Reste à produire sur les affaires signées. À ne pas confondre avec le backlog produit. |
| **Capacité brute** | Temps théorique disponible d'un collaborateur selon son contrat et son calendrier. |
| **Capacité nette** | Capacité brute − absences − charge interne récurrente, ajustée du taux d'occupation cible. **Seule valeur utilisée en planification.** |
| **Carnet pondéré** | Backlog + opportunités pondérées par leur probabilité. |
| **Charge ferme** | Charge issue de projets signés. |
| **Charge probable** | Charge issue d'opportunités pondérées au-delà du seuil de probabilité. Jamais agrégée avec la charge ferme sans mention. |
| **Coût de revient chargé** | Coût d'un collaborateur incluant les charges sociales et, selon paramétrage, une quote-part de charges indirectes. |
| **Dérive** | Écart entre pourcentage de budget consommé et pourcentage d'avancement déclaré, au-delà d'un seuil. |
| **ENF** | Exigence non fonctionnelle. |
| **Deptrac / test d'architecture** | Outil vérifiant automatiquement en intégration continue que les dépendances entre couches et modules respectent les règles déclarées (`ARC-63`). |
| **Développement assisté par agent** | Mode de développement où un agent produit une part importante du code sous la conduite de l'auteur. Déplace le facteur limitant de l'écriture vers la relecture (`ADR-16`). |
| **Discriminant de tenant** | Colonne identifiant le tenant sur chaque table, filtrée automatiquement. Stratégie d'isolation retenue (`ADR-8`). |
| **Ember** | Tableau de bord temps réel en terminal pour Caddy et FrankenPHP (MIT, gratuit). Retenu comme outil de supervision du serveur applicatif (`ADR-14`). |
| **Domaine cœur / support / générique** | Classement DDD d'un sous-domaine selon sa complexité et sa contribution à la différenciation. Gouverne l'intensité de modélisation (`ARC-100`). |
| **ESN** | Entreprise de services du numérique. |
| **FrankenPHP** | Serveur applicatif PHP bâti sur Caddy. Son mode *worker* garde l'application chargée en mémoire entre les requêtes (`ADR-2`). |
| **ETP** | Équivalent temps plein. |
| **Imputation** | Ligne élémentaire de temps : collaborateur, date, projet, lot, durée. Unité de base de la chaîne financière. |
| **Invariant (`INV`)** | Propriété structurelle du modèle de données non négociable, non rétro-adaptable. |
| **Jalon** | Point de contrôle daté d'un projet, pouvant déclencher une facturation. |
| **Lot (projet)** | Subdivision d'un projet portant un budget en charge et en montant. |
| **Lot (livraison)** | Tranche de réalisation du projet de refonte. Cf. chapitre 08. |
| **Fait / dimension** | Dans un schéma en étoile : une table de faits porte les mesures à un grain donné, les dimensions portent les axes d'analyse. |
| **Grain (d'un fait)** | Niveau de détail élémentaire d'une table de faits. Un grain ambigu produit des doubles comptages irrattrapables (`ARC-68`). |
| **Marge brute** | CA − coût direct de production. |
| **Marge après charges indirectes** | Marge brute − quote-part de charges de structure. |
| **MoSCoW** | Méthode de priorisation : Must, Should, Could, Won't. |
| **Monolithe modulaire** | Application déployée d'un seul tenant, mais structurée en modules à frontières explicites. Cf. `ADR-1`. |
| **MVP** | Produit minimum viable. Ici, l'existant HotOnes. |
| **Modèle dimensionnel (schéma en étoile)** | Organisation des données analytiques en tables de faits (mesures) et dimensions (axes d'analyse). Retenu au niveau conception dès le lot 1, implémenté en vues matérialisées (`ADR-9`). |
| **Modèle de lecture** | Modèle de données dédié à la restitution, dérivé du modèle transactionnel. Ici, le schéma en étoile (`ADR-9`). |
| **Multi-tenant** | Architecture où une instance unique sert plusieurs organisations clientes avec isolation stricte de leurs données. |
| **Mode *worker*** | Mode d'exécution où le noyau applicatif reste en mémoire entre les requêtes. Gain de performance majeur, au prix d'un risque de fuite d'état (`ARC-47`). |
| **Objet-valeur** | Objet défini par sa valeur et non par une identité, portant ses propres invariants : `Money`, `Duration`, `Period`. |
| **Persona** | Profil type d'utilisateur, caractérisé par son usage et son critère de rejet. |
| **Plongement (vecteur sémantique)** | Représentation numérique d'un texte permettant d'en mesurer la proximité de sens. Utilisé pour le rapprochement de compétences et de projets (`ARC-41`). |
| **PWA** | *Progressive Web App*. Application web installable, fonctionnant partiellement hors ligne. Cf. `EVO-1`. |
| **Pondération** | Application de la probabilité de succès d'une opportunité à son montant ou à sa charge. |
| **Rendu serveur** | Génération des pages côté serveur, par opposition à une application client autonome consommant une API. Mode par défaut retenu (`ADR-5`). |
| **Reconstruction (du modèle analytique)** | Commande régénérant intégralement les tables de faits depuis le seul modèle transactionnel. Preuve exécutable de `ARC-70` et chemin de reprise après incident (`ARC-112`). |
| **Reste à faire** | Charge restante réestimée par le chef de projet. **Distinct du budget restant.** |
| **Reprise (Symfony)** | Couche d'intégration Symfony pour les bundlers modernes (Vite, Rsbuild), successeur de Webpack Encore. **Statut expérimental 0.x** (`ADR-5`). |
| **RLS** | *Row Level Security*. Filtrage des lignes appliqué par la base de données elle-même, indépendamment du code applicatif. Seconde barrière d'isolation (`ARC-34`). |
| **Symfony AI** | Ensemble de composants Symfony (Platform, Agent, Chat, Store) offrant un accès unifié aux fournisseurs de modèles, y compris locaux via Ollama, et une abstraction de stockage vectoriel (`ADR-10`). |
| **Schéma en étoile** | Organisation analytique en tables de faits et dimensions conformes. Retenu physiquement dès le lot 1 (`ADR-9`). |
| **Taux d'occupation** | Part du temps disponible consacrée à des projets facturables. |
| **Tenant** | Organisation cliente disposant de son espace isolé sur la plateforme. |
| **TJM réalisé** | CA du projet ÷ nombre de jours réellement consommés. À comparer au TJM vendu. |

---

## 2. Matrice de traçabilité

### 2.1 Objectifs → exigences

| Objectif | Exigences contributrices principales |
|---|---|
| `OBJ-1` Fiabiliser les temps | `EF-TMP-1` à `-13`, `-20` à `-24` ; `ENF-UX-1`, `-2` ; `ENF-DES-1` à `-3`, `-11` à `-14` ; `EVO-1.2` |
| `OBJ-2` Détecter la dérive tôt | `EF-PRJ-12` à `-16`, `-29` ; `EF-PIL-13` |
| `OBJ-3` Réduire le coût du suivi | `EF-PRJ-28` ; `EF-FIN-25` ; `EF-PIL-1` à `-6`, `-20` |
| `OBJ-4` Améliorer le taux d'occupation | `EF-PLN-1` à `-12`, `-23`, `-24` |
| `OBJ-5` Anticiper le recrutement | `EF-PLN-25` ; `EF-REC-1` à `-6` |
| `OBJ-6` Fiabiliser la prévision financière | `EF-FIN-6` à `-11` ; `EF-PRJ-13`, `-14` ; `INV-2`, `INV-3` |
| `OBJ-7` Obtenir l'adhésion | `EF-TMP-3`, `-26`, `-27` ; `EF-PIL-14` ; `ENF-UX-1` à `-3` ; `DP-1` à `DP-6` ; `ENF-DES-1` à `-8` |

### 2.2 Processus → modules

| Processus | Modules mobilisés |
|---|---|
| `MP1` Opportunité → projet | `CRM`, `PLN`, `PRJ`, `REF` |
| `MP2` Projet → livraison | `PRJ`, `TMP`, `PIL` |
| `MP3` Planification et staffing | `PLN`, `RH`, `CRM`, `REC` |
| `MP4` Saisie et validation des temps | `TMP`, `REF` |
| `MP5` Pilotage financier | `FIN`, `TMP`, `PRJ`, `PIL` |
| `MP6` Besoin → recrutement | `PLN`, `REC`, `RH` |

### 2.3 Volumétrie des exigences

| Module | Exigences | Lot |
|---|---|---|
| `REF` — Référentiels | 34 | 1 (+2) |
| `CRM` — Avant-vente | 26 | 3 |
| `PRJ` — Projets | 31 | 1 (+3) |
| `PLN` — Planification | 26 | 2 (+4) |
| `TMP` — Temps | 32 | 1 (+2) |
| `FIN` — Finance | 28 | 2 |
| `RH` — Ressources humaines | 27 | 4 |
| `REC` — Recrutement | 22 | 4 |
| `PIL` — Pilotage | 22 | 3 |
| **Total fonctionnelles** | **248** | |
| **Total non fonctionnelles (ch. 05)** | **63** | Transverses |
| **Exigences de design (ch. 11, `ENF-DES`)** | **28** | Transverses |
| **Contraintes d'architecture (ch. 06 et 12, `ARC`)** | **120** | Transverses |
| **Décisions d'architecture (ch. 12, `ADR`)** | **16** | Lot 0 |
| **Exigences anticipatoires (ch. 13, `EVO`)** | **13** | Lots 1 à 3 |
| **Total général** | **488** | |

*Comptage incluant les exigences marquées `W` (hors périmètre, tracées pour mémoire).*

### 2.4 Exigences à criticité maximale

Exigences dont l'échec compromet le produit dans son ensemble. À traiter en priorité et à ne jamais arbitrer au titre du planning.

| Réf | Exigence | Motif |
|---|---|---|
| `EF-TMP-3` | Saisie hebdomadaire < 2 minutes | Point de défaillance unique de la chaîne de données |
| `ENF-SEC-4` | Isolation inter-tenant | Incident commercialement fatal |
| `ENF-SEC-6` / `ARC-9` | Filtrage IA à la source | Vecteur de fuite principal |
| `EF-PIL-19` | Assistant conversationnel cloisonné | Idem, sur la fonction la plus visible |
| `INV-1` à `INV-8` | Invariants du modèle | Non rétro-adaptables |
| `EF-PRJ-12`, `-13` | Avancement et reste à faire déclarés | Sans eux, aucune détection de dérive possible |
| `EF-PLN-2` | Capacité nette | Sans elle, le plan de charge n'est pas crédible |
| `EF-PIL-5` | Traçabilité des indicateurs | Sans elle, les chiffres ne sont pas utilisés |
| `ENF-IA-1` à `-3` | Explicabilité, non-substitution, séparation calcul/rédaction | Conditions d'acceptabilité de l'IA |
| `ARC-19` | Habilitations dans la couche applicative, pas dans l'adaptateur | Sinon web et API divergent, et l'un des deux aura un trou |
| `ARC-33` à `-36` | Double barrière d'isolation multi-tenant | Rend le discriminant partagé acceptable en solo |
| `ENF-DES-1` | Première saisie réussie sans aide | Mesure directe de l'appropriation, contrainte fondatrice C1 |
| `EVO-2.3` | Mesure du coût et de la qualité IA par fonction dès le lot 1 | Sans elle, tout arbitrage ultérieur sur l'IA locale est aveugle |
| `ARC-47`, `ARC-50` | Aucun état conservé entre requêtes en mode *worker*, vérifié par des tests exécutés en configuration *worker* | Une fuite d'état en multi-tenant est un incident d'isolation, pas un bogue |
| `ARC-51` | Tolérance zéro aux dépréciations | Seule mesure qui rend indolore la montée semestrielle imposée par `ADR-3` |
| `ARC-63` | Frontières d'architecture vérifiées automatiquement | En solo, sans revue de code, une règle non outillée n'est pas une règle |
| `ARC-70` | Modèle analytique dérivé du transactionnel, source de vérité unique | Préserve `EF-PIL-5` et évite `RSQ-5` |
| `ARC-82` | Chiffrement et non-journalisation des clés d'API des tenants | Une fuite de clés clients est un incident majeur |
| `ARC-106` | Le périmètre de sécurité ne délègue pas à une génération de code | Une habilitation manquante ne produit pas d'erreur, elle produit un accès |
| `ARC-112` à `ARC-114` | Reconstruction du modèle analytique, test de non-divergence, réconciliation en production | Sans eux, la divergence se découvre en comité de direction (`RSQ-22`) |
| `ARC-103` | Une règle de gestion `RG-*` = un test nommé | Seul dispositif qui tient quand le volume de code dépasse la capacité de relecture |

---

## 3. Hypothèses à valider

| Réf | Hypothèse | Impact si fausse | Qui valide | Échéance |
|---|---|---|---|---|
| `HYP-1` | L'existant est un MVP partiel, sans production ni données critiques | +3 à 5 mois (reprise et bascule) ; révision du chapitre 07 | Sponsor + audit `AUD-1` | Lot 0 |
| `HYP-2` | Cible produit SaaS multi-clients | Simplification majeure (multi-tenant inutile) | Sponsor | **Immédiat** |
| `HYP-3` | Configurable de 10 à 150 collaborateurs | Complexité du module `REF` divisée par deux | Sponsor | **Immédiat** |
| `HYP-4` | IA intégrée aux modules, socle mutualisé | Nécessite un lot IA autonome | Sponsor | Lot 0 |
| `HYP-5` | Tenant mono-devise pour son reporting consolidé | Complexité de consolidation accrue | Sponsor | Lot 2 |
| `HYP-6` | Le chiffrage est réalisé dans HotOnes | Import structuré à ajouter | Métier | Lot 3 |
| `HYP-7` | Rituel de staffing hebdomadaire | Ergonomie du plan de charge à repenser | Référent pilote | Lot 2 |
| `HYP-8` | HotOnes est source de vérité de la facturation | Réduction forte du module `FIN` | Sponsor | Lot 2 |
| `HYP-9` | La paie reste externe | — | Sponsor | Lot 4 |
| `HYP-10` | Un outil de BI externe reste possible | Élargissement de `EF-PIL-8` | Sponsor | Lot 3 |
| `HYP-11` | ~150 tenants à l'horizon 3 ans | Dimensionnement infra | Sponsor | Lot 0 |
| `HYP-12` | L'existant n'a ni modèle multi-tenant ni couche applicative découplée | Le tableau des préconisations techniques (12 § 11) s'allège | Audit `AUD-1` | Lot 0 |
| `HYP-13` | L'adoption de la saisie est limitée par l'ergonomie, pas par l'absence d'app mobile | L'application native remonte fortement en priorité | Mesure sur le pilote | Lot 1-2 |
| `HYP-14` | Stack existante : Symfony + Twig + Stimulus / Live Components | Cf. `HYP-12` | Audit `AUD-1` | Lot 0 |
| `HYP-15` | **Équipe d'une personne au démarrage** | Le chapitre 08 redevient applicable tel quel ; le chapitre 12 est à réexaminer | Sponsor | **Immédiat** |
| `HYP-16` | Les tenants acceptent de fournir et de payer leurs propres clés d'accès aux modèles d'IA (`ADR-10`) | Une offre avec inférence incluse devient nécessaire dès le lancement, et le coût d'inférence revient à l'éditeur (`CTR-4`, `RSQ-7`) | Test commercial sur le pilote | Lot 2 |
| `HYP-17` | Le développement assisté par agent produit un gain global de 1,4 à 1,6 sur la charge du chapitre 08 (`ADR-16`) | Si le gain est inférieur, `ARB-20` devient plus contraignant encore ; s'il est supérieur, `V1` ou `V4` redeviennent envisageables | Mesure sur les 3 premiers mois du lot 1 | Lot 1 |
| `HYP-18` | Railway en plan Hobby, zone euro, convient au staging avec support du mode *worker* | Changement de plateforme de staging ; `ARC-86` (parité) compromis si le mode *worker* n'y est pas exécutable | Vérification technique | Lot 0 |

---

## 4. Arbitrages attendus

Classés par échéance. Les arbitrages marqués **bloquant** doivent être clos avant le lot indiqué, faute de quoi la conception ne peut démarrer.

| Réf | Objet | Recommandation AMOA | Échéance |
|---|---|---|---|
| `ARB-20` | **Trajectoire de ressource** : réduire le périmètre, réduire l'ambition produit, financer une équipe, ou accepter un horizon long (12 § 0.1) | Réduire l'ambition produit (outil mono-organisation d'abord), puis financer l'industrialisation SaaS sur une preuve d'usage | **Avant tout le reste** |
| `ARB-2` | Mesure des situations de référence avant lot 1 | La réaliser — sans elle, aucun objectif n'est démontrable | **Lot 0, immédiat** |
| `ARB-3` | Fournisseur de modèles IA et souveraineté | Exiger UE ; contractualiser la non-réutilisation des données | **Lot 0, bloquant** |
| `ARB-5` | Profondeur de la personnalisation | Établir une matrice « paramétrable / non paramétrable » et s'y tenir | **Lot 0, bloquant** |
| `ARB-18` | Version Symfony de départ et cible LTS | Suivre la branche stable avec tolérance zéro aux dépréciations, se poser sur la prochaine LTS. **Symfony 8.1 sort de support en janvier 2027 — vérifier le calendrier LTS** | **Lot 0, bloquant** |
| `ARB-25` | **Hébergement de production** — le staging est réglé (Railway Hobby, zone euro) | À instruire au lot 2, au regard des engagements `ENF-DISPO-1` à `-3` contractualisés envers les tenants | Lot 2 |
| `ARB-19` | Internalisation ou externalisation du design | Externaliser le design system et les parcours clés en lot 0, maintenir ensuite en interne | **Lot 0** |
| `ARB-10` | Cadre juridique du pré-remplissage par signaux d'activité | Conseil en droit social + AIPD | **Lot 0, bloquant pour `EF-TMP-10`** |
| `ARB-14` | Frontière aide à la décision RH / profilage | Conseil juridique + AIPD ; position conservatrice par défaut | **Lot 0, bloquant pour lot 4** |
| `ARB-4` | IA en évaluation de candidatures | Maintenir la restriction (extraction seule) | Lot 0 |
| `ARB-1` | Frontière avec les outils de gestion de tâches | S'interfacer, ne pas remplacer | Lot 1 |
| `ARB-11` | Politique de relance sur la saisie | Paramétrage par défaut à tester sur le pilote | Lot 1 |
| `ARB-9` | Maille de planification | Demi-journée par défaut | Lot 2 |
| `ARB-12` | Facturation électronique | **Vérifier auprès d'une source officielle** avant conception | Lot 2 |
| `ARB-13` | Ambition du rapprochement comptable | Export + écran de contrôle des écarts | Lot 2 |
| `ARB-8` | Détection de surcharge : limite à ne pas franchir | Borner à la charge ; aucun profilage comportemental | Lot 2 |
| `ARB-6` | Position vis-à-vis d'un CRM existant | Se synchroniser, ne pas concurrencer | Lot 3 |
| `ARB-7` | Sens de la synchronisation avec l'outil de tâches | Unidirectionnel | Lot 3 |
| `ARB-17` | Périmètre de l'assistant en langage naturel | Questions bornées d'abord ; exploration libre après test d'intrusion | Lot 3 |
| `ARB-15` | Articulation avec un SIRH existant | HotOnes consomme l'effectif, produit la compétence | Lot 4 |
| `ARB-16` | Position vis-à-vis d'un ATS existant | Ne pas concurrencer ; intégration légère | Lot 4 |
| `ARB-21` | Étendue de l'API publique | Délibérément étroite : une API étroite s'élargit, une API large ne se rétracte jamais | Lot 3 |
| `ARB-22` | Communication commerciale sur l'application mobile | Ne rien annoncer avant budgétisation | Lot 5 |
| `ARB-23` | Comparaison inter-organisations anonymisée | Instruire juridiquement avant toute conception ; consentement explicite et seuil de participants | Post-lot 5 |
| `ARB-24` | Offre optionnelle avec inférence IA incluse, pour les tenants ne voulant gérer ni clé ni serveur | La prévoir : l'architecture le permet sans modification. Répond à la friction d'installation créée par le modèle de clés client sur le segment 10-30 personnes | Lot 5, avec le modèle tarifaire |

---

## 5. Points restant à préciser

| Sujet | Module | Échéance |
|---|---|---|
| Granularité de l'avancement (lot seul ou lot + jalon) | `PRJ` | Lot 1, atelier avec 2 chefs de projet |
| Formats d'export paie et CIR | `TMP`, `RH` | Lot 2, selon outils des premiers clients |
| Formats d'export comptable | `FIN` | Lot 2, idem |
| Répartition des collaborateurs multi-projets simultanés | `PLN` | Lot 2, atelier |
| Canaux de diffusion et sites d'emploi à intégrer | `REC` | Lot 4, selon clients |
| Modèle tarifaire de HotOnes | Lot 5 | Hors périmètre de ce CDC — prérequis au lot 5 |
| Bibliothèque de composants servant de base au design system | `ADR-6` | Lot 0, décision conjointe design / technique |
| Base de données réelle du MVP et présence d'une couche applicative | `ADR-4`, `HYP-12` | Lot 0, audit `AUD-1` |
| Outil de profilage applicatif en production | `ADR-14` | Non prioritaire ; à réévaluer sur besoin réel |
| Classement cœur / support / générique de chaque module | `ARC-100` | Lot 0 — gouverne l'intensité de modélisation |
| Support de FrankenPHP en mode *worker* sur la plateforme de staging | `ARC-86`, `ADR-13` | Lot 0 — une demi-journée de vérification |
| Serveur applicatif, base et outillage réels du MVP | `AUD-1` | Lot 0 — complète le tableau 12 § 16 |

---

## 6. Prochaines étapes recommandées

Dans l'ordre, sans attendre la validation complète de ce document :

1. **Trancher `ARB-20` — la trajectoire de ressource.** C'est la décision qui détermine quelle portion de ce document est engageable ; toutes les autres en dépendent. Tant qu'elle n'est pas prise, le chapitre 08 chiffre un projet que personne n'a la capacité de réaliser.
2. **Valider ou infirmer `HYP-1`, `HYP-2` et `HYP-3`.** Une demi-journée. Si l'une est fausse, ce CDC doit être révisé avant d'aller plus loin.
3. **Lancer la mesure des situations de référence** (`ARB-2`). Quatre semaines de relevé, à démarrer maintenant : c'est le chemin critique du dispositif de démonstration du ROI.
4. **Commander l'audit de l'existant** (`AUD-1`, `AUD-2`). Une semaine. Il conditionne la validité de la recommandation du chapitre 07 et complète le tableau des préconisations techniques (12 § 11).
5. **Engager la qualification juridique** (`CTR-3`, `ARB-10`, `ARB-14`) et l'analyse d'impact. Délai externe long : à lancer tôt.
6. **Identifier et engager formellement l'organisation pilote**, avec un référent nommé et une disponibilité contractualisée d'un jour par semaine.
7. **Tenir les arbitrages bloquants du lot 0** en un atelier unique : `ARB-3`, `ARB-5`, `ARB-18`, `ARB-19`.
8. **Faire concevoir et tester le parcours de saisie de temps** avant tout développement (`MD-1`, `EVO-1.2`) : conçu pour un écran étroit d'abord.

**Une remarque de méthode pour finir.** Ce document décrit une cible à 18-22 mois. La tentation naturelle sera de le valider intégralement avant de commencer. C'est une erreur : le seul chapitre qui doit être validé avant le démarrage est le 07 (scénario de refonte), et les invariants du chapitre 06. Le reste se précise en avançant. Un CDC qu'on cherche à figer entièrement avant la première ligne de code est un CDC qui aura douze mois de retard sur la réalité le jour de sa validation.
