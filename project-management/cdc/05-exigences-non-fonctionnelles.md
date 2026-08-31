# 05 — Exigences non fonctionnelles

Chaque exigence est formulée avec un critère mesurable. Une exigence non fonctionnelle sans seuil chiffré est inopposable en recette et ne figure donc pas dans ce chapitre.

---

## 1. Performance et volumétrie (`ENF-PERF`)

### Volumétrie de dimensionnement

| Grandeur | Tenant petit | Tenant moyen | Tenant grand (cible max) |
|---|---|---|---|
| Collaborateurs actifs | 10 | 50 | 150 |
| Projets actifs simultanés | 15 | 80 | 300 |
| Projets en historique (5 ans) | 200 | 1 500 | 6 000 |
| Lignes de temps / an | 12 000 | 60 000 | 200 000 |
| Utilisateurs simultanés en pointe | 8 | 35 | 100 |
| Nombre de tenants sur la plateforme (an 3) | — | — | 150 `[HYP-11]` |

**Pointe d'usage :** la charge n'est pas uniforme. Les 3 derniers jours du mois et le lundi matin concentrent la saisie et la validation. Le dimensionnement doit se faire sur la pointe, pas sur la moyenne.

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-PERF-1` | M | Le temps de réponse des écrans de consultation courante doit être inférieur à 1 s au 95e centile, sur un tenant grand. | Mesuré en charge nominale. |
| `ENF-PERF-2` | M | Le temps de réponse de l'écran de saisie de temps doit être inférieur à 500 ms au 95e centile. | L'écran le plus utilisé du produit conditionne l'adoption. |
| `ENF-PERF-3` | M | Les tableaux de bord doivent se charger en moins de 3 s au 95e centile sur un tenant grand avec 5 ans d'historique. | Mesuré avec un jeu de données de volumétrie cible. |
| `ENF-PERF-4` | S | Le plan de charge sur 12 mois pour 150 collaborateurs doit s'afficher en moins de 2 s. | La vue la plus lourde du produit. |
| `ENF-PERF-5` | M | Les indicateurs financiers doivent refléter une validation de temps dans un délai maximum de 15 minutes. | Une latence supérieure ruine la confiance dans les chiffres. |
| `ENF-PERF-6` | S | Le système doit supporter la pointe de fin de mois (× 5 du trafic nominal) sans dégradation au-delà de 2× les seuils ci-dessus. | Test de charge obligatoire avant mise en production. |

## 2. Disponibilité et continuité (`ENF-DISPO`)

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-DISPO-1` | M | Disponibilité du service ≥ 99,5 % sur les heures ouvrées, hors maintenance planifiée. | Mesurée mensuellement, avec engagement contractuel envers les tenants. |
| `ENF-DISPO-2` | M | RPO (perte de données maximale admissible) ≤ 1 heure. | Sauvegardes testées par restauration réelle, au moins trimestriellement. |
| `ENF-DISPO-3` | M | RTO (délai de remise en service) ≤ 4 heures en heures ouvrées. | Procédure de reprise documentée et testée. |
| `ENF-DISPO-4` | S | Les maintenances planifiées doivent être annoncées 5 jours ouvrés à l'avance et réalisées hors heures ouvrées. | — |
| `ENF-DISPO-5` | S | Une dégradation d'un service IA ne doit jamais empêcher l'usage des fonctions cœur (saisie, consultation, validation). | Test de coupure du fournisseur de modèle : le produit reste pleinement utilisable en mode manuel. |

**Sur `ENF-DISPO-5` :** exigence structurante d'architecture. Toute fonction IA doit avoir un chemin manuel équivalent. Un produit dont la saisie de temps dépend d'un service d'inférence tiers est indisponible chaque fois que ce tiers l'est.

