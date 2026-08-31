# Personas — HotOnes

**Projet :** HotOnes — ERP de gestion d'agence digitale / ESN
**Source de vérité :** `cdc/02-perimetre-acteurs.md`
**Date :** 2026-08-31

Six personas structurent la conception. Chacun a un **enjeu principal**, une **fréquence d'usage**, un **critère de rejet** et un **niveau technique**. Le critère de rejet est le signal de conception le plus utile : il définit la ligne rouge à ne pas franchir.

---

## P1 — Camille — Collaboratrice (développeuse)

### Identité

- **Rôle :** Membre de l'équipe de production (développeuse, consultante, designer, etc.)
- **Âge :** 28 ans
- **Niveau technique :** Élevé dans son métier ; moyen sur les outils RH/gestion
- **Volume :** **80 % des utilisateurs** de la plateforme

### Citation

> « Si je dois passer plus de deux minutes à saisir mon temps, j'arrête. Et si j'ai l'impression que c'est pour me surveiller plutôt que pour piloter les projets, je fais le minimum syndical. »

### Fréquence et durée d'usage

- **Fréquence :** Quotidienne (saisie) ou hebdomadaire (planning)
- **Durée :** 2 à 5 minutes par session

### Objectifs

1. Saisir son temps en moins de 2 minutes sans y penser — idéalement le lundi matin.
2. Voir son planning des deux prochaines semaines pour s'organiser.
3. Poser ses congés et absences sans démarche administrative lourde.
4. Comprendre à quoi servent ses saisies (contrepartie visible : avancement projet, marge).

### Frustrations actuelles

1. La saisie de temps est longue, peu ergonomique, et ne lui apporte rien directement.
2. Elle ne sait pas si son planning est à jour — elle l'apprend en réunion.
3. Poser un congé nécessite d'envoyer un email et d'attendre une confirmation.
4. Elle ressent la saisie comme un outil de contrôle, pas de pilotage.

### Critère de rejet

> **La saisie dépasse deux minutes**, ou elle sert visiblement à la surveiller plutôt qu'à piloter les projets.

### Scénario d'utilisation clé

**Contexte :** Lundi matin, 9h. Camille ouvre HotOnes sur son téléphone.
**Besoin :** Valider la semaine passée (temps) et vérifier la semaine à venir (planning).
**Action :** Le pré-remplissage IA a déjà proposé une répartition basée sur son planning et ses signaux d'activité (réunions, commits). Elle ajuste deux lignes et valide.
**Résultat :** 90 secondes. Elle voit aussi que son projet est à 72 % du budget — elle le signale à son CP.

### Habilitations clés

| Objet | Accès |
|-------|-------|
| Son propre temps | C L M |
| Ses affectations | L |
| Ses projets — fiche | L |
| Ses compétences | L M |
| Ses absences/congés | C L |

---

## P2 — Marc — Chef de projet

### Identité

- **Rôle :** Chef de projet / Directeur de projet
- **Âge :** 35 ans
- **Niveau technique :** Moyen-élevé ; à l'aise avec les outils de gestion de projet

### Citation

> « Je veux voir la dérive avant qu'elle soit irrattrapable, pas quand il ne reste plus rien à faire. »

### Fréquence et durée d'usage

- **Fréquence :** Quotidienne à hebdomadaire
- **Durée :** 20 à 40 minutes par session

### Objectifs

1. Voir l'état de consommation de ses projets en temps réel (budget, RAF, avancement).
2. Être alerté d'une dérive avant d'avoir consommé 50 % du budget.
3. Connaître la disponibilité de ses ressources pour les 4 prochaines semaines.
4. Préparer ses reporting hebdomadaires sans ressaisie.

### Frustrations actuelles

1. Le dépassement apparaît dans le reporting mensuel, quand il est trop tard.
2. Il doit ressaisir dans l'outil de gestion ce qui est déjà dans Jira.
3. Les chiffres de consommation sont calculés manuellement depuis un tableur.
4. Les temps non saisis par ses équipes rendent ses marges fausses.

### Critère de rejet

> **Il doit ressaisir dans HotOnes ce qui existe ailleurs**, ou les chiffres de consommation sont faux.

### Scénario d'utilisation clé

**Contexte :** Mardi matin. Marc a reçu une alerte HotOnes : le projet Acme est à 68 % du budget pour 55 % d'avancement.
**Besoin :** Comprendre d'où vient l'écart et décider d'une action.
**Action :** Il ouvre la fiche projet, voit la décomposition consommation / RAF par profil, identifie un sous-lot qui a dérivé. Il ajuste le RAF avec le titulaire et déclenche une alerte vers Sophie (resource manager).
**Résultat :** 15 minutes. La dérive est connue à 55 % du budget. Les leviers de correction sont encore disponibles.

