# 03 — Processus cibles

Ce chapitre décrit les six macro-processus que HotOnes doit outiller. Chacun est décrit par son déclencheur, ses étapes, ses acteurs, ses règles de gestion structurantes et ce qui change par rapport à une organisation non outillée.

---

## MP1 — De l'opportunité au projet

**Déclencheur :** une demande client entrante ou une action commerciale sortante.
**Fin :** projet créé, budget engagé, équipe pré-affectée.

| # | Étape | Acteur | Sortie |
|---|---|---|---|
| 1 | Qualification de l'opportunité | Commercial | Opportunité avec montant estimé, probabilité, date de démarrage souhaitée |
| 2 | Chiffrage | Chef de projet + Commercial | Décomposition en lots, charge par profil, prix |
| 3 | **Contrôle de faisabilité capacitaire** | Resource manager | Avis : capacité disponible / tension / impossible à la date visée |
| 4 | Émission du devis | Commercial | Devis versionné, envoyé, tracé |
| 5 | Négociation et révisions | Commercial | Versions successives, historique conservé |
| 6 | Signature | Commercial + Direction | Devis accepté |
| 7 | **Bascule en projet** | Automatique | Projet créé avec lots, budget, jalons issus du devis |
| 8 | Affectation définitive | Resource manager | Plan de charge mis à jour |

**Règles structurantes :**

- `RG-MP1-1` — Un devis ne peut être émis sans décomposition en lots avec charge par profil. C'est ce qui permet la pondération du plan de charge et la comparaison ultérieure vendu/réalisé.
- `RG-MP1-2` — L'étape 3 est **bloquante en avertissement, pas en interdiction** : le commercial peut passer outre l'avis capacitaire, mais l'arbitrage est tracé et remonte à la direction. Un outil qui bloque une vente perd la confiance du commerce.
- `RG-MP1-3` — La bascule devis → projet est automatique et **sans ressaisie**. Toute ressaisie à ce stade est un défaut de conception.
- `RG-MP1-4` — Une opportunité pondérée alimente le plan de charge prévisionnel dès qu'elle atteint un seuil de probabilité paramétrable (défaut : 60 %), en charge « probable » distincte de la charge « ferme ».

**Ce qui change :** aujourd'hui, la capacité n'est consultée qu'après la signature. Le processus cible la consulte avant l'engagement, ce qui est le seul moyen de tenir les dates de démarrage.

---

## MP2 — Du projet à la livraison

**Déclencheur :** création du projet.
**Fin :** clôture opérationnelle.

| # | Étape | Acteur | Sortie |
|---|---|---|---|
| 1 | Cadrage et structuration | Chef de projet | Lots, jalons, budget par lot, équipe |
| 2 | Lancement | Chef de projet | Projet actif, imputations ouvertes |
| 3 | Production et imputation | Collaborateurs | Temps imputés par lot |
| 4 | **Suivi de l'avancement** | Chef de projet | Avancement physique déclaré par lot, reste à faire réestimé |
| 5 | **Détection de dérive** | Système | Alerte si écart significatif entre avancement et consommation |
| 6 | Ajustement | Chef de projet + Direction | Avenant, réaffectation, ou acceptation de la perte |
| 7 | Livraison et recette | Chef de projet | Jalons validés |
| 8 | Clôture opérationnelle | Chef de projet | Projet fermé aux imputations, bilan produit |

**Règles structurantes :**

- `RG-MP2-1` — L'avancement est **déclaré, pas déduit de la consommation**. Déduire l'avancement du budget consommé rend la détection de dérive mathématiquement impossible. C'est l'erreur de conception la plus fréquente et la plus coûteuse.
- `RG-MP2-2` — Le **reste à faire** est réestimé au minimum à chaque jalon et à chaque période de clôture. Le budget restant n'est pas le reste à faire.
- `RG-MP2-3` — Une alerte de dérive se déclenche quand `consommation en % du budget − avancement déclaré en % > seuil` (défaut : 15 points), et non sur la seule consommation.
- `RG-MP2-4` — La clôture opérationnelle ferme les imputations mais **pas** le suivi financier, qui reste ouvert jusqu'à l'encaissement.