## 3. Sécurité (`ENF-SEC`)

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-SEC-1` | M | Authentification par identifiant/mot de passe avec politique de robustesse, et second facteur activable par tenant. | Le second facteur est imposable par l'administrateur tenant. |
| `ENF-SEC-2` | S | Authentification déléguée (SSO) via un protocole standard, activable par tenant. | Testé avec au moins deux fournisseurs d'identité du marché. |
| `ENF-SEC-3` | M | Chiffrement des données en transit et au repos. | Aucune donnée applicative n'est stockée en clair. |
| `ENF-SEC-4` | M | **Isolation stricte des données entre tenants.** Aucun chemin applicatif ne doit permettre l'accès à la donnée d'un autre tenant. | Test d'intrusion dédié avant mise en production, incluant des tentatives de traversée par identifiant forgé, par export et par l'assistant IA. **Critère bloquant.** |
| `ENF-SEC-5` | M | Application systématique des habilitations au niveau de l'accès aux données, pas au niveau de l'affichage. | Revue de code dédiée ; aucun filtrage d'habilitation réalisé côté client. |
| `ENF-SEC-6` | M | Toute fonction IA doit accéder aux données via le même contrôle d'habilitation que l'utilisateur qui la déclenche (`HAB-5`). | Test d'intrusion incluant l'injection de consigne et l'extraction par recoupement. **Critère bloquant.** |
| `ENF-SEC-7` | M | Journalisation des accès aux données sensibles (coûts, RH, entretiens) et des actions d'administration, conservée et consultable. | Piste d'audit consultable par l'administrateur tenant. |
| `ENF-SEC-8` | M | L'accès de l'équipe éditeur aux données d'un tenant doit être exceptionnel, motivé, tracé et notifié au tenant. | Aucun accès permanent ; procédure de support documentée et contractualisée. |
| `ENF-SEC-9` | S | Test d'intrusion externe annuel, et à chaque évolution majeure du modèle d'habilitation ou des fonctions IA. | Rapport et plan de remédiation. |
| `ENF-SEC-10` | S | Gestion des secrets et des clés hors du code source, avec rotation. | — |
| `ENF-SEC-11` | S | Analyse automatisée des dépendances et correction des vulnérabilités critiques sous 15 jours. | Intégrée à la chaîne d'intégration continue. |

**`ENF-SEC-4` et `ENF-SEC-6` sont les deux exigences les plus critiques du document.** Un incident de fuite inter-tenant sur des données de marge et de rémunération est un événement dont un éditeur de cette taille ne se remet pas commercialement. Elles ne sont pas négociables au titre du planning.

## 4. Conformité et protection des données (`ENF-RGPD`)

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-RGPD-1` | M | Registre des traitements tenu et à jour pour l'ensemble des traitements de la plateforme. | Document maintenu, revu annuellement. |
| `ENF-RGPD-2` | M | Durées de conservation définies et paramétrables par catégorie de données, avec purge ou anonymisation automatique effective. | La purge est vérifiable techniquement, pas seulement déclarée. |
| `ENF-RGPD-3` | M | Exercice des droits des personnes (accès, rectification, effacement, portabilité) outillé, dans les délais réglementaires. | Une demande d'accès est traitable en moins de 5 jours ouvrés. |
| `ENF-RGPD-4` | M | Minimisation : aucune donnée personnelle collectée sans finalité identifiée et documentée. | Revue de conformité par le DPO avant chaque mise en production d'un module traitant de données personnelles. |
| `ENF-RGPD-5` | M | Analyse d'impact relative à la protection des données (AIPD) réalisée pour les traitements RH, recrutement, et pour le pré-remplissage assisté par signaux d'activité. | **Prérequis bloquant** aux lots 1 (pour `EF-TMP-10`) et 4. |
| `ENF-RGPD-6` | M | Contrats de sous-traitance conformes avec tous les fournisseurs traitant de la donnée, y compris les fournisseurs de modèles d'IA. | — |
| `ENF-RGPD-7` | M | Hébergement et traitement des données dans l'Union européenne. | Y compris pour les traitements d'inférence IA. `[ARB-3]` |
| `ENF-RGPD-8` | M | Les données des tenants ne doivent en aucun cas être utilisées pour l'entraînement de modèles, sauf accord explicite, spécifique et révocable du tenant. | Engagement contractuel et vérification technique auprès du fournisseur de modèle. |
| `ENF-RGPD-9` | S | Réversibilité : le tenant doit pouvoir récupérer l'intégralité de ses données dans un format exploitable, à tout moment et à la résiliation. | Export complet réalisable en autonomie ; format documenté. |
| `ENF-RGPD-10` | M | Information des personnes sur les traitements automatisés les concernant, notamment les suggestions IA en matière de staffing et de compétences. | Mention accessible depuis les écrans concernés. |