### Habilitations clés

| Objet | Accès |
|-------|-------|
| Temps de l'équipe projet | L V |
| Affectations (ses projets) | L M |
| Projet — fiche | C L M |
| Projet — budget et marge | L M |
| Entretien / évaluation (son équipe) | C L M |
| Besoin de recrutement | C (proposer) |

---

## P3 — Sophie — Resource Manager / Directrice de production

### Identité

- **Rôle :** Resource manager ou directrice de production
- **Âge :** 42 ans
- **Niveau technique :** Élevé sur les outils de gestion ; forte maîtrise des tableurs

### Citation

> « Mon tableur est plus fiable que n'importe quel outil parce que je le contrôle. Pour que je l'abandonne, il faut que l'outil me donne plus de visibilité, pas moins. »

### Fréquence et durée d'usage

- **Fréquence :** Hebdomadaire (réunion de staffing) + consultations ponctuelles
- **Durée :** 1 à 2 heures par session

### Objectifs

1. Arbitrer les affectations sur 4 à 12 semaines, avec une vue de la capacité nette réelle.
2. Voir les conflits d'affectation et les sous-charges en un coup d'oeil.
3. Simuler l'impact d'une nouvelle affaire sur la capacité avant de s'engager.
4. Recevoir des suggestions d'affectation explicables (pas une boîte noire).

### Frustrations actuelles

1. Le tableur de staffing est la seule source fiable, mais personne ne le consulte.
2. Elle découvre les conflits en réunion, pas avant.
3. Les suggestions automatiques (si elles existent) ne s'expliquent pas — elle ne leur fait pas confiance.
4. La capacité affichée dans les outils ne tient pas compte des absences réelles ni du pipeline.

### Critère de rejet

> **Le plan de charge affiché ne correspond pas à ce qu'elle sait de la réalité**, ou le moteur de suggestion ne s'explique pas.

### Scénario d'utilisation clé

**Contexte :** Jeudi — réunion hebdo de staffing. Sophie ouvre le plan de charge.
**Besoin :** Arbitrer trois conflits d'affectation et simuler l'intégration d'une nouvelle affaire urgente.
**Action :** Elle voit la capacité nette par profil sur 12 semaines (congés et absences déjà déduits). Elle identifie un développeur senior disponible et l'affecte. Pour la nouvelle affaire, elle lance une simulation : impact sur le reste de l'équipe, tension sur les profils juniors.
**Résultat :** 45 minutes. La réunion se termine sans tableur. La simulation est sauvegardée pour le CODIR.

### Habilitations clés

| Objet | Accès |
|-------|-------|
| Plan de charge global | C L M |
| Toutes affectations | C L M V |
| Temps de l'équipe | L |
| Profils et taux | L |
| Besoin de recrutement | C |

---

## P4 — Yann — Commercial / Directeur de clientèle

### Identité

- **Rôle :** Commercial, responsable de compte ou directeur de clientèle
- **Âge :** 38 ans
- **Niveau technique :** Moyen ; à l'aise avec les outils CRM

### Citation

> « Ce dont j'ai besoin, c'est de savoir si on peut s'engager sur une date. Pas dans 3 jours après validation de 4 personnes. »

### Fréquence et durée d'usage

- **Fréquence :** Quotidienne
- **Durée :** 15 à 30 minutes par session

### Objectifs

1. Gérer son pipeline d'opportunités avec pondération réaliste.
2. Produire un devis cohérent avec la capacité disponible réelle.
3. S'engager sur une date de démarrage en ayant une réponse immédiate.
4. Basculer un devis en projet sans aucune ressaisie.

### Frustrations actuelles

1. Il ne sait jamais si la capacité annoncée est réelle ou optimiste.
2. Produire un devis prend du temps — il doit interroger le resource manager manuellement.
3. Le passage devis → projet entraîne toujours de la ressaisie.
4. L'outil le bloque tant que le paramétrage n'est pas parfait.

### Critère de rejet

> **L'outil l'empêche d'avancer sur une affaire faute de paramétrage parfait**, ou la capacité affichée ne reflète pas la réalité.

### Scénario d'utilisation clé

