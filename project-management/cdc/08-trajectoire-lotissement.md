# 08 — Trajectoire, lotissement et chiffrage

---

## 1. Principe de lotissement

Le lotissement obéit à trois règles, dans cet ordre de priorité :

1. **Chaque lot livre de la valeur utilisable en production.** Pas de lot « technique » sans contrepartie utilisateur — ce sont les premiers à sauter et leur absence se paie plus tard.
2. **La chaîne de la donnée est construite dans le sens du flux.** On ne peut pas piloter des marges sans temps fiables, ni staffer sans capacité, ni recruter sans projection. L'ordre des lots suit cette dépendance.
3. **Le risque est pris tôt.** Les sujets structurants — multi-tenant, invariants du modèle, ergonomie de la saisie — sont dans le lot 1, pas repoussés.

**Conséquence sur l'IA :** elle n'est pas un lot. Elle apparaît dès le lot 1 là où elle réduit la friction de saisie (`EF-TMP-9`), puis progressivement. Les fonctions conversationnelles, les plus démonstratives et les plus risquées, viennent au lot 3, après que le socle de sécurité a été éprouvé.

---

## 2. Lot 0 — Cadrage et fondations *(préalable, 6 à 8 semaines)*

Ce lot ne produit pas de logiciel. Il produit les conditions pour que le reste ne dérive pas.

| Livrable | Contenu | Charge indicative |
|---|---|---|
| Audit de l'existant | `AUD-1` + `AUD-2` — confirme ou infirme la recommandation du chapitre 07 | 5 à 8 j |
| Mesure des situations de référence | `AUD-3` — sans quoi aucun objectif n'est démontrable | 2 j étalés sur 4 semaines |
| Arbitrages bloquants | `ARB-20` en premier (trajectoire de ressource), puis `ARB-5`, `ARB-10`, `ARB-14`, `ARB-18`, `ARB-19`, `ARB-25` | 5 j |
| Analyse d'impact données personnelles | `ENF-RGPD-5` — prérequis à `EF-TMP-10` | Externe, 5 à 8 j |
| Qualification juridique IA | `CTR-3`, `ARB-4`, `ARB-14` — conseil externe | Externe, 3 à 5 j |
| Choix du socle technique | `ARB-18` — trois critères, décision rapide et fermée | 5 j |
| Modèle de données cible | Invariants `INV-1` à `INV-8` posés et revus | 10 j |
| Design system et parcours clés | Notamment le parcours de saisie de temps, testé avant d'être développé | 15 à 20 j |
| Outillage de sécurité automatisé | `ADR-15` — 8 couches, outils libres ou gratuits | 2 à 3 j |
| Chaîne d'intégration continue | `ADR-12` — 11 étapes bloquantes, dont les tests en mode *worker* | 3 à 5 j |
| Modèle dimensionnel conçu et documenté | `ADR-9` — grains, dimensions conformes, historisation, passage au crible de `ARC-71` | 4 à 6 j |
| Classement des modules cœur / support / générique | `ARC-100` — gouverne l'intensité de modélisation de chaque module | 1 j |
| Conventions de développement assisté | `ARC-105` — fichier de conventions versionné : classement, vocabulaire, patrons imposés et interdits | 2 à 3 j |
| Squelette technique validé | `ADR-1`, `ADR-2`, `ADR-8` — couche applicative, mode *worker*, frontières outillées, sur un cas d'usage réel de bout en bout | 8 à 12 j |
| Organisation pilote identifiée | Une agence volontaire, engagée, avec un référent nommé | — |

**Point critique :** le parcours de saisie de temps doit être **testé auprès de vrais collaborateurs avant d'être développé**. C'est le seul écran du produit dont l'ergonomie décide de la réussite globale. Le tester après développement, c'est découvrir trop tard.

---

## 3. Lot 1 — Le cœur : projet, temps, référentiels *(4 à 5 mois)*

**Objectif :** une organisation pilote saisit son temps, suit ses projets, et les chiffres sont justes.