**Sur `ENF-RGPD-8` :** c'est un point de vente autant qu'une exigence de conformité. Une agence ne confiera pas ses données de marge et de rémunération à un SaaS sans garantie contractuelle et technique sur ce point.

**Sur la réglementation européenne relative à l'IA :** les obligations applicables aux usages RH et d'évaluation des personnes — et leur calendrier d'entrée en vigueur — doivent être **vérifiées auprès d'une source officielle ou d'un conseil spécialisé**. Ce document identifie le risque et impose une analyse ; il ne se substitue pas à une qualification juridique. Cf. `CTR-3`, `[ARB-14]`.

## 5. Multi-tenant et exploitabilité (`ENF-SAAS`)

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-SAAS-1` | M | Architecture multi-tenant permettant l'isolation logique des données et un paramétrage indépendant par tenant. | Cf. `ENF-SEC-4`. |
| `ENF-SAAS-2` | M | Création d'un nouveau tenant opérationnelle en moins de 15 minutes, sans intervention manuelle sur l'infrastructure. | Cf. `EF-REF-29`. |
| `ENF-SAAS-3` | S | Déploiement d'une nouvelle version sans interruption de service perceptible. | — |
| `ENF-SAAS-4` | S | Possibilité de déployer une fonctionnalité progressivement (activation par tenant). | Permet le pilotage de fonctions sensibles sur un tenant volontaire. |
| `ENF-SAAS-5` | M | Supervision technique et fonctionnelle : disponibilité, temps de réponse, erreurs, consommation IA par tenant. | Alerte automatique en cas de dépassement de seuil. |
| `ENF-SAAS-6` | S | Le produit doit être exploitable par une équipe de 2 à 4 personnes sans astreinte 24/7 (`CTR-1`). | Contrainte d'architecture : rejeter toute solution nécessitant une exploitation dédiée. |

## 6. Exigences relatives à l'IA (`ENF-IA`)

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-IA-1` | M | **Explicabilité.** Toute suggestion, alerte ou synthèse produite par une fonction IA doit exposer les données et les critères qui la fondent. | Aucune fonction IA n'est mise en production sans ce dispositif. **Critère bloquant.** |
| `ENF-IA-2` | M | **Non-substitution.** Aucune décision affectant un projet, une rémunération, une affectation ou une personne n'est prise automatiquement. L'IA propose, un humain décide. | Revue fonctionnelle de chaque fonction IA avant mise en production. |
| `ENF-IA-3` | M | **Séparation calcul / rédaction.** Aucun chiffre affiché ou diffusé ne provient d'un modèle de langage. Les modèles rédigent et structurent ; le système calcule. | Revue de conception ; test de non-régression sur les synthèses générées. |
| `ENF-IA-4` | M | **Traçabilité.** Toute interaction avec un modèle est journalisée : fonction appelante, utilisateur, périmètre de données mobilisé, horodatage, coût. | Journal exploitable pour l'audit et le suivi de consommation. |
| `ENF-IA-5` | M | **Maîtrise du coût.** Le coût d'inférence doit être suivi par tenant, plafonné, et la dégradation en cas de dépassement doit être gracieuse. | Un tenant qui atteint son plafond conserve toutes les fonctions cœur (`CTR-4`, `ENF-DISPO-5`). |
| `ENF-IA-6` | S | **Réversibilité du fournisseur.** L'architecture doit permettre le changement de fournisseur de modèle sans réécriture des fonctions métier. | Couche d'abstraction validée par un test de bascule. |
| `ENF-IA-7` | S | **Mesure de la qualité.** Chaque fonction IA doit disposer d'un indicateur de qualité mesuré en production (taux d'acceptation des suggestions, taux de faux positifs des alertes). | Suivi mensuel ; toute fonction sous son seuil pendant 3 mois est révisée ou retirée. |
| `ENF-IA-8` | M | **Retour utilisateur.** Chaque suggestion doit pouvoir être signalée comme inexacte ou inutile en un geste, et ce signalement doit alimenter le suivi de qualité. | — |
| `ENF-IA-9` | M | **Désactivation.** Toute fonction IA doit être désactivable par tenant, et le produit doit rester pleinement fonctionnel sans elle. | Test de recette avec toutes les fonctions IA désactivées. |

