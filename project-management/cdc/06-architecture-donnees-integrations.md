# 06 — Modèle de données, socle IA et intégrations

Ce chapitre décrit **ce que le système doit modéliser**, pas comment le coder. Il fixe les invariants structurels sur lesquels aucune liberté de conception n'est laissée, parce que les remettre en cause plus tard coûte une réécriture.

---

## 1. Modèle de données conceptuel

### 1.1 Entités pivots

```
                              ┌──────────┐
                              │  TENANT  │
                              └────┬─────┘
                                   │ (toute entité en dépend)
        ┌──────────────┬───────────┼───────────┬──────────────┐
        │              │           │           │              │
   ┌────▼────┐   ┌─────▼─────┐ ┌───▼────┐ ┌────▼─────┐  ┌─────▼──────┐
   │ COMPTE  │   │COLLABORA- │ │ PROFIL │ │COMPÉTENCE│  │ CALENDRIER │
   │ CLIENT  │   │   TEUR    │ └───┬────┘ └────┬─────┘  └────────────┘
   └────┬────┘   └─────┬─────┘     │           │
        │              │           │           │
   ┌────▼──────┐       │      (coût, taux)  (niveau,
   │OPPORTUNITÉ│       │       historisés    validé)
   └────┬──────┘       │
        │              │
   ┌────▼────┐         │
   │  DEVIS  │─────────┼──────────┐
   └────┬────┘         │          │
        │ (bascule)    │          │
   ┌────▼────┐    ┌────▼──────┐   │
   │ PROJET  │───►│AFFECTATION│◄──┘
   └────┬────┘    └────┬──────┘
        │              │
   ┌────▼────┐    ┌────▼──────┐    ┌───────────┐
   │   LOT   │◄───│IMPUTATION │───►│ VALORISA- │
   └────┬────┘    │  (TEMPS)  │    │   TION    │
        │         └───────────┘    └───────────┘
   ┌────▼────┐
   │ JALON   │──► FACTURE ──► ENCAISSEMENT
   └─────────┘
```

### 1.2 Invariants structurels

Ces règles ne sont pas des recommandations. Les enfreindre produit une dette non remboursable.

| Réf | Invariant | Motif |
|---|---|---|
| `INV-1` | **Toute entité porte un identifiant de tenant.** L'isolation est portée par le modèle, pas par le code applicatif. | Une isolation implémentée uniquement dans les requêtes finit toujours par avoir un trou. |
| `INV-2` | **Toute donnée à impact financier est historisée avec date d'effet** (taux, coût, clé de répartition, taux d'activité). | Sans cela, toute valorisation passée est réécrite à chaque changement de tarif. Irrattrapable a posteriori. |
| `INV-3` | **L'imputation de temps est l'unité élémentaire immuable** de la chaîne financière. Elle porte : collaborateur, date, projet, lot, durée, statut, et sa valorisation figée à la validation. | Recalculer les valorisations à la volée à partir des paramètres courants rend tout historique instable. |
| `INV-4` | **Avancement, reste à faire et consommation sont trois données distinctes et indépendantes.** | Toute déduction de l'une par l'autre supprime la capacité de détecter une dérive. |
| `INV-5` | **Charge ferme et charge probable sont deux natures distinctes**, jamais fusionnées en base. | La distinction doit survivre à tous les agrégats. |
| `INV-6` | **Aucune suppression physique** des entités métier : désactivation et archivage. | Traçabilité, audit, cohérence des historiques. |
| `INV-7` | **Toute action utilisateur à effet métier est journalisée** (qui, quoi, quand, valeur avant/après). | Exigé par l'audit financier et par le RGPD. |
| `INV-8` | **Un projet conserve un lien permanent vers le devis qui l'a engendré et vers ses avenants.** | Condition de la comparaison vendu/réalisé, elle-même condition de l'estimation assistée. |

**Sur `INV-2` et `INV-3` :** ce sont les deux invariants dont l'omission est la plus fréquente et la plus coûteuse. Ils doivent être posés dès le premier schéma de données du lot 1.

### 1.3 Référentiels de données

