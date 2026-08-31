# Definition of Done — HotOnes

**Projet :** HotOnes — ERP de gestion d'agence digitale / ESN
**Date :** 2026-08-31
**Source :** `.claude/rules/` (TDD, couverture, PHPStan, Clean Archi), CDC (ENF-SEC, ENF-IA, ENF-UX, ENF-RGPD, ENF-MAINT), `ADR-12`, `ARC-103..108`

---

## Préambule

La DoD est un contrat d'équipe : une fonctionnalité ou un sprint n'est « fini » que si **tous** les critères de la liste sont verts. Elle n'est pas une aspiration — c'est un seuil de sortie. Tout critère non rempli génère une dette identifiée, planifiée et acceptée explicitement.

La DoD du projet HotOnes intègre trois niveaux : **User Story** (condition de merge), **Sprint** (condition de livraison démo), **Lot** (condition de mise en production et de sortie de lot).

Les critères bloquants (🔴) sont non négociables : ils ne peuvent pas être dérogés, même sous pression de délai. Les critères recommandés (🟡) peuvent faire l'objet d'un arbitrage documenté.

---

## 1. DoD d'une User Story

Une User Story est terminée et peut être mergée sur la branche principale quand **tous** les critères suivants sont verts.

### 1.1 Spécification et traçabilité

- [ ] La US respecte le format INVEST : **I**ndépendante, **N**égociable, **V**aluable, **E**stimable, **S**ized ≤ 8 points, **T**estable.
- [ ] Elle est liée à un identifiant d'exigence fonctionnelle du CDC (`EF-<MODULE>-n`) ou à un critère non-fonctionnel (`ENF-*`).
- [ ] Les critères d'acceptance sont rédigés en format Gherkin (`GIVEN / WHEN / THEN`) et incluent : 1 scénario nominal, au minimum 2 scénarios alternatifs, au minimum 2 scénarios d'erreur.
- [ ] Les critères sont **SMART** : Spécifiques, Mesurables, Atteignables, Réalistes, Temporels — aucun critère sans seuil chiffré ou comportement observable.

### 1.2 Développement — Clean Architecture et SOLID

- [ ] Aucune logique métier dans un contrôleur ni dans un template Twig (`ARC-15/16`).
- [ ] Le cas d'usage est invocable sans HTTP (test unitaire du domaine sans serveur) (`ARC-17`).
- [ ] Aucune entité de persistance exposée directement via l'API — DTO strict (`ADR-4`, `ARC-18`).
- [ ] Validation et vérification d'habilitation dans la couche applicative, jamais dans l'adaptateur (`ARC-19`).
- [ ] Les calculs financiers ou capacitaires sont dans le moteur unique partagé — aucune duplication backend/frontend (`ARC-6`).
- [ ] Toute fonction IA passe par la couche d'abstraction unique — aucun appel direct depuis le métier (`ARC-5`).
- [ ] PHPStan niveau max : aucune erreur tolérée.
- [ ] PSR-12 respecté, style validé par le formateur automatique.
- [ ] Aucune dépréciation déclenchée par le code applicatif (tolérance zéro — `ARC-51`).

### 1.3 Tests — TDD obligatoire

- [ ] **Cycle TDD respecté :** test écrit avant l'implémentation (RED → GREEN → REFACTOR).
- [ ] Chaque règle de gestion couverte par la US dispose d'un test nommé d'après son identifiant `RG-*` (`ARC-103`).
- [ ] Couverture de tests ≥ 80 % sur le code nouvellement écrit — seuil bloquant CI (`ENF-MAINT-1`).
- [ ] Les tests s'exécutent en configuration **worker** (FrankenPHP) — aucun état conservé entre requêtes (`ARC-50`, `ADR-11`).
- [ ] Les tests des scénarios d'isolation multi-tenant sont verts : aucune donnée d'un tenant accessible depuis un autre (`ENF-SEC-4`).
- [ ] Les invariants du modèle de données (`INV-1..8`) sont garantis en base et couverts par des tests dédiés (`ARC-104`).

### 1.4 Sécurité et habilitations

- [ ] L'habilitation est vérifiée à la source de la donnée, jamais au niveau de l'affichage (`ENF-SEC-5`).
- [ ] Si la US implique une fonction IA : le contexte transmis au modèle est filtré selon les droits de l'utilisateur (`ARC-9`, `HAB-5`). Ce périmètre de sécurité est écrit à la main, relu ligne à ligne, testé manuellement — il n'est **pas délégué** au développement assisté (`ARC-106`).
- [ ] Toute lecture de donnée RH sensible ou de coût est journalisée (`HAB-6`, `ENF-SEC-7`).
- [ ] Aucune secret ou donnée d'authentification dans le dépôt (`ADR-11`).