| Module | Périmètre | Exigences |
|---|---|---|
| Référentiels | Organisation, profils, calendriers, compétences (socle), taux et coûts historisés, rôles et habilitations, paramétrage tenant | `EF-REF-1` à `-11`, `-15`, `-16`, `-19`, `-20`, `-22` à `-26`, `-29` à `-31`, `-33` |
| Projets | Structure, lots, jalons, budget, avancement, reste à faire, détection de dérive, équipe, clôture | `EF-PRJ-1` à `-5`, `-8` à `-16`, `-19`, `-20`, `-22` |
| Temps | Saisie, pré-remplissage depuis le plan, absences, validation, clôture, restitution collaborateur | `EF-TMP-1` à `-6`, `-9`, `-11`, `-14` à `-16`, `-20` à `-24`, `-26`, `-27`, `-29` |
| Socle | Multi-tenant, habilitations, journalisation, moteur de calcul unique, couche IA | `ENF-SEC-1`, `-3` à `-5`, `-7` ; `ARC-1` à `-3`, `-5` à `-8` |
| Analytique | Dimensions, `D_Temps`, projections, commande de reconstruction, test de non-divergence, faits `F_Imputation`, `F_Capacite`, `F_AvancementProjet` | `ADR-9` ; `ARC-111` à `-119` |

**Critères de sortie de lot — bloquants :**

- `EF-TMP-3` : saisie hebdomadaire en moins de 2 minutes, mesurée en test utilisateur.
- `ENF-SEC-4` : isolation inter-tenant validée par test d'intrusion.
- `ENF-MAINT-1` : couverture de tests ≥ 80 % sur valorisation, capacité et habilitations.
- `ARC-113` : test de non-divergence du modèle analytique, vert en intégration continue.
- `ARC-103` : chaque règle de gestion `RG-*` du périmètre du lot est couverte par un test nommé d'après elle.
- `ARC-50` : jeu de tests exécuté en configuration *worker*, vert.
- Taux de saisie complète à J+2 ≥ 80 % sur le pilote après 6 semaines d'usage.

**Le dernier critère est le plus important.** Il mesure l'adoption réelle, pas la conformité fonctionnelle. S'il n'est pas atteint, l'engagement du lot 2 doit être suspendu : construire de la finance sur des temps non saisis ne produit que des chiffres faux affichés avec autorité.

---

## 4. Lot 2 — Capacité et argent *(3 à 4 mois)*

**Objectif :** le resource manager abandonne son tableur, la direction voit ses marges.

| Module | Périmètre | Exigences |
|---|---|---|
| Planification | Capacité nette, plan de charge, affectations, affectation par profil, publication, simulation | `EF-PLN-1` à `-4`, `-6` à `-14`, `-16`, `-19` à `-21` |
| Finance | Valorisation, marge, atterrissage, facturation, encaissement, tableau de bord financier, export comptable | `EF-FIN-1` à `-3`, `-6` à `-8`, `-11` à `-17`, `-20`, `-22`, `-23` |
| Référentiels | Conditions commerciales, charges indirectes, exercices et clôture | `EF-REF-17`, `-21`, `-23` |
| IA | Pré-remplissage enrichi par signaux consentis, suggestions d'affectation explicables | `EF-TMP-10`, `-12` ; `EF-PLN-23`, `-24` |
| Intégrations | SSO, agenda, comptabilité | — |

**Critères de sortie — bloquants :**

- Écart entre marge HotOnes et marge comptable expliqué à 100 % sur un exercice (`EF-FIN-23`).
- Le resource manager du pilote n'utilise plus de tableur de staffing en parallèle. **Ce critère se vérifie en le lui demandant, franchement.**
- `EF-PLN-2` : la capacité nette affichée est jugée conforme à la réalité par le resource manager.

**Prérequis bloquant du lot 2 :** l'analyse d'impact (`ENF-RGPD-5`) et l'arbitrage `ARB-10` doivent être clos avant le développement de `EF-TMP-10`.

---

## 5. Lot 3 — Amont commercial et pilotage *(3 à 4 mois)*

**Objectif :** la boucle est fermée entre la vente et la production ; les décideurs disposent de leurs tableaux de bord.

| Module | Périmètre | Exigences |
|---|---|---|
| CRM | Opportunités, chiffrage, devis, marge prévisionnelle, contrôle capacitaire, bascule en projet, avenants | `EF-CRM-1` à `-5`, `-7` à `-13`, `-15` à `-17`, `-19` à `-22` |
| Pilotage | Tableaux de bord par persona, rapports standards, exploration, alertes, notifications agrégées | `EF-PIL-1` à `-3`, `-5` à `-10`, `-13` à `-17` |
| Projets | Intégration outil de tâches, bilan de projet, base d'historique | `EF-PRJ-23` à `-25` |
| IA | Synthèses projet et financière, détection de signaux faibles, estimation par historique, exploration en langage naturel **bornée** | `EF-PRJ-28`, `-29` ; `EF-FIN-25` ; `EF-CRM-23`, `-24` ; `EF-PIL-18` à `-20` |
| Intégrations | Signature, CRM tiers, messagerie d'équipe | — |