**Sur `ENF-IA-7` :** sans mesure de qualité en production, les fonctions IA dérivent silencieusement et perdent la confiance des utilisateurs avant que l'éditeur ne s'en aperçoive. C'est un dispositif de gouvernance produit, pas une option technique.

**Sur `ENF-IA-9` :** un tenant doit pouvoir refuser l'IA. Certains clients — secteur public, santé, défense — le refuseront contractuellement. Un produit dont l'IA n'est pas désactivable se ferme ces marchés.

## 7. Ergonomie et accessibilité (`ENF-UX`)

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-UX-1` | M | Le parcours de saisie de temps doit respecter le budget de 2 minutes par semaine (`EF-TMP-3`). | Test utilisateur sur 5 profils représentatifs. **Critère bloquant du lot 1.** |
| `ENF-UX-2` | M | Un nouvel utilisateur collaborateur doit être autonome sur la saisie sans formation, avec la seule aide contextuelle. | Test utilisateur sur 3 personnes n'ayant jamais vu le produit. |
| `ENF-UX-3` | S | Les écrans destinés aux collaborateurs (saisie, planning, absences) doivent être pleinement utilisables sur mobile. | — |
| `ENF-UX-4` | S | Conformité au niveau AA du référentiel d'accessibilité applicable aux contenus web. | Audit d'accessibilité sur les parcours principaux. |
| `ENF-UX-5` | S | Le produit doit être disponible en français et en anglais, avec une architecture permettant l'ajout d'autres langues. | Aucune chaîne en dur dans le code. |
| `ENF-UX-6` | S | Toute action destructive doit être confirmée et, lorsque c'est possible, réversible. | — |
| `ENF-UX-7` | C | Aide contextuelle et parcours de découverte intégrés au produit. | — |

**Sur `ENF-UX-4` :** l'accessibilité est souvent traitée comme optionnelle sur un outil interne. C'est une erreur ici pour deux raisons : le produit est vendu à des tiers, dont certains ont des obligations d'accessibilité, et un salarié en situation de handicap doit pouvoir saisir son temps comme les autres. Rétro-adapter l'accessibilité coûte trois à cinq fois plus cher que la concevoir.

## 8. Maintenabilité et évolutivité (`ENF-MAINT`)

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-MAINT-1` | M | Couverture de tests automatisés sur les règles de gestion critiques (valorisation, marge, capacité, habilitations) ≥ 80 %. | Mesurée en intégration continue ; seuil bloquant pour la mise en production. |
| `ENF-MAINT-2` | M | Chaîne d'intégration et de déploiement continus, avec environnements de développement, recette et production distincts. | — |
| `ENF-MAINT-3` | S | Documentation technique du modèle de données et des règles de calcul, maintenue à jour. | — |
| `ENF-MAINT-4` | S | API documentée et versionnée pour les intégrations tierces. | Documentation générée depuis le code. |
| `ENF-MAINT-5` | S | Jeux de données de test représentatifs des trois tailles de tenant, régénérables. | Indispensable aux tests de performance. |

## 9. Support et service (`ENF-SUP`)

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `ENF-SUP-1` | S | Canal de support intégré au produit, avec transmission du contexte technique. | — |
| `ENF-SUP-2` | S | Engagements de délai de prise en charge par niveau de criticité, contractualisés. | — |
| `ENF-SUP-3` | S | Base de connaissances et documentation utilisateur maintenue. | — |
| `ENF-SUP-4` | C | Statut de service public consultable par les tenants. | — |