### 1.5 Qualité du code

- [ ] KISS : méthodes < 20 lignes, complexité cyclomatique < 10, indentation max 3 niveaux.
- [ ] DRY : aucune logique dupliquée entre modules (frontières vérifiées par Deptrac en CI — `ARC-63`).
- [ ] YAGNI : aucune fonctionnalité non demandée dans le ticket.
- [ ] Le code est livré avec ses tests — pas de code sans test (`ARC-107`).

### 1.6 CI verte

- [ ] La chaîne CI est **entièrement verte** avant le merge (`ADR-12`) : PHPStan max, Deptrac, style, tests + couverture ≥ 80 %, tests isolation multi-tenant, tests worker, zéro dépréciation, audit des dépendances, détection de secrets.
- [ ] Aucune fusion sans chaîne verte — aucune exception.

### 1.7 Documentation et révision

- [ ] La PR inclut une description claire du changement et de la valeur ajoutée.
- [ ] Si la US introduit une décision d'architecture, un ADR est créé ou mis à jour.
- [ ] La PR a été relue par au moins une autre personne avant le merge.

---

## 2. DoD d'un Sprint

Un sprint est terminé et peut être présenté en Sprint Review quand **tous** les User Stories du sprint satisfont leur DoD individuelle ET que les critères suivants sont verts.

### 2.1 Livraison de valeur

- [ ] Les US du sprint sont **déployées sur l'environnement de recette** (staging) — pas seulement mergées.
- [ ] Une démonstration des fonctionnalités livrées est réalisée devant un utilisateur réel ou un représentant métier — pas uniquement devant l'équipe technique.
- [ ] L'incrément est potentiellement livrable (qualité production) — même s'il ne va pas en production.

### 2.2 Tests et qualité

- [ ] Tous les tests automatisés passent sur l'environnement de recette.
- [ ] La couverture de tests sur les règles critiques (valorisation, marge, capacité, habilitations) reste ≥ 80 % en agrégat (`ENF-MAINT-1`).
- [ ] Zéro régressions non assumées sur les fonctionnalités des sprints précédents.
- [ ] Le test de non-divergence du modèle analytique est vert en intégration continue (`ARC-113`).

### 2.3 Backlog et pilotage

- [ ] Le backlog du sprint suivant est raffiné (User Stories estimées, priorisation à jour, dépendances identifiées).
- [ ] Les US non terminées sont réestimées et repositionnées dans le backlog (elles ne sont pas automatiquement transférées au sprint suivant sans réévaluation).
- [ ] La vélocité du sprint est enregistrée.
- [ ] Les indicateurs de pilotage projet sont mis à jour (avancement lot, exigences livrées / total, couverture de tests).

### 2.4 Sécurité

- [ ] Aucune régression sur les tests d'isolation multi-tenant.
- [ ] Si une nouvelle fonction IA a été livrée dans le sprint : le filtrage à la source (`ARC-9`) a été relu et le test d'intrusion de périmètre a été exécuté manuellement.

### 2.5 Rétrospective