**Critères de sortie — bloquants :**

- `EF-PIL-19` : test d'intrusion sur l'assistant en langage naturel, incluant injection de consigne et extraction par recoupement. **Aucune mise en production sans ce test.**
- `EF-CRM-20` : bascule devis → projet sans aucune ressaisie.
- `EF-PIL-14` : aucun utilisateur ne reçoit plus d'une notification agrégée par jour hors criticité.

**Rappel de l'arbitrage `ARB-17` :** ouvrir l'exploration en langage naturel de manière bornée (questions pré-outillées) avant d'ouvrir l'exploration libre. Le faire dans l'ordre inverse, c'est prendre le risque d'un incident de sécurité sur le sujet le plus visible du produit.

---

## 6. Lot 4 — RH, compétences et recrutement *(3 à 4 mois)*

**Objectif :** la boucle capacité → compétence → recrutement est fermée.

| Module | Périmètre | Exigences |
|---|---|---|
| RH | Dossier collaborateur, compétences validées, cartographie, entretiens, souhaits, intégration et sortie | `EF-RH-1` à `-3`, `-6` à `-10`, `-13` à `-18`, `-21`, `-24` à `-26` |
| Recrutement | Détection de besoin, instruction des options, plan de recrutement, postes, candidatures, intégration | `EF-REC-1` à `-9`, `-12`, `-13`, `-16`, `-18` à `-22` |
| Planification | Compétences dans le staffing, détection de tension, surcharge répétée | `EF-PLN-13` à `-15`, `-25`, `-26` |
| IA | Structuration de compétences, extraction de CV, synthèse d'entretien — **jamais d'évaluation** | `EF-RH-11`, `-12`, `-19` ; `EF-REC-14`, `-15` |

