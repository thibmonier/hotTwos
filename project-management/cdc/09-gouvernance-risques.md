# 09 — Gouvernance, organisation et risques

---

## 1. Organisation projet

### 1.1 Rôles

| Rôle | Responsabilité | Charge |
|---|---|---|
| **Sponsor** | Arbitre le périmètre, le budget et les points `[ARB]`. Décide des GO/NO-GO de lot. | 2 j/mois |
| **Product owner / AMOA** | Détient le backlog, priorise, spécifie, recette fonctionnellement, anime le pilote. | Temps plein |
| **Responsable technique** | Architecture, invariants du modèle, décisions de reprise de code (`CDR-2`), qualité. | Temps plein |
| **Équipe de réalisation** | 2 à 3 développeurs, 1 designer à temps partiel. | Temps plein |
| **Référent pilote** | Point d'entrée unique dans l'organisation pilote. Organise les tests, remonte les usages réels. | 1 j/semaine |
| **DPO / conseil juridique** | Analyse d'impact, qualification IA, conformité. Externe. | Ponctuel |

**Sur le référent pilote :** ce rôle est systématiquement sous-doté et c'est une erreur. Sans un interlocuteur métier réellement disponible, l'équipe spécifie dans le vide et découvre les écarts à la recette. Une journée par semaine est un plancher, pas une cible.

### 1.2 Instances

| Instance | Fréquence | Participants | Objet |
|---|---|---|---|
| Point produit | Hebdomadaire | PO, resp. technique, équipe | Avancement, arbitrages courants, levée de blocages |
| Comité de pilotage | Mensuel | Sponsor, PO, resp. technique, référent pilote | Avancement du lot, arbitrages `[ARB]`, risques, budget |
| Revue de lot | Fin de lot | Comité élargi | Vérification des critères de sortie, décision GO/NO-GO |
| Atelier métier | Selon besoin | PO + utilisateurs pilotes | Spécification détaillée, tests d'usage |
| Revue de sécurité | À chaque lot touchant habilitations ou IA | Resp. technique, expert externe | `ENF-SEC-4`, `-5`, `-6` |

### 1.3 Règle de décision

| Type de décision | Décideur |
|---|---|
| Priorisation dans un lot | Product owner |
| Modification du périmètre d'un lot | Comité de pilotage |
| Arbitrage `[ARB]` | Sponsor, sur proposition argumentée du PO |
| Architecture et invariants | Responsable technique |
| Reprise de code du MVP | Responsable technique, décision tracée (`CDR-2`) |
| GO/NO-GO de lot | Sponsor, sur la base des critères de sortie |

**Sur le GO/NO-GO :** les critères de sortie de lot du chapitre 08 sont **bloquants**. Un lot qui ne les atteint pas ne passe pas, même sous pression de planning. Le principal service qu'un sponsor peut rendre à ce projet est de tenir cette règle une fois. Elle ne se tiendra plus jamais s'il la lève la première fois.

---

## 2. Méthode

### 2.1 Approche

Développement itératif, par incréments de 2 semaines, avec une démonstration à chaque fin d'itération sur un environnement accessible au référent pilote.

**Pourquoi pas un cycle en V :** le sujet comporte trop d'incertitude d'usage. L'ergonomie de la saisie, la crédibilité du plan de charge et l'utilité des suggestions IA ne se spécifient pas à l'avance, elles se testent.

**Pourquoi ce CDC malgré tout :** l'itératif sans cible fixée dérive. Ce document fixe la destination et les invariants ; le chemin reste itératif. Ce qui est figé : les invariants du modèle (`INV-1` à `INV-8`), les exigences de sécurité et de conformité, les exclusions de périmètre. Ce qui reste ouvert : l'ergonomie, la priorisation fine, la richesse fonctionnelle de chaque exigence.

### 2.2 Recette

| Niveau | Responsable | Objet |
|---|---|---|
| Tests automatisés | Équipe | Règles de gestion critiques, ≥ 80 % (`ENF-MAINT-1`) |
| Recette fonctionnelle | Product owner | Conformité aux critères d'acceptation des exigences |
| Recette d'usage | Référent pilote + utilisateurs | Usage réel en conditions réelles, sur plusieurs semaines |
| Tests de performance | Équipe | `ENF-PERF-1` à `-6`, sur jeu de données de volumétrie cible |
| Tests de sécurité | Expert externe | `ENF-SEC-4`, `-6`, `EF-PIL-19` — obligatoires avant mise en production |
| Tests utilisateurs | Designer + PO | `EF-TMP-3`, `ENF-UX-1`, `-2` |

**La recette d'usage est celle qui compte.** Une recette fonctionnelle passe toujours : on vérifie que le produit fait ce qui est écrit. La recette d'usage vérifie que quelqu'un s'en sert. C'est la seule qui prédit l'adoption.

---

## 3. Registre des risques

Cotation : probabilité (P) et impact (I) de 1 à 5. Criticité = P × I.