| Référentiel | Portée | Historisé | Remarque |
|---|---|---|---|
| Organisation | Tenant | Oui | Reconstitution de l'organigramme à toute date |
| Profils | Tenant | Oui (coût, taux) | Pivot de la valorisation |
| Compétences | Tenant, initialisé depuis un socle éditeur | Non (le niveau l'est) | Fusion contrôlée, jamais automatique |
| Calendriers | Tenant, entité, collaborateur | Oui | Base du calcul de capacité |
| Types d'absence | Tenant | Non | Impact capacité paramétrable |
| Comptes clients | Tenant | Non | Hiérarchie groupe/filiale |
| Statuts et workflows | Tenant | Non | Périmètre de personnalisation à arbitrer (`ARB-5`) |

---

## 2. Socle technique

### 2.1 Contraintes d'architecture

| Réf | Contrainte | Motif |
|---|---|---|
| `ARC-1` | Application web, sans installation client. | Cible SaaS. |
| `ARC-2` | Multi-tenant à isolation logique, avec une stratégie d'isolation vérifiable par test. | `ENF-SEC-4`. |
| `ARC-3` | Exploitable par une équipe de 2 à 4 personnes, sans astreinte permanente. | `CTR-1`, `ENF-SAAS-6`. Interdit les architectures distribuées complexes. |
| `ARC-4` | Hébergement et traitements dans l'Union européenne. | `ENF-RGPD-7`. |
| `ARC-5` | Toute fonction IA passe par une couche d'abstraction unique, jamais par un appel direct depuis le code métier. | `ENF-IA-6`, `ENF-IA-4`, `ENF-IA-5`. |
| `ARC-6` | Les calculs financiers et capacitaires sont implémentés dans un moteur unique, testé, jamais dupliqué entre modules ou entre backend et frontend. | Deux implémentations d'un calcul de marge produisent tôt ou tard deux chiffres différents. |
| `ARC-7` | API interne cohérente, servant à la fois l'interface et les intégrations tierces. | `ENF-MAINT-4`. |

**Sur `ARC-3` :** contrainte à opposer à toute proposition d'architecture. Une architecture en microservices, event-sourcée ou multi-briques est séduisante sur le papier et ingérable pour une équipe de trois personnes qui doit aussi livrer des fonctionnalités. Le choix du socle doit être arbitré sur ce critère avant tout autre.

**Sur `ARC-6` :** le moteur de calcul unique est ce qui permet à `EF-PIL-5` (traçabilité de tout indicateur) d'être tenable. Sans lui, chaque écran recalcule à sa façon et les chiffres divergent.

### 2.2 Socle des fonctions IA

Les fonctions IA sont réparties dans les modules métier (`HYP-4`), mais elles partagent une infrastructure commune dont les exigences sont posées ici.

| Réf | Exigence de socle | Rattachement |
|---|---|---|
| `ARC-8` | Une couche d'accès aux modèles, unique, gérant : sélection du modèle, quota, coût, journalisation, reprise sur erreur, dégradation. | `ENF-IA-4`, `ENF-IA-5`, `ENF-IA-6` |
| `ARC-9` | Un mécanisme de construction de contexte qui applique **les habilitations de l'utilisateur à la source des données**, avant toute transmission à un modèle. | `ENF-SEC-6`, `HAB-5` — **le point critique du socle** |
| `ARC-10` | Un mécanisme de citation : toute réponse générée référence les enregistrements qui l'ont alimentée. | `ENF-IA-1` |
| `ARC-11` | Une séparation stricte entre les valeurs calculées par le système et le texte produit par un modèle, avec assemblage côté système. | `ENF-IA-3` |
| `ARC-12` | Un dispositif de collecte du retour utilisateur sur chaque suggestion, alimentant les indicateurs de qualité. | `ENF-IA-7`, `ENF-IA-8` |
| `ARC-13` | Un commutateur d'activation par tenant et par fonction IA. | `ENF-IA-9`, `ENF-SAAS-4` |

> **Mise en œuvre.** Ces six exigences décrivent *ce que le socle doit faire*, indépendamment de la technologie. Leur implémentation est arrêtée à l'`ADR-10` du chapitre 12 : les composants **Symfony AI** (Platform, Agent, Store) portent l'accès multi-fournisseur, l'abstraction vectorielle et les outils de test ; une couche produit mince porte ce qu'aucun composant générique ne peut porter — la construction de contexte sous habilitations (`ARC-9`), le plafonnement par tenant, la citation des sources et l'assemblage séparé des valeurs calculées.
>
> **Modèle économique du socle.** Les clés d'accès aux fournisseurs sont **fournies et paramétrées par chaque tenant** dans son espace d'administration, avec une entrée « modèle local » prévue dès la conception (`ADR-10`). Conséquence directe sur ce chapitre : `ARC-13` (commutateur par tenant) n'est plus une option de confort mais une propriété structurelle — un tenant sans clé n'a simplement aucune fonction IA, et le produit doit rester pleinement utilisable dans cet état (`ARC-80`, `ENF-IA-9`).

**Sur `ARC-9` :** le risque dominant du projet sur le plan de la sécurité est l'exposition indirecte de données par une fonction IA. Deux erreurs classiques à interdire explicitement en conception :

1. Transmettre à un modèle un contexte plus large que le périmètre de l'utilisateur, en comptant sur une consigne pour qu'il n'en révèle qu'une partie. **Une consigne n'est pas un contrôle d'accès.**
2. Filtrer la réponse générée plutôt que la donnée d'entrée. Un modèle peut restituer une information par inférence ou par agrégat sans la citer littéralement.

Le filtrage se fait à la requête, avant le modèle. Ce point doit être conçu, revu et testé par intrusion avant la mise en production de la première fonction IA conversationnelle.

### 2.3 Répartition des fonctions IA par module

| Module | Fonctions IA | Nature dominante |
|---|---|---|
| Référentiels | `EF-REF-13`, `-14`, `-34` | Structuration, assistance au paramétrage |
| CRM | `EF-CRM-23` à `-26` | Extraction documentaire, estimation par historique, rédaction |
| Projets | `EF-PRJ-28` à `-31` | Synthèse, détection de signaux faibles |
| Planification | `EF-PLN-23` à `-26` | Recommandation explicable, détection de tension |
| Temps | `EF-TMP-9`, `-10`, `-12`, `-13`, `-25` | **Réduction de friction de saisie — priorité n°1** |
| Finance | `EF-FIN-25` à `-28` | Synthèse commentée, exploration, décomposition d'écart |
| RH | `EF-RH-11`, `-12`, `-19`, `-23` | Structuration, jamais évaluation |
| Recrutement | `EF-REC-2`, `-14`, `-15` | Détection de besoin, extraction documentaire |
| Pilotage | `EF-PIL-18` à `-22` | Exploration en langage naturel, synthèse |

**Priorité d'investissement IA :** le module Temps d'abord. C'est là que l'IA produit le plus de valeur réelle (fiabilisation de la donnée qui alimente tout le reste) pour le risque le plus faible. Les fonctions conversationnelles du module Pilotage sont les plus démonstratives et les plus risquées : elles viennent après, une fois le socle de sécurité éprouvé.

---

## 3. Intégrations

### 3.1 Cartographie

| Domaine | Systèmes cibles | Sens | Priorité | Lot |
|---|---|---|---|---|
| Identité | Fournisseur d'identité d'entreprise (SSO) | Entrant | S | 2 |
| Agenda | Calendrier professionnel | Entrant (signal de pré-remplissage) | S | 2 |
| Gestion de tâches | Jira, Linear ou équivalent | Entrant | S | 3 |
| Dépôts de code | Plateforme de gestion de code | Entrant (signal de pré-remplissage) | C | 3 |
| Comptabilité | Logiciel comptable du tenant | Sortant | S | 2 |
| Paie | Logiciel de paie du tenant | Sortant | S | 4 |
| Signature | Service de signature électronique | Bidirectionnel | S | 3 |
| CRM | CRM du tenant | Bidirectionnel | S | 3 |
| SIRH / ATS | SIRH et ATS du tenant | Bidirectionnel | C | 4 |
| Messagerie d'équipe | Outil de messagerie du tenant | Sortant (notifications) | C | 3 |
| BI | Outil d'analyse du tenant | Sortant | C | 4 |
| Modèles d'IA | Fournisseur d'inférence UE | Sortant | M | 1 |

### 3.2 Principes d'intégration

| Réf | Principe |
|---|---|
| `INT-1` | Chaque intégration a une **source de vérité unique et documentée** par objet. Une donnée n'a jamais deux maîtres. |
| `INT-2` | Le sens par défaut est **unidirectionnel**. La bidirectionnalité est une exception justifiée, jamais un défaut. |
| `INT-3` | Toute intégration doit fonctionner en mode dégradé : l'indisponibilité d'un système tiers ne bloque aucune fonction cœur. |
| `INT-4` | Les erreurs de synchronisation sont visibles d'un administrateur tenant, avec possibilité de rejeu. |
| `INT-5` | Toute donnée entrante utilisée comme signal de pré-remplissage est soumise au consentement du collaborateur (`RG-MP4-5`). |
| `INT-6` | Les intégrations sont construites sur l'API interne du produit (`ARC-7`), pas sur des accès directs à la base. |

**Sur `INT-2` :** chaque synchronisation bidirectionnelle ajoutée multiplie les cas de conflit à arbitrer, et ces conflits sont la première source de tickets de support d'un ERP intégré. Recommandation AMOA : n'accepter la bidirectionnalité que sur la signature électronique (statut) et, éventuellement, le CRM — et encore, sur un périmètre de champs strictement délimité.

---

## 4. Reprise de données

L'hypothèse `HYP-1` (MVP partiel, pas de production) réduit fortement ce chantier. Sous cette hypothèse :

| Réf | Exigence | Prio |
|---|---|---|
| `REP-1` | Reprise des référentiels du MVP (clients, collaborateurs, profils) si leur qualité le justifie ; sinon ressaisie. | S |
| `REP-2` | Reprise de l'historique de projets **si et seulement si** il est suffisamment volumineux et fiable pour alimenter l'estimation assistée (`EF-CRM-24`). En dessous de 10 projets exploitables, la reprise n'a pas de valeur. | C |
| `REP-3` | Import initial depuis tableur pour l'onboarding d'un nouveau tenant : collaborateurs, clients, projets en cours, soldes de congés. | M |
| `REP-4` | Contrôle de cohérence et rapport d'anomalies sur tout import. | M |

**`REP-3` est l'exigence importante ici**, pas la reprise du MVP. Chaque nouveau client arrivera avec ses données dans des tableurs. La qualité de cet import conditionne le coût d'acquisition de chaque tenant.

**Si `HYP-1` est fausse** — si le MVP est réellement en production avec des données vivantes — ce chapitre change de nature : il faut un plan de reprise complet, une stratégie de bascule et une période de double saisie ou de cohabitation. Compter 3 à 5 mois supplémentaires et un risque projet significativement plus élevé.