**Prérequis bloquants du lot 4 :** `ARB-14` (frontière aide à la décision / profilage) et `ARB-4` (position sur l'IA en recrutement) doivent être tranchés et documentés avant conception. La qualification juridique du lot 0 en est le support.

**Critères de sortie :**

- Aucune fonction produisant un score, un classement ou une recommandation d'écartement de personne (`RG-RH-4`, `RG-REC-2`).
- Traçabilité complète des accès aux données RH sensibles (`HAB-6`).
- La détection de tension capacitaire produit des besoins qualifiés à un horizon supérieur au délai de recrutement (`RG-MP6-2`).

---

## 7. Lot 5 — Industrialisation SaaS *(2 à 3 mois, partiellement parallélisable)*

**Objectif :** le produit se vend et s'installe sans intervention de l'éditeur.

| Périmètre | Exigences |
|---|---|
| Onboarding self-service, import initial, assistant de configuration | `EF-REF-29`, `-34` ; `REP-3`, `REP-4` |
| Gestion des abonnements et facturation éditeur | `[à préciser]` — dépend du modèle tarifaire, hors périmètre de ce CDC |
| Supervision multi-tenant, suivi de consommation IA | `ENF-SAAS-5`, `ENF-IA-5` |
| Réversibilité et export complet | `ENF-RGPD-9` |
| Documentation, base de connaissances, support | `ENF-SUP-1` à `-3` |
| Accessibilité et internationalisation | `ENF-UX-4`, `-5` |

**Prérequis :** le modèle tarifaire doit être arrêté avant ce lot. Il est hors périmètre de ce CDC (cf. `00`, § 5).

---

## 8. Planning indicatif

```
        M1   M3   M5   M7   M9   M11  M13  M15  M17  M19
Lot 0   ████
Lot 1        ████████████
Lot 2                    ██████████
Lot 3                              ██████████
Lot 4                                        ██████████
Lot 5                                             ████████
                ▲            ▲          ▲         ▲
              Pilote      Pilote     Pilote    1ers clients
              L1          L2         L3        externes
```

**Durée totale indicative : 18 à 22 mois** jusqu'à un produit commercialisable, avec une mise en service pilote dès le mois 6.

Les lots 4 et 5 se recouvrent partiellement. Les lots 1 à 3 ne doivent pas être parallélisés : ils sont en dépendance de données stricte.

---

## 9. Chiffrage indicatif

**Avertissement.** Les charges ci-dessous sont des ordres de grandeur établis à partir du volume d'exigences et de l'expérience de projets comparables. Elles ne constituent **ni un devis, ni un engagement**. Un chiffrage opposable exige une conception détaillée, une équipe identifiée et un socle technique arrêté. **À affiner en lot 0, et à ne pas utiliser comme référence contractuelle.**

| Lot | Charge indicative (j·h) | Fourchette |
|---|---|---|
| Lot 0 — Cadrage et fondations | 85 | 60 – 115 |
| Lot 1 — Cœur | 275 | 215 – 360 |
| Lot 2 — Capacité et finance | 200 | 160 – 260 |
| Lot 3 — Commercial et pilotage | 200 | 160 – 260 |
| Lot 4 — RH et recrutement | 180 | 140 – 240 |
| Lot 5 — Industrialisation SaaS | 120 | 90 – 170 |
| **Total** | **1 075** | **840 – 1 430** |

**Non inclus dans ces chiffres :** design et UX (compter 15 à 20 % en sus), conseil juridique externe, hébergement, effort de l'organisation pilote, commercialisation.

**À ajouter depuis la rédaction du chapitre 12 :**

- **15 à 25 j** de provision pour la mise au point de la stack retenue (mode *worker*, composants jeunes) — chapitre 12 § 17.
- **12 à 18 j** de surcoût du schéma en étoile physique par rapport à une approche en vues matérialisées (`ADR-9`), intégrés aux lots 0 et 1 ci-dessus.
- **2 à 3 j** de mise en place de l'outillage de sécurité automatisé (`ADR-15`), à placer en lot 0.
- **1 à 3 j × 2 par an** de montée de version Symfony (`ARC-52`), en charge récurrente et non en charge projet.

**En revanche, le coût d'inférence IA sort du budget de l'éditeur** : les clés sont fournies par les tenants (`ADR-10`). Il faudra le réintroduire si l'offre avec inférence incluse (`ARB-24`) est retenue.

**Équipe cible :** 1 produit/AMOA, 1 responsable technique, 2 à 3 développeurs, 1 designer à temps partiel. Soit environ **4,5 ETP sur 18 à 22 mois**.

**Effet du développement assisté par agent** (`ADR-16`) : il porte sur les 55 à 60 % de la charge qui relèvent de l'écriture de code, pas sur la décision produit, le design, les tests d'usage, l'exploitation, la conformité ni le support. Effet global estimé : **facteur 1,4 à 1,6**. Il ne dispense pas de l'arbitrage `ARB-20` — il en déplace le seuil.

**Sur la fourchette :** l'écart entre les bornes basse et haute (× 1,7) reflète l'incertitude réelle à ce stade, principalement portée par trois inconnues : le résultat de l'audit de l'existant, la profondeur de personnalisation retenue (`ARB-5`), et l'ambition finale des fonctions IA. Annoncer une valeur unique à ce stade serait une fausse précision.

---

## 10. Ce qui peut être retiré si le budget se contraint

Dans l'ordre où l'AMOA recommande de couper, du moins douloureux au plus :

1. **Lot 4 en totalité.** La boucle RH/recrutement est la plus différenciante mais la moins urgente. Un tableur tient encore. Économie : ~180 j.
2. **Les fonctions IA de priorité `C`** de tous les modules. Économie : ~80 j, sans perte de valeur cœur.
3. **Le module CRM (lot 3)**, en se limitant à une intégration avec le CRM du client. Économie : ~90 j — voir `ARB-6`, qui recommande de toute façon cette option.
4. **La personnalisation avancée** (`EF-REF-27`, `-28`, `EF-PIL-8`). Économie : ~60 j, au prix d'une adaptabilité moindre en phase commerciale.

**Ce qui ne peut jamais être coupé, quel que soit le budget :**

- L'ergonomie de la saisie de temps (`EF-TMP-3`, `ENF-UX-1`) — sans elle, rien ne fonctionne.
- L'isolation multi-tenant et le filtrage IA à la source (`ENF-SEC-4`, `ENF-SEC-6`) — un incident ici est fatal commercialement.
- Les invariants du modèle de données (`INV-1` à `INV-8`) — non rétro-adaptables.
- La traçabilité des indicateurs (`EF-PIL-5`) — sans elle, les chiffres ne seront pas utilisés.

Couper dans ces quatre postes ne fait pas économiser du budget : cela le déplace vers une réécriture ultérieure, avec intérêts.