---

## MP3 — Planification et staffing

**Déclencheur :** hebdomadaire (rituel), ou événement (nouvelle affaire, départ, absence longue).
**Fin :** plan de charge arbitré et publié.

| # | Étape | Acteur | Sortie |
|---|---|---|---|
| 1 | Consolidation de la demande | Système | Charge ferme (projets) + probable (opportunités pondérées) |
| 2 | Consolidation de la capacité | Système | Capacité nette : effectif × temps disponible − absences − tâches internes |
| 3 | **Identification des tensions** | Système | Sur-charge, sous-charge, manque de compétence, par semaine et par profil |
| 4 | Proposition d'affectation | Système (IA) + Resource manager | Scénarios d'affectation avec justification |
| 5 | Arbitrage | Resource manager | Affectations retenues |
| 6 | Publication | Resource manager | Planning visible des collaborateurs et chefs de projet |
| 7 | **Escalade des tensions non résolues** | Système | Besoin de renfort : recrutement, sous-traitance, ou décalage commercial |

**Règles structurantes :**

- `RG-MP3-1` — La capacité est calculée **nette**, jamais brute. Un ETP ne vaut pas 5 jours facturables par semaine. Le taux d'occupation cible est paramétrable par profil (défaut : 75 à 85 % selon rôle).
- `RG-MP3-2` — La charge probable et la charge ferme sont **toujours affichées séparément**. Les agréger produit un plan de charge que personne ne croit.
- `RG-MP3-3` — Une affectation dépassant la capacité d'un collaborateur est autorisée mais signalée visuellement et tracée. Le blocage dur est contre-productif : il pousse à contourner l'outil.
- `RG-MP3-4` — Toute proposition IA d'affectation expose ses critères (disponibilité, adéquation compétence, coût, continuité sur le projet, souhait exprimé du collaborateur) et leur pondération.
- `RG-MP3-5` — L'escalade de l'étape 7 alimente directement le processus MP6. C'est le lien qui manque dans la plupart des organisations.

---

## MP4 — Saisie et validation des temps

**Déclencheur :** quotidien.
**Fin :** période clôturée et verrouillée.

| # | Étape | Acteur | Sortie |
|---|---|---|---|
| 1 | **Pré-remplissage** | Système (IA) | Proposition d'imputation basée sur les affectations, le calendrier et les signaux d'activité connectés |
| 2 | Confirmation ou correction | Collaborateur | Temps imputés |
| 3 | Relance | Système | Notification aux retardataires selon une politique paramétrable |
| 4 | Validation | Chef de projet / manager | Temps validés ou renvoyés |
| 5 | Clôture de période | Administrateur / RH | Période verrouillée |
| 6 | Alimentation aval | Système | Coûts projet, taux d'occupation, éléments variables de paie |

**Règles structurantes :**

- `RG-MP4-1` — Le pré-remplissage est une **proposition, jamais un enregistrement automatique**. Le collaborateur confirme. Une saisie automatique non confirmée n'a aucune valeur probante et sera juridiquement et socialement contestée.
- `RG-MP4-2` — L'objectif de conception est **moins de deux minutes par jour** pour un cas nominal. C'est un critère d'acceptation, pas une intention (cf. `EF-TMP-3`).
- `RG-MP4-3` — La modification d'un temps validé exige une justification tracée. La modification d'un temps sur période clôturée est impossible sans réouverture formelle.
- `RG-MP4-4` — La granularité minimale de saisie est le **demi-quart de journée** ou l'heure, au choix du tenant. La saisie à la minute est un anti-pattern : elle produit une fausse précision et détruit l'adoption.
- `RG-MP4-5` — Les signaux d'activité utilisés pour le pré-remplissage (calendrier, outil de tâches, dépôts de code) sont **opt-in explicite par collaborateur**, révocable, et leur nature est affichée. Sans cela, le dispositif est un outil de surveillance et sera traité comme tel.

`RG-MP4-5` n'est pas une précaution rédactionnelle. C'est la condition pour que le module soit acceptable en CSE et conforme au RGPD. À faire valider avant conception.

---

## MP5 — Pilotage financier