**Contexte :** Appel d'un prospect — projet urgent pour dans 3 semaines.
**Besoin :** Savoir si c'est faisable et à quel prix.
**Action :** Yann crée une opportunité, sélectionne les profils et la charge estimée. HotOnes lui affiche la disponibilité réelle et lui propose une marge prévisionnelle. Il génère un devis en 10 minutes.
**Résultat :** Il rappelle le prospect dans l'heure avec une réponse fondée. Quand le devis est accepté, un clic le bascule en projet — sans ressaisie.

### Habilitations clés

| Objet | Accès |
|-------|-------|
| Opportunités / devis | C L M |
| Plan de charge global | L |
| Taux de vente | L |
| Budget et marge projet | L (ses affaires) |
| Facturation | L (ses affaires) |

---

## P5 — Nadia — Responsable RH

### Identité

- **Rôle :** Responsable des ressources humaines
- **Âge :** 45 ans
- **Niveau technique :** Moyen ; utilise un SIRH séparé pour la paie et l'administratif

### Citation

> « Je passe mon temps à relancer les managers pour les entretiens. Si l'outil peut m'éviter ça, il me convient. Mais s'il duplique mon SIRH ou expose des données d'évaluation à tout le monde, il crée plus de problèmes qu'il n'en résout. »

### Fréquence et durée d'usage

- **Fréquence :** Hebdomadaire
- **Durée :** 1 à 3 heures par session

### Objectifs

1. Piloter le cycle des entretiens annuels sans relancer 40 personnes individuellement.
2. Maintenir une cartographie des compétences à jour pour alimenter le staffing.
3. Détecter les besoins de recrutement à partir de la tension capacitaire, pas du constat.
4. Gérer les absences et congés sans conflit avec la comptabilité ni le SIRH.

### Frustrations actuelles

1. Les managers font les entretiens mais ne renseignent pas les compétences.
2. La cartographie des compétences est dans un tableur que personne ne maintient.
3. Elle découvre le besoin de recrutement quand la surcharge est déjà là.
4. Les données d'évaluation sont mal protégées — n'importe quel manager peut voir n'importe quoi.

### Critère de rejet

> **Le module duplique le SIRH**, ou les données d'évaluation sont accessibles à des personnes qui ne devraient pas les voir.

### Scénario d'utilisation clé

**Contexte :** Campagne d'entretiens annuels — 35 collaborateurs.
**Besoin :** Suivre l'avancement sans relancer individuellement chaque manager.
**Action :** Nadia ouvre le tableau de bord des entretiens. Elle voit en un coup d'oeil les 12 entretiens non planifiés. Elle envoie un rappel groupé depuis l'outil. Pour chaque entretien réalisé, les compétences validées alimentent automatiquement la cartographie.
**Résultat :** 30 minutes de travail au lieu de 3 heures de relances. Les données restent strictement habilités : le manager voit son équipe, pas les autres.

### Habilitations clés

| Objet | Accès |
|-------|-------|
| Fiche collaborateur | C L M |
| Compétences | C L M V |
| Entretien / évaluation | C L M |
| Absences / congés | C L M V |
| Coût collaborateur | L |
| Besoin de recrutement | C L M V |
| Candidature | C L M |

> **`HAB-2` critique :** le contenu d'un entretien n'est visible que de l'intéressé, de son manager direct et de la RH. La direction n'a accès qu'aux agrégats et au statut de réalisation.

---

## P6 — Élodie — Dirigeante / Associée

### Identité

- **Rôle :** Directrice générale ou associée de l'agence
- **Âge :** 48 ans
- **Niveau technique :** Moyen ; utilise peu d'outils opérationnels directement

### Citation

> « Je n'ai pas besoin de détails — j'ai besoin de voir d'un coup d'oeil si on est rentable, si on est en risque, et si on va avoir un problème de capacité dans trois mois. Et je dois pouvoir expliquer ce que je vois à mon expert-comptable. »

### Fréquence et durée d'usage

- **Fréquence :** Hebdomadaire à mensuelle
- **Durée :** 30 minutes par session

### Objectifs

1. Voir la marge par projet et l'atterrissage prévisionnel en une vue synthétique.
2. Identifier les tensions de capacité et les risques de sous-occupation à 3 mois.
3. Valider que les chiffres HotOnes sont réconciliés avec la comptabilité.
4. Prendre des décisions de recrutement ou d'investissement sur des données fiables.

### Frustrations actuelles

1. Elle reçoit des tableaux Excel produits manuellement — une fois par mois, avec 3 semaines de retard.
2. Les chiffres changent selon qui les produit.
3. Elle ne peut pas expliquer d'où vient un chiffre à son expert-comptable.
4. L'écart entre la marge HotOnes et la comptabilité n'est jamais expliqué.

### Critère de rejet

