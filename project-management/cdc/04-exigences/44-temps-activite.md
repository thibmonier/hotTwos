# 04.4 — Temps et activité (`TMP`)

**Lot cible :** 1 — **Personas concernés :** collaborateur (P1), chef de projet (P2), RH (P5)

## Avertissement de conception

Ce module est le **point de défaillance unique** du système. Toute la chaîne financière et capacitaire repose sur la fiabilité des temps saisis, et cette fiabilité dépend d'une seule variable : l'acceptabilité de la saisie par 80 % des utilisateurs de la plateforme, pour qui elle n'apporte aucun bénéfice direct.

Trois conséquences pour la conception :

1. **Le budget d'ergonomie de ce module doit être le plus élevé du projet**, devant les modules à plus forte valeur perçue par la direction.
2. **Le critère d'acceptation dominant est le temps de saisie**, pas la richesse fonctionnelle.
3. **La contrepartie doit être visible pour le collaborateur.** Une saisie qui ne lui rend rien est une taxe. Elle doit lui donner accès à son planning, ses compteurs de congés, sa charge à venir.

---

## 1. Saisie

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-TMP-1` | M | Le système doit permettre à un collaborateur d'imputer son temps sur les projets et lots auxquels il est affecté, à la maille paramétrée par le tenant (défaut : demi-journée). | La saisie d'une journée type (2 projets) est réalisable en moins de 30 secondes. |
| `EF-TMP-2` | M | Le système doit proposer une vue de saisie hebdomadaire ainsi qu'une saisie quotidienne rapide. | Les deux modes coexistent et manipulent les mêmes données. |
| `EF-TMP-3` | M | Le temps de saisie d'une semaine nominale (3 à 5 projets, quelques absences) ne doit pas excéder **2 minutes** pour un utilisateur formé. | Mesuré en test utilisateur sur 5 collaborateurs représentatifs. **Critère bloquant de recette du lot 1.** |
| `EF-TMP-4` | M | Le système doit permettre de saisir un commentaire optionnel par imputation. | Le commentaire est reporté dans les exports et les analyses. |
| `EF-TMP-5` | S | Le système doit permettre de dupliquer la saisie d'une journée ou d'une semaine précédente. | — |
| `EF-TMP-6` | S | Le système doit être utilisable en saisie sur mobile. | La saisie quotidienne est réalisable intégralement sur un écran de téléphone. |
| `EF-TMP-7` | S | Le système doit permettre la saisie en mode dégradé hors connexion, avec synchronisation ultérieure. | Les saisies hors ligne sont conservées et synchronisées sans doublon. |
| `EF-TMP-8` | C | Le système doit proposer une saisie par démarrage/arrêt de minuteur, pour les usages qui le préfèrent. | Optionnel par collaborateur. |

## 2. Pré-remplissage assisté

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-TMP-9` | M | **[IA]** Le système doit **pré-remplir** la saisie à partir des affectations planifiées du collaborateur. | La saisie d'une semaine conforme au plan se réduit à une confirmation en un geste. |
| `EF-TMP-10` | S | **[IA]** Le système doit enrichir le pré-remplissage à partir de signaux d'activité connectés et **explicitement autorisés par le collaborateur** : agenda, outil de tâches, dépôts de code. | Chaque source est activable et révocable individuellement par le collaborateur ; la liste des sources utilisées est affichée à côté de la proposition. |
| `EF-TMP-11` | M | Aucune imputation ne doit être enregistrée sans confirmation explicite du collaborateur. | Une proposition non confirmée reste à l'état de proposition et n'alimente aucun calcul. |
| `EF-TMP-12` | S | **[IA]** Le système doit apprendre des corrections apportées par le collaborateur pour améliorer ses propositions ultérieures. | Le taux d'acceptation sans correction est mesuré et suivi ; il doit progresser sur les 8 premières semaines d'usage. |
| `EF-TMP-13` | C | **[IA]** Le système doit permettre une saisie en langage naturel (« lundi j'étais sur le projet Dupont toute la journée, mardi matin réunion interne ») convertie en imputations structurées soumises à confirmation. | La conversion est présentée sous forme structurée avant enregistrement. |

**Sur `EF-TMP-10` et `EF-TMP-11` — non négociable :** ces deux exigences forment le dispositif de protection du collaborateur. Elles rendent le module défendable en instance représentative du personnel et conforme au RGPD. Les affaiblir — activation par défaut, enregistrement automatique — transforme l'outil en dispositif de surveillance et expose le tenant. À faire valider par un conseil en droit social avant conception. `[ARB-10]`

## 3. Absences

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-TMP-14` | M | Le système doit permettre de déclarer une absence (congé, RTT, arrêt, formation) avec son type, ses dates et sa demi-journée de début et de fin. | — |
| `EF-TMP-15` | M | Le système doit soumettre les demandes d'absence au circuit de validation paramétré et notifier le demandeur du résultat. | — |
| `EF-TMP-16` | M | Le système doit tenir des **compteurs** par type d'absence (acquis, pris, en attente, solde) et les rendre visibles au collaborateur. | Les compteurs sont exacts à la date du jour et projetés à la fin de la période de référence. |
| `EF-TMP-17` | S | Le système doit signaler au valideur les conflits d'absence au sein d'une équipe ou sur un projet critique. | Une demande créant un trou de compétence sur un projet actif est signalée. |
| `EF-TMP-18` | S | Le système doit gérer les règles d'acquisition et de report paramétrables par type d'absence et par tenant. | Les règles françaises usuelles (CP, RTT) sont fournies par défaut. |
| `EF-TMP-19` | C | Le système doit présenter un calendrier d'équipe des absences validées. | Aucun motif d'absence n'y est visible, uniquement le type et les dates. |

## 4. Validation et clôture

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-TMP-20` | M | Le système doit permettre au valideur (chef de projet ou manager selon paramétrage) de valider ou refuser les temps par lot de saisies, avec motif en cas de refus. | La validation d'une semaine d'équipe est réalisable en moins de 5 minutes pour 10 collaborateurs. |
| `EF-TMP-21` | M | Le système doit relancer automatiquement les collaborateurs en retard de saisie selon une politique paramétrable (délai, canal, escalade). | Les relances sont paramétrables et désactivables ; leur fréquence maximale est bornée. |
| `EF-TMP-22` | M | Le système doit permettre la clôture d'une période, verrouillant les saisies et déclenchant les calculs aval. | Après clôture, aucune modification n'est possible sans réouverture tracée par un rôle habilité. |
| `EF-TMP-23` | S | Le système doit tracer toute modification d'un temps déjà validé, avec auteur, date, valeur avant/après et motif. | La piste d'audit est consultable et exportable. |
| `EF-TMP-24` | S | Le système doit présenter au valideur un tableau de bord de complétude de saisie de son équipe. | Le taux de complétude par collaborateur et par semaine est visible en une vue. |
| `EF-TMP-25` | C | **[IA]** Le système doit signaler au valideur les saisies **atypiques** (volume anormal, imputation sur un projet non affecté, écart fort au plan) pour orienter son contrôle. | L'anomalie est décrite factuellement, sans qualification de comportement. La détection porte sur la donnée, jamais sur la personne. |

**Sur `EF-TMP-25` :** formulation volontairement contrainte. « Cette imputation s'écarte de 3 jours du plan » est une information. « Ce collaborateur semble peu fiable dans ses déclarations » est un profilage. La frontière doit être tenue dans la conception, pas seulement dans le libellé.

## 5. Restitution au collaborateur

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-TMP-26` | S | Le système doit présenter à chaque collaborateur une synthèse de son activité : répartition par projet et par type, sur la période de son choix. | Accessible en un clic depuis l'écran de saisie. |
| `EF-TMP-27` | S | Le système doit présenter au collaborateur son planning à venir depuis le même écran que sa saisie. | La contrepartie de la saisie est immédiatement visible. |
| `EF-TMP-28` | C | Le système doit permettre au collaborateur d'exporter son relevé d'activité individuel. | — |

## 6. Alimentation aval

| Réf | Prio | Exigence | Critère d'acceptation |
|---|---|---|---|
| `EF-TMP-29` | M | Les temps validés doivent alimenter automatiquement la valorisation des coûts projet, le calcul de marge et le taux d'occupation. | Les indicateurs sont à jour dans un délai inférieur à 15 minutes après validation. |
| `EF-TMP-30` | S | Le système doit produire les éléments variables destinés à la paie (absences, heures particulières) dans un format exportable. | Le format d'export est paramétrable et compatible avec les principaux logiciels de paie du marché. `[à préciser]` |
| `EF-TMP-31` | S | Le système doit permettre l'extraction des temps par projet pour justification client en régie. | L'extraction est produisible au format PDF et tableur, filtrée par période et par projet. |
| `EF-TMP-32` | C | Le système doit produire les données nécessaires à un dossier de Crédit d'Impôt Recherche ou d'Innovation. | La ventilation par projet éligible et par collaborateur est extractible. `[à préciser]` |

## 7. Règles de gestion du module

| Réf | Règle |
|---|---|
| `RG-TMP-1` | Un temps ne peut être imputé que sur un projet ouvert aux imputations et auquel le collaborateur est affecté (sauf ouverture exceptionnelle tracée). |
| `RG-TMP-2` | Le total imputé sur une journée ne peut dépasser un plafond paramétrable, sauf justification explicite. |
| `RG-TMP-3` | Une absence validée bloque l'imputation de temps de production sur la période concernée. |
| `RG-TMP-4` | Une proposition de pré-remplissage n'a aucune valeur tant qu'elle n'est pas confirmée. |
| `RG-TMP-5` | Toute donnée d'activité issue d'une source externe est traitée comme un signal de suggestion et n'est jamais conservée au-delà de la durée nécessaire à la proposition. |
| `RG-TMP-6` | La modification d'une période clôturée exige une réouverture formelle, tracée, réservée à un rôle habilité. |

## 8. Points ouverts

- `[ARB-10]` — Cadre juridique et social du pré-remplissage assisté par signaux d'activité. **Prérequis bloquant** à la conception de `EF-TMP-10`. À instruire avec un conseil en droit social et le DPO.
- `[ARB-11]` — Politique de relance : une relance trop insistante détruit l'adhésion, une relance trop faible détruit la donnée. Le paramétrage par défaut doit être arbitré et testé sur le pilote.
- `[à préciser]` — Formats d'export paie et CIR : dépendent des outils cibles des premiers clients. À spécifier au lot 2.