**Déclencheur :** continu, avec un rituel de clôture mensuelle.
**Fin :** résultat consolidé.

| # | Étape | Acteur | Sortie |
|---|---|---|---|
| 1 | Valorisation des temps | Système | Coût de production par projet |
| 2 | Calcul de la marge à date | Système | Marge brute par projet, par client, par pôle |
| 3 | **Calcul de l'atterrissage** | Système | Marge projetée à la clôture = f(réalisé, reste à faire, engagements) |
| 4 | Déclenchement de la facturation | Chef de projet / Direction | Facture selon l'échéancier ou l'avancement |
| 5 | Suivi de l'encaissement | Direction | Retards de paiement, relances |
| 6 | Clôture mensuelle | Direction | Consolidé : CA, marge, taux d'occupation, backlog |
| 7 | Export comptable | Système | Écritures vers l'outil comptable |

**Règles structurantes :**

- `RG-MP5-1` — Le coût de production utilise un **coût de revient chargé** par profil ou par personne, paramétrable, incluant les charges indirectes selon une clé définie par le tenant.
- `RG-MP5-2` — L'atterrissage est recalculé à chaque modification du reste à faire, et son historique est conservé. Voir *comment* la projection a évolué est plus informatif que sa valeur du jour.
- `RG-MP5-3` — Tout indicateur financier doit être **traçable jusqu'à la ligne de temps ou la ligne de facture** qui le compose. Un chiffre non traçable ne sera pas utilisé en CODIR.
- `RG-MP5-4` — Le calcul de marge distingue explicitement : marge brute (CA − coût direct de production), marge après charges indirectes, et marge encaissée. Confondre les trois est la source principale des désaccords entre production et direction.

---

## MP6 — Du besoin de compétence au recrutement

**Déclencheur :** tension capacitaire détectée (MP3, étape 7), départ, ou décision stratégique.
**Fin :** collaborateur intégré et opérationnel.

| # | Étape | Acteur | Sortie |
|---|---|---|---|
| 1 | **Détection du besoin** | Système + Resource manager | Tension qualifiée : profil, compétences, volume, échéance |
| 2 | Instruction des options | Direction | Recrutement / sous-traitance / montée en compétence interne / décalage commercial |
| 3 | Validation du besoin | Direction | Besoin de recrutement validé et budgété |
| 4 | Ouverture du poste | RH | Fiche de poste, canaux de diffusion |
| 5 | Sourcing et candidatures | RH | Vivier de candidatures |
| 6 | Évaluation | RH + opérationnels | Comptes rendus d'entretien structurés |
| 7 | Décision et proposition | Direction + RH | Offre |
| 8 | Intégration | RH + Manager | Parcours d'onboarding, affectation initiale |
| 9 | **Boucle de retour** | Système | Capacité mise à jour, plan de charge recalculé |

**Règles structurantes :**

- `RG-MP6-1` — L'étape 2 est obligatoire. Un outil qui transforme automatiquement une tension en ouverture de poste produit du sureffectif. Les quatre options doivent être présentées et l'arbitrage tracé.
- `RG-MP6-2` — La détection de besoin s'appuie sur une projection à **horizon supérieur au délai de recrutement** du profil concerné (paramétrable, défaut : 4 mois). Détecter un besoin à 6 semaines n'a aucune utilité opérationnelle.
- `RG-MP6-3` — Toute assistance IA à l'évaluation de candidatures est **strictement limitée à la structuration et à l'extraction d'information** (parsing de CV, synthèse de compte rendu). Aucun scoring, classement ou recommandation d'écartement de candidat. `[ARB-4]`
- `RG-MP6-4` — Les données de candidature ont une durée de conservation limitée et un mécanisme de purge automatique (cf. `EF-REC-20`).

**Réserve sur `RG-MP6-3` :** cette restriction est délibérément conservatrice. Le tri automatisé de candidatures est un cas d'usage explicitement encadré par la réglementation européenne sur l'IA et expose à un risque juridique et réputationnel disproportionné par rapport au gain. Si le sponsor souhaite lever cette restriction, cela doit être un arbitrage explicite, documenté, et validé juridiquement — pas une dérive de conception.