| Réf | Risque | P | I | Crit. | Parade |
|---|---|---|---|---|---|
| `RSQ-1` | **La saisie de temps n'est pas adoptée.** Toute la chaîne financière et capacitaire devient fausse. | 4 | 5 | **20** | Budget d'ergonomie prioritaire ; test utilisateur avant développement ; critère de sortie de lot 1 bloquant ; contrepartie visible pour le collaborateur |
| `RSQ-2` | **Fuite de données inter-tenant, notamment via une fonction IA.** Conséquence commerciale irréversible. | 3 | 5 | **15** | `INV-1` porté par le modèle ; filtrage à la source (`ARC-9`) ; test d'intrusion bloquant à chaque lot IA ; ouverture progressive de l'exploration libre (`ARB-17`) |
| `RSQ-3` | **Dérive du périmètre.** L'ambition fonctionnelle croît plus vite que la capacité de livraison. | 4 | 4 | **16** | Exclusions de périmètre écrites et opposables (`00` § 5, `01` § 2.2) ; règle des 60 % de `M` par lot ; toute nouvelle exigence entre par le comité de pilotage |
| `RSQ-4` | **L'audit révèle un existant moins réutilisable qu'espéré.** Le budget du lot 1 augmente. | 3 | 3 | 9 | Audit en lot 0, avant tout engagement ; recommandation du chapitre 07 révisable |
| `RSQ-5` | **Les chiffres financiers ne se réconcilient pas avec la comptabilité.** La direction perd confiance. | 3 | 5 | **15** | `EF-FIN-23` (écran de contrôle des écarts) dès le lot 2 ; `INV-2` et `INV-3` ; traçabilité systématique (`EF-PIL-5`) |
| `RSQ-6` | **Les alertes IA produisent trop de faux positifs.** Elles sont ignorées, puis toutes les alertes le sont. | 4 | 3 | 12 | Seuil de faux positifs mesuré (`EF-PRJ-29`) ; retrait des fonctions sous seuil (`ENF-IA-7`) ; budget de notification quotidien (`EF-PIL-14`) |
| `RSQ-7` | **Le coût d'inférence IA dégrade la marge du modèle SaaS.** | 3 | 3 | 9 | Suivi et plafonnement par tenant (`ENF-IA-5`) ; dégradation gracieuse ; arbitrage économique par fonction |
| `RSQ-8` | **Non-conformité réglementaire sur les usages RH ou le pré-remplissage.** Risque juridique et social. | 3 | 4 | 12 | Analyse d'impact en lot 0 (prérequis bloquant) ; qualification juridique externe ; exclusions `EF-RH-20` et `EF-REC-17` maintenues |
| `RSQ-9` | **L'équipe maintient l'ancien produit en parallèle.** Sa capacité de construction s'effondre. | 4 | 4 | **16** | `CDR-4` : arrêt du MVP à la mise en service du lot 1 ; décision explicite du sponsor, pas un entre-deux |
| `RSQ-10` | **Le référent pilote n'est pas disponible.** L'équipe spécifie sans retour terrain. | 4 | 4 | **16** | Engagement formel de l'organisation pilote avant le lot 1 ; 1 j/semaine minimum ; escalade au sponsor si non tenu |
| `RSQ-11` | **Effet tunnel sur le lot 1.** Aucune valeur livrée avant 8 mois. | 3 | 4 | 12 | `CDR-3` : mise en service pilote à 4-5 mois ; démonstration toutes les 2 semaines |
| `RSQ-12` | **Le resource manager continue d'utiliser son tableur.** Le module de planification est un échec silencieux. | 4 | 3 | 12 | `EF-PLN-2` (capacité nette crédible), `EF-PLN-10` (affectation par profil), `EF-PLN-21` (brouillon/publié) ; critère de sortie de lot 2 vérifié en le demandant directement |
| `RSQ-13` | **Le choix du socle technique devient un projet en soi.** | 3 | 3 | 9 | Décision en lot 0, sur trois critères ordonnés, non rouverte ensuite (`ARB-18`) |
| `RSQ-14` | **Personnalisation excessive.** Le coût de maintenance et de support explose. | 3 | 4 | 12 | Matrice « paramétrable / non paramétrable » arrêtée en lot 0 (`ARB-5`) |
| `RSQ-15` | **Fuite d'état entre requêtes en mode *worker*.** Un service conserve le tenant ou l'utilisateur d'une requête précédente : incident d'isolation, pas simple bogue. | 3 | 5 | **15** | `ARC-47` à `ARC-50` ; parité des environnements (`ARC-86`) ; tests exécutés en configuration *worker* en intégration continue |
| `RSQ-16` | **Dérive de version.** Symfony 8.1 sort de support en janvier 2027 ; les montées sont repoussées et le produit finit en production sur une version non supportée. | 4 | 3 | 12 | `ARC-51` (tolérance zéro aux dépréciations), `ARC-52` (montée planifiée), `ARC-53` (réécriture automatisée) |
| `RSQ-17` | **La construction devient un refuge.** Le développement assisté rend agréable et rapide la production de structure, de couches et de tests. Le produit avance visiblement sans que personne ne l'ait mis devant un utilisateur. | 4 | 4 | **16** | `ARC-100` (dosage par sous-domaine) ; `MD-1` et `MD-2` (tests d'usage) ; indicateur avancé : jours depuis le dernier test auprès d'un utilisateur réel |
| `RSQ-20` | **La capacité de relecture devient le facteur limitant.** Le volume de code produit dépasse ce qui peut être réellement lu ; du code correct et testé fait autre chose que ce qui était voulu. | 4 | 4 | **16** | `ADR-16` — intention rendue machine-vérifiable : `ARC-103` (un test par règle de gestion), `ARC-104` (invariants en base), `ARC-105` (conventions versionnées), `ARC-108` (tests écrits depuis l'exigence) |
| `RSQ-21` | **Une règle d'habilitation générée et non relue.** Une habilitation manquante ne produit aucune erreur : elle produit un accès. | 3 | 5 | **15** | `ARC-106` — le périmètre de sécurité ne délègue pas : écrit à la main, relu ligne à ligne, testé à la main, plus test d'intrusion humain |
| `RSQ-22` | **Divergence silencieuse du modèle analytique.** Les tables de faits s'écartent du modèle transactionnel ; la direction découvre l'écart en comité. | 4 | 4 | **16** | `ARC-112` (reconstruction complète), `ARC-113` (test de non-divergence bloquant en intégration), `ARC-114` (réconciliation en production avec alerte) |
| `RSQ-18` | **Friction d'installation du modèle de clés IA.** Un tenant de 15 personnes sans DSI n'obtient pas de clé, n'active aucune fonction IA, et ne perçoit pas la valeur du produit. | 3 | 3 | 9 | `ARC-80` (produit pleinement utilisable sans IA) ; offre avec inférence incluse (`ARB-24`) ; test commercial sur le pilote (`HYP-16`) |
| `RSQ-19` | **Rupture d'API d'un composant jeune** (Reprise 0.x, Symfony AI). | 3 | 2 | 6 | `ARC-60` (configuration d'assets isolée) ; couche produit tampon (`ARC-73` à `ARC-79`) ; provision du chapitre 12 § 17 |

### Les risques à surveiller en priorité

Criticité 15 ou plus : `RSQ-1` (adoption de la saisie), `RSQ-3` (dérive de périmètre), `RSQ-9` (maintien du MVP en parallèle), `RSQ-10` (indisponibilité du pilote), `RSQ-17` (la construction comme refuge), `RSQ-20` (capacité de relecture saturée), `RSQ-22` (divergence du modèle analytique), `RSQ-2`, `RSQ-15` et `RSQ-21` (fuites de données — applicative, par état résiduel, ou par habilitation non relue).

**Observation :** la majorité de ces risques sont **organisationnels ou comportementaux, pas techniques**. Le développement assisté par agent ne change pas cette proportion — il la renforce. Il retire le facteur limitant qui, auparavant, ralentissait mécaniquement la production et laissait le temps de la relecture. Ce qui protégeait par la lenteur doit désormais protéger par l'outillage (`ADR-16`). C'est conforme à ce qu'on observe sur ce type de projet : la difficulté n'est presque jamais de construire l'outil, elle est de le faire adopter et de tenir le périmètre. Le pilotage doit y consacrer son attention en proportion.

---

## 4. Indicateurs de pilotage du projet

| Indicateur | Fréquence | Seuil d'alerte |
|---|---|---|
| Avancement du lot (exigences `M` livrées et recettées / total) | Bimensuel | Écart > 15 % au plan |
| Nombre d'exigences ajoutées depuis le début du lot | Mensuel | > 10 % du périmètre initial |
| Couverture de tests sur les règles critiques | Continu | < 80 % |
| Taux de saisie complète sur le pilote (dès lot 1) | Hebdomadaire | < 80 % après 6 semaines d'usage |
| Nombre d'arbitrages `[ARB]` en attente | Mensuel | > 3 arbitrages bloquants ouverts |
| Disponibilité effective du référent pilote | Mensuel | < 0,7 j/semaine |
| Consommation budgétaire vs avancement | Mensuel | Écart > 10 points |
| Jours écoulés depuis le dernier test d'usage auprès d'un utilisateur réel | Bimensuel | > 30 jours — indicateur avancé de `RSQ-17` |
| Dépréciations déclenchées par le code applicatif | Continu | > 0 (`ARC-51`) |
| Divergence détectée par la réconciliation analytique | Hebdomadaire | > 0 (`ARC-114`) |
| Règles de gestion `RG-*` sans test nommé | Mensuel | > 0 (`ARC-103`) |

**Sur l'avant-dernier indicateur :** la disponibilité du référent pilote est le meilleur indicateur avancé de l'échec d'un projet de ce type. Quand elle décroche, la dérive fonctionnelle suit deux à trois mois plus tard. Il vaut la peine de le mesurer explicitement plutôt que de le constater après coup.