- [ ] Une rétrospective est tenue à chaque fin de sprint (**Directive Fondamentale** : même sous pression de délai, elle n'est pas annulée).
- [ ] Au moins une action concrète d'amélioration est identifiée et assignée.
- [ ] Les actions des rétrospectives précédentes sont examinées.

---

## 3. DoD d'un Lot

Un lot est terminé et peut être mis en production quand **tous** les critères du lot sont verts. Certains critères sont **bloquants** : la mise en production est interdite s'ils ne sont pas satisfaits, quelle que soit la pression de délai ou de budget.

### 3.1 Critères bloquants transverses à tous les lots 🔴

> Ces quatre critères ne peuvent pas être dérogés. Couper dedans ne fait pas économiser du budget : cela déplace la dette vers une réécriture ultérieure, avec intérêts.

#### 🔴 ENF-SEC-4 — Isolation inter-tenant

- [ ] Un **test d'intrusion dédié** (identifiant forgé, extraction par export, extraction via prompt IA) est réalisé avant toute MEP.
- [ ] Aucun accès cross-tenant n'a pu être démontré lors du test.
- [ ] Le résultat du test est documenté et signé.

#### 🔴 ENF-SEC-6 — Habilitations IA à la source

- [ ] Toute fonction IA du lot accède aux données uniquement via le même contrôle d'habilitation que l'utilisateur (`HAB-5`).
- [ ] Un **test d'intrusion** couvrant l'injection de consigne et l'extraction par recoupement a été réalisé sur chaque fonction IA du lot.
- [ ] Le périmètre de sécurité de chaque fonction IA a été **écrit à la main, relu ligne à ligne et testé manuellement** — non délégué au développement assisté (`ARC-106`).

#### 🔴 ENF-IA-1 — Explicabilité de toute suggestion IA

- [ ] **Aucune fonction IA ne peut être mise en production sans dispositif d'explicabilité** : chaque suggestion, alerte ou synthèse expose les enregistrements source qui l'ont alimentée (`ARC-10`).
- [ ] La séparation calcul / rédaction est respectée : aucun chiffre n'est issu d'un LLM (`ARC-11`, `ENF-IA-3`).

#### 🔴 ENF-MAINT-1 — Couverture de tests ≥ 80 % sur les règles critiques

- [ ] La couverture sur les règles de valorisation, de marge, de capacité et d'habilitations est ≥ 80 % en CI, **et bloque le pipeline de déploiement** si elle passe sous ce seuil.

### 3.2 Critères bloquants du lot 1 🔴

#### 🔴 ENF-UX-1 — Saisie de temps ≤ 2 min/semaine

- [ ] Un **test utilisateur** sur au minimum 5 profils représentatifs de P1 (Camille) est réalisé **avant** la mise en production du lot 1.
- [ ] Le temps médian de saisie hebdomadaire est ≤ 2 minutes.
- [ ] Si ce critère n'est pas atteint, le lot 1 n'est pas mis en production — l'ergonomie de saisie est retravaillée.

#### 🔴 ENF-RGPD-5 — AIPD (pour `EF-TMP-10` et le lot 4)

- [ ] L'Analyse d'Impact sur la Protection des Données est réalisée et validée **avant** le développement de la fonction de pré-remplissage par signaux d'activité (`EF-TMP-10`).
- [ ] Le consentement est recueilli de manière explicite et révocable avant toute collecte de signal.

#### Critères supplémentaires du lot 1

- [ ] `ARC-113` : le test de non-divergence du modèle analytique (transactionnel vs étoile) est vert en intégration continue.
- [ ] `ARC-103` : chaque règle de gestion `RG-*` du périmètre du lot dispose d'un test nommé d'après elle.
- [ ] `ARC-50` : l'ensemble des tests s'exécute en configuration worker, verts.
- [ ] Taux de saisie complète à J+2 ≥ 80 % sur l'organisation pilote après **6 semaines d'usage réel**. Si ce critère n'est pas atteint, le lot 2 ne démarre pas.

### 3.3 Critères bloquants du lot 2

- [ ] L'écart entre la marge HotOnes et la marge comptable est expliqué à 100 % sur un exercice complet (`EF-FIN-23`).
- [ ] Le resource manager du pilote **n'utilise plus de tableur de staffing en parallèle** — ce critère est vérifié directement, pas inféré.
- [ ] La capacité nette affichée est jugée conforme à la réalité par le resource manager du pilote (`EF-PLN-2`).
- [ ] Prérequis : l'AIPD (`ENF-RGPD-5`) et l'arbitrage `ARB-10` sont clos avant le développement de `EF-TMP-10`.

### 3.4 Critères bloquants du lot 3

- [ ] 🔴 `EF-PIL-19` : un **test d'intrusion** sur l'assistant en langage naturel est réalisé, couvrant l'injection de consigne et l'extraction par recoupement. **Aucune mise en production sans ce test.**
- [ ] `EF-CRM-20` : la bascule devis → projet se fait sans aucune ressaisie.
- [ ] `EF-PIL-14` : aucun utilisateur ne reçoit plus d'une notification agrégée par jour hors criticité.
- [ ] L'exploration en langage naturel est bornée (questions pré-outillées) avant l'exploration libre (`ARB-17`).

### 3.5 Critères bloquants du lot 4

- [ ] `ARB-14` et `ARB-4` (frontière aide à la décision / profilage, position sur l'IA en recrutement) sont tranchés et documentés **avant** la conception.
- [ ] La qualification juridique AI Act (`CTR-3`) est finalisée et disponible.
- [ ] Aucune fonction ne produit un score, un classement ou une recommandation d'écartement de personne (`RG-RH-4`, `RG-REC-2`).
- [ ] La traçabilité complète des accès aux données RH sensibles est active (`HAB-6`).

### 3.6 Critères communs à tous les lots (avant MEP)

#### Technique

- [ ] La CI complète est verte : PHPStan max, Deptrac, style, tests ≥ 80 %, tests isolation multi-tenant, tests worker, zéro dépréciation, audit des dépendances, détection de secrets, tests E2E, test de performance (`ADR-12`).
- [ ] Test de charge sous la pointe ×5 réalisé avant MEP (`ENF-PERF-6`).
- [ ] RPO ≤ 1 h et RTO ≤ 4 h vérifiés par un test de restauration (`ENF-DISPO-2/3`).
- [ ] Aucune dépréciation active dans les dépendances (audit automatisé).

#### Fonctionnel

- [ ] Toutes les User Stories du lot labellisées `Must` sont livrées et recettées.
- [ ] Au moins 60 % des US `Should` du lot sont livrées.
- [ ] Chaque US a satisfait sa DoD individuelle (section 1).
- [ ] Une démonstration complète du lot est réalisée devant les parties prenantes (Sprint Review de lot).

#### Sécurité et conformité

- [ ] Le test d'intrusion inter-tenant (`ENF-SEC-4`) est réalisé et concluant.
- [ ] Si le lot inclut des fonctions IA : test d'intrusion IA (`ENF-SEC-6`) réalisé et concluant.
- [ ] Les droits RGPD (purge, anonymisation, export) sont vérifiables techniquement (`ENF-RGPD-2/9`).
- [ ] L'hébergement et l'inférence IA sont localisés dans l'UE (`ENF-RGPD-7`, `CTR-5`).

#### Disponibilité

- [ ] Disponibilité ≥ 99,5 % sur les heures ouvrées est dimensionnée et vérifiable (`ENF-DISPO-1`).
- [ ] La dégradation gracieuse des fonctions IA est testée : le produit reste pleinement utilisable sans IA (`ENF-DISPO-5`, `ARC-80`, `ENF-IA-9`).

#### Documentation

- [ ] L'API du lot est documentée et versionnée (`ENF-MAINT-4`).
- [ ] Les jeux de données de test représentatifs des 3 tailles de tenant sont régénérables (`ENF-MAINT-5`).
- [ ] Le changelog du lot est mis à jour.
- [ ] Les ADR créés ou modifiés pendant le lot sont à jour.

---

## 4. Règles complémentaires

### 4.1 Ce que la DoD ne dégrade jamais

Quels que soient les délais ou les contraintes budgétaires, les éléments suivants ne font jamais l'objet d'une dérogation :

1. **L'ergonomie de la saisie de temps** (`EF-TMP-3`, `ENF-UX-1`) — sans elle, rien ne fonctionne.
2. **L'isolation multi-tenant et le filtrage IA à la source** (`ENF-SEC-4`, `ENF-SEC-6`) — un incident ici est fatal commercialement.
3. **Les invariants du modèle de données** (`INV-1..8`) — non rétro-adaptables. À poser dès le premier schéma du lot 1.
4. **La traçabilité des indicateurs** (`EF-PIL-5`) — sans elle, les chiffres ne seront pas utilisés.

### 4.2 Rôle du développement assisté dans la DoD

Le développement assisté par agent (`ADR-16`) déplace le goulot d'écriture vers la relecture et la décision. La DoD intègre cette réalité :

- Un test nommé par `RG-*` reste obligatoire même si le code est généré (`ARC-103`).
- Le périmètre de sécurité (habilitations, filtrage IA) n'est **jamais délégué** au développement assisté (`ARC-106`) : il est écrit, relu et testé manuellement.
- Le code généré est livré avec ses tests — non avec l'intention d'écrire les tests ensuite (`ARC-107`).
- Les tests sont écrits depuis l'exigence, pas depuis l'implémentation (`ARC-108`).

### 4.3 Indicateurs de pilotage de la DoD

| Indicateur | Fréquence | Seuil d'alerte |
|-----------|-----------|---------------|
| Couverture de tests (règles critiques) | Continu (CI) | < 80 % |
| Règles `RG-*` sans test nommé | Mensuel | > 0 |
| Dépréciations déclenchées | Continu (CI) | > 0 |
| Tests d'isolation multi-tenant | Continu (CI) | Tout échec |
| Divergence modèle analytique | Hebdomadaire (ARC-114) | > 0 |
| Jours depuis le dernier test d'usage réel | Bimensuel | > 30 j (RSQ-17) |

---

**Documents liés :** `prd.md`, `personas.md`, `cdc/`, `analysis/constraints.md`