> **Elle ne peut pas expliquer d'où vient un chiffre**, ou l'écart avec la comptabilité est inexpliqué.

### Scénario d'utilisation clé

**Contexte :** Lundi matin avant le CODIR.
**Besoin :** Préparer le point mensuel en 30 minutes.
**Action :** Elle ouvre le tableau de bord dirigeant. Marge consolidée, taux d'occupation, pipeline pondéré, alertes de dérive projet, tension capacitaire à 3 mois. Chaque chiffre est cliquable : elle remonte jusqu'aux imputations de temps qui le fondent.
**Résultat :** Elle arrive en CODIR avec des chiffres traçables. L'écart avec la comptabilité est affiché, expliqué (`EF-FIN-23`). Elle peut répondre aux questions de son expert-comptable.

### Habilitations clés

| Objet | Accès |
|-------|-------|
| Projet — budget et marge | L M |
| Plan de charge global | L |
| Facturation | C L M V |
| Opportunités / devis | L V |
| Fiche collaborateur (pro) | L |
| Entretien / évaluation | L (agrégat uniquement — `HAB-2`) |

---

## Acteurs secondaires

### Administrateur tenant

- **Rôle :** Configure l'instance HotOnes de son organisation (services, rôles, taux, calendriers, paramétrage IA).
- **Usage :** Ponctuel — à l'initialisation, puis lors de changements organisationnels.
- **Accès :** Paramétrage tenant complet (`EF-REF-31`), audit des accès sensibles (`HAB-6`).
- **Limite :** Ses droits s'arrêtent au périmètre de son tenant — aucun accès cross-tenant.

### Éditeur (équipe HotOnes)

- **Rôle :** Supervision multi-tenant, support, mise en service de nouveaux tenants.
- **Contrainte :** Accès **exceptionnel, motivé, tracé et notifié** au tenant concerné (`ENF-SEC-8`). Il ne peut pas traverser les tenants via un rôle applicatif — le canal de supervision est distinct.
- **Outillage :** Tableau de bord de supervision (`ENF-SAAS-5`), suivi de consommation IA par tenant.

### Systèmes tiers

| Système | Type d'intégration | Lot |
|---------|-------------------|-----|
| Outil de tâches (Jira, Linear) | Lecture bidirectionnelle (jalons, avancement) — `EF-PRJ-25` | 3 |
| Comptabilité | Export unidirectionnel (factures, éléments de paie) — `EF-FIN-22`, `EF-RH-18` | 2 |
| SIRH | Synchronisation partielle (collaborateurs, absences) | 2-4 |
| Calendrier (Google, Outlook) | Lecture pour le pré-remplissage de temps (consentement) | 2 |
| Messagerie d'équipe (Slack, Teams) | Notifications sortantes | 3 |
| Signature électronique | Statut de signature (devis, contrats) — `EF-CRM-19` | 3 |
| Job boards (LinkedIn, etc.) | Import candidatures — `EF-REC-12` | 4 |
| Fournisseurs d'inférence IA (UE) | Clés fournies par tenant — `ADR-10`, `CTR-5` | 1+ |

> **Principe d'intégration (`INT-1`)** : source de vérité unique par objet. Un objet existe dans un seul système — les autres l'importent ou s'y référencent. Pas de synchronisation bidirectionnelle généralisée.

---

## Matrice de synthèse

| Persona | Volume | Fréquence | Durée | Priorité adoption | Risque de rejet principal |
|---------|--------|-----------|-------|------------------|--------------------------|
| P1 Camille | **80 %** | Quotidienne | 2-5 min | **Critique** | Saisie > 2 min / sentiment de flicage |
| P2 Marc | ~15 % | Quotidienne-hebdo | 20-40 min | Haute | Ressaisie ; chiffres faux |
| P3 Sophie | ~5 % | Hebdomadaire | 1-2 h | Haute | Plan de charge irréaliste ; suggestions inexpliquées |
| P4 Yann | ~5 % | Quotidienne | 15-30 min | Moyenne | Blocage sur affaire ; capacité irréaliste |
| P5 Nadia | ~3 % | Hebdomadaire | 1-3 h | Moyenne | Duplication SIRH ; données d'évaluation accessibles |
| P6 Élodie | ~2 % | Hebdo-mensuelle | 30 min | Haute | Chiffres inexplicables ; écart comptable inexpliqué |

> **Règle de conception prioritaire :** si `P1` rejette l'outil, tous les objectifs `OBJ-1` à `OBJ-6` deviennent inaccessibles. L'adoption de Camille est un prérequis à la valeur de tout le reste.
