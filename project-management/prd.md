# Product Requirements Document — HotOnes

## Informations du document

| Champ | Valeur |
|-------|--------|
| **Nom du produit** | HotOnes — ERP de gestion d'agence digitale / ESN |
| **Version** | 0.1 (draft) |
| **Date** | 2026-08-31 |
| **Auteur** | AMOA / Product Owner |
| **Statut** | Draft |

---

## 1. Résumé exécutif

### 1.1 Objet

HotOnes est la refonte complète de l'ERP interne d'une agence digitale / ESN, conçue pour devenir un produit SaaS commercialisable. Il pilote le cycle de vie projet — de l'opportunité commerciale à la clôture financière — en réconciliant en temps réel les trois variables fondamentales de l'agence : **charge vendue, capacité disponible, marge réelle**.

### 1.2 Contexte

Le MVP existant couvre partiellement la chaîne fonctionnelle, avec des outils séparés (CRM, tableur de staffing, saisie de temps, comptabilité) réconciliés manuellement une fois par mois, avec ~3 semaines de retard. La refonte vise deux ruptures :

1. **L'IA comme réducteur de friction de saisie** — priorité absolue, avant les usages « démonstratifs ».
2. **L'IA comme aide à la décision sous condition d'explicabilité** — toute suggestion expose les données qui la fondent (`ENF-IA-1`).

La décision de cadrage `ARB-20` (2026-08-31) retient le **périmètre complet** (6 lots, 248 exigences fonctionnelles, équipe cible ~4,5 ETP) et le **scénario C** : reconstruction du socle multi-tenant, reprise sélective et motivée du MVP (`CDR-2`).

### 1.3 Périmètre de haut niveau

9 modules fonctionnels (REF, CRM, PRJ, PLN, TMP, FIN, RH, REC, PIL), 6 lots sur 18-22 mois, chiffrage indicatif ~1 075 j·h (fourchette 840-1 430, hors design/UX +15-20 %). Voir section 7 pour le détail inclus/exclu.

---

## 2. Problème à résoudre

### 2.1 Situation actuelle

Les agences digitales et ESN de 10 à 150 personnes pilotent trois variables interdépendantes avec des outils séparés, réconciliés manuellement une fois par mois avec ~3 semaines de retard. La donnée de temps est le point de défaillance unique : sans temps fiable, ni marge ni capacité ne sont calculables.

### 2.2 Cinq pain points récurrents

| Réf | Pain point | Impact | Personas concernés |
|-----|-----------|--------|-------------------|
| PP-1 | Dérive projet détectée trop tard (60-80 % du budget consommé quand l'alerte arrive) | Critique | P2 Marc (CP), P6 Élodie (direction) |
| PP-2 | Staffing réactif, sans visibilité sur le pipeline pondéré | Élevé | P3 Sophie (resource manager), P4 Yann (commercial) |
| PP-3 | Besoin de recrutement constaté, jamais anticipé (délai 3-6 mois > horizon de visibilité) | Élevé | P5 Nadia (RH), P6 Élodie |
| PP-4 | Suivi RH : charge administrative non réinjectée dans la décision | Moyen | P5 Nadia |
| PP-5 | Saisie de temps mal faite car inutile à celui qui la fait — point de défaillance unique | Critique | P1 Camille (collaborateur) — 80 % des utilisateurs |

### 2.3 Opportunité

Traiter la saisie de temps comme un **enjeu d'adoption** (budget ergonomique 2 min/semaine, pré-remplissage IA consentie) constitue le principal différenciateur face aux ERP d'agence existants. L'IA explicable et le socle multi-tenant posé tôt sont des leviers commerciaux autant que des exigences.

---

## 3. Objectifs et indicateurs de succès

### 3.1 Objectifs business

- **Principal :** Produire un ERP SaaS utilisé au quotidien, qui fiabilise la donnée de temps, détecte la dérive projet, et permet le pilotage marge/capacité sans tableur.
- **Secondaires :** Anticiper les besoins RH/recrutement ; fermer la boucle commerce → production → finance.

### 3.2 Objectifs utilisateurs

- P1 Camille : saisir son temps en moins de 2 minutes sans y penser.
- P2 Marc : voir la dérive avant qu'elle soit irrattrapable.
- P3 Sophie : arbitrer les affectations sur 4-12 semaines sans tableur.
- P4 Yann : s'engager sur une date de démarrage en connaissant la capacité réelle.
- P5 Nadia : piloter entretiens et recrutement sans relancer manuellement.
- P6 Élodie : lire marge, taux d'occupation et tension capacitaire en une vue explicable.

### 3.3 KPI (objectifs OBJ-1 à OBJ-7)

> **Important — `ARB-2` :** les baselines sont **inconnues** à ce jour. Elles doivent être mesurées sur l'organisation pilote **avant** le lot 1, via `AUD-3` (relevé sur 4 semaines). Sans cette mesure, le ROI reste déclaratif.

| Réf | Objectif | Indicateur | Baseline | Cible à 12 mois |
|-----|----------|-----------|---------|----------------|
| `OBJ-1` | Fiabiliser la donnée de temps | Taux de saisie complète à J+2 | À mesurer sur le pilote (AUD-3) | ≥ 90 % |
| `OBJ-2` | Détecter la dérive tôt | % dépassements > 10 % détectés avant 50 % de consommation budget | À mesurer (AUD-3) | ≥ 75 % |
| `OBJ-3` | Réduire le coût du suivi | Temps hebdo CP au reporting | À mesurer (AUD-3) | −40 % |
| `OBJ-4` | Améliorer le taux d'occupation | Taux d'occupation facturable moyen | À mesurer (AUD-3) | +5 pts (sans surcharge) |
| `OBJ-5` | Anticiper le recrutement | Délai détection besoin → ouverture poste | À mesurer (AUD-3) | ≤ 15 jours |
| `OBJ-6` | Fiabiliser la prévision | Écart marge prévisionnelle mi-projet vs clôture | À mesurer (AUD-3) | ≤ 5 points |
| `OBJ-7` | Obtenir l'adhésion | Utilisateurs actifs hebdo / déclarés | — | ≥ 85 % |

> `OBJ-7` est le signal le plus discriminant : un produit utilisé produit de la donnée fiable.

### 3.4 Critères de succès

- [ ] `OBJ-7` ≥ 85 % d'utilisateurs actifs hebdo sur le pilote à M+12
- [ ] `OBJ-1` ≥ 90 % de saisies complètes à J+2 après 12 mois
- [ ] Test d'intrusion isolation inter-tenant réussi (`ENF-SEC-4`) — bloquant lot 1
- [ ] Saisie de temps ≤ 2 min/semaine validée par test utilisateur (`ENF-UX-1`) — bloquant lot 1
- [ ] Marge HotOnes réconciliée avec la comptabilité à 100 % sur un exercice (`EF-FIN-23`) — bloquant lot 2

---

## 4. Utilisateurs cibles

Détail complet dans `/project-management/personas.md`. Synthèse ci-dessous.

### 4.1 Personas principaux (P1-P6)

| Réf | Persona | Rôle | Usage | Critère de rejet |
|-----|---------|------|-------|-----------------|
| `P1` | Camille | Collaboratrice (développeuse) | Quotidien, 2-5 min | Saisie > 2 min, ou sentiment de flicage |
| `P2` | Marc | Chef de projet | Quotidien-hebdo, 20-40 min | Ressaisie dans HotOnes de ce qui est déjà dans Jira ; chiffres faux |
| `P3` | Sophie | Resource manager / dir. production | Hebdo, 1-2 h | Plan de charge ne reflétant pas la réalité ; suggestions inexpliquées |
| `P4` | Yann | Commercial / directeur de clientèle | Quotidien, 15-30 min | Blocage sur une affaire faute de paramétrage parfait |
| `P5` | Nadia | Responsable RH | Hebdo, 1-3 h | Module dupliquant le SIRH ; données d'évaluation trop accessibles |
| `P6` | Élodie | Dirigeante / associée | Hebdo-mensuel, 30 min | Chiffres inexplicables ; écart inexpliqué avec la comptabilité |

> P1 représente 80 % du volume d'utilisateurs. Son adoption conditionne la fiabilité de toute la chaîne financière.

### 4.2 Acteurs secondaires

- **Administrateur tenant** : paramètre l'instance de son organisation (rôles, taux, calendriers).
- **Éditeur (équipe HotOnes)** : supervision multi-tenant, support, mise en service — accès exceptionnel, tracé et notifié (`ENF-SEC-8`).
- **Systèmes tiers** : comptabilité, SIRH, Jira/Linear, calendrier, messagerie, signature électronique, job boards.

### 4.3 Parcours clé — saisie de temps (P1)

```
[Lundi matin] → [Accès mobile ou desktop] → [Pré-remplissage IA depuis planning + signaux d'activité]
      → [Validation / ajustement < 2 min] → [Données fiables pour le CP et la direction]
```

---

## 5. Exigences fonctionnelles

> **Note de lecture.** Les 248 exigences fonctionnelles (`EF-<MODULE>-n`) sont spécifiées dans le cahier des charges (`cdc/`). Cette section présente les 9 modules comme blocs d'exigences, avec priorité dominante MoSCoW, lot cible et exigences phares. Les US détaillées seront dans `backlog/`.

### 5.1 Must Have — cœur du produit (lots 1-2)

#### Module REF — Référentiels et paramétrage

| Attribut | Valeur |
|----------|--------|
| Lot cible | 1 |
| Nb d'EF | ~25 |
| Priorité dominante | Must |
| Exigences phares | `EF-REF-1` Organisation et services ; `EF-REF-15` Profils et taux historisés (`INV-2`) ; `EF-REF-31` Modèle de rôles et habilitations par défaut |

#### Module PRJ — Projets et delivery

| Attribut | Valeur |
|----------|--------|
| Lot cible | 1 |
| Nb d'EF | ~25 |
| Priorité dominante | Must |
| Exigences phares | `EF-PRJ-1` Structure projet-lots-jalons ; `EF-PRJ-8` Calcul avancement/RAF/consommation (3 données distinctes `INV-4`) ; `EF-PRJ-14` Détection de dérive et alerte |

#### Module TMP — Temps et activité

| Attribut | Valeur |
|----------|--------|
| Lot cible | 1 |
| Nb d'EF | ~30 |
| Priorité dominante | Must |
| Exigences phares | `EF-TMP-3` Saisie hebdomadaire ≤ 2 min (critère bloquant `ENF-UX-1`) ; `EF-TMP-9` Pré-remplissage IA depuis le planning (consentement) ; `EF-TMP-6` Clôture et validation des temps (imputation immuable `INV-3`) |

#### Module PLN — Planification et staffing

| Attribut | Valeur |
|----------|--------|
| Lot cible | 2 |
| Nb d'EF | ~26 |
| Priorité dominante | Must |
| Exigences phares | `EF-PLN-2` Capacité nette calculée (calendriers + absences + affectations) ; `EF-PLN-10` Plan de charge 12 semaines par profil ; `EF-PLN-21` Simulation d'impact d'une nouvelle affaire |

#### Module FIN — Finance et rentabilité

| Attribut | Valeur |
|----------|--------|
| Lot cible | 2 |
| Nb d'EF | ~25 |
| Priorité dominante | Must |
| Exigences phares | `EF-FIN-1` Valorisation des imputations (coûts historisés `INV-2/3`) ; `EF-FIN-12` Tableau de bord financier marge/atterrissage ; `EF-FIN-23` Écran de contrôle des écarts comptables |

### 5.2 Should Have (lots 3-4)

#### Module CRM — Avant-vente et pipeline

| Attribut | Valeur |
|----------|--------|
| Lot cible | 3 |
| Nb d'EF | ~24 |
| Priorité dominante | Should |
| Exigences phares | `EF-CRM-5` Pondération d'opportunité et capacité prévisionnelle ; `EF-CRM-15` Devis avec marge prévisionnelle ; `EF-CRM-20` Bascule devis → projet sans ressaisie (critère bloquant lot 3) |

#### Module PIL — Pilotage et reporting

| Attribut | Valeur |
|----------|--------|
| Lot cible | 3 |
| Nb d'EF | ~20 |
| Priorité dominante | Should |
| Exigences phares | `EF-PIL-5` Traçabilité des indicateurs (données source citées) ; `EF-PIL-14` Budget de notifications (≤ 1 agrégé/jour hors criticité) ; `EF-PIL-19` Assistant en langage naturel borné (critère sécurité bloquant) |

#### Module RH — RH et cycle de vie collaborateur

| Attribut | Valeur |
|----------|--------|
| Lot cible | 4 |
| Nb d'EF | ~26 |
| Priorité dominante | Should |
| Exigences phares | `EF-RH-6` Cartographie des compétences validées ; `EF-RH-7` Gestion des entretiens (habilitations strictes `HAB-2`) ; `EF-RH-11` Structuration IA des compétences (jamais d'évaluation de personnes) |

#### Module REC — Recrutement

| Attribut | Valeur |
|----------|--------|
| Lot cible | 4 |
| Nb d'EF | ~22 |
| Priorité dominante | Should |
| Exigences phares | `EF-REC-1` Détection de besoin depuis la tension capacitaire ; `EF-REC-14` Extraction IA de CV structurée ; `EF-REC-9` Intégration des candidats retenus |

### 5.3 Could Have — industrialisation (lot 5)

- Onboarding self-service et assistant de configuration (`EF-REF-29`, `-34`)
- Import initial depuis tableur (`REP-3`, `REP-4`) — priorité M à chaque onboarding tenant
- Supervision multi-tenant et suivi consommation IA (`ENF-SAAS-5`, `ENF-IA-5`)
- Accessibilité WCAG AA et internationalisation FR+EN (`ENF-UX-4`, `-5`)
- Réversibilité et export complet (`ENF-RGPD-9`)

### 5.4 Won't Have (exclu de ce CDC — `ARB-1`)

- Comptabilité générale et déclarations fiscales
- Calcul de paie et bulletins de salaire
- Gestion de tâches / tickets (intégration Jira/Linear uniquement)
- Portail client externe (statut `W` — backlog futur)

---

## 6. Exigences non-fonctionnelles

### 6.1 Performance (dimensionnement : tenant grand = 150 collab., pointe fin de mois ×5)

| Réf | Exigence | Seuil | Priorité |
|-----|----------|-------|---------|
| `ENF-PERF-1` | Consultation courante | < 1 s P95 | Must |
| `ENF-PERF-2` | Saisie de temps | < 500 ms P95 | Must |
| `ENF-PERF-3` | Tableaux de bord (5 ans d'historique) | < 3 s P95 | Must |
| `ENF-PERF-4` | Plan de charge 12 mois / 150 collab. | < 2 s | Should |
| `ENF-PERF-5` | Répercussion validation → indicateurs | ≤ 15 min | Must |
| `ENF-PERF-6` | Test de charge sous pointe ×5 obligatoire avant MEP | — | Should |

### 6.2 Disponibilité et continuité

| Réf | Exigence | Seuil |
|-----|----------|-------|
| `ENF-DISPO-1` | Disponibilité heures ouvrées | ≥ 99,5 % (engagement contractuel) |
| `ENF-DISPO-2` | RPO | ≤ 1 heure |
| `ENF-DISPO-3` | RTO | ≤ 4 heures |
| `ENF-DISPO-5` | Dégradation IA sans blocage des fonctions cœur | Chemin manuel équivalent obligatoire |

### 6.3 Sécurité — critères bloquants avant MEP 🔴

| Réf | Exigence | Niveau |
|-----|----------|--------|
| `ENF-SEC-4` | **Isolation stricte inter-tenant** — test d'intrusion dédié (identifiant forgé, export, IA) | **BLOQUANT** |
| `ENF-SEC-6` | **Toute fonction IA accède aux données via le même contrôle d'habilitation que l'utilisateur** (`HAB-5`) — test d'intrusion injection consigne + extraction par recoupement | **BLOQUANT** |
| `ENF-SEC-1/2` | Authentification MDP + 2FA activable par tenant ; SSO (Should) | Must |
| `ENF-SEC-3` | Chiffrement transit + repos | Must |
| `ENF-SEC-5` | Habilitations au niveau de l'accès données, pas de l'affichage | Must |
| `ENF-SEC-7` | Journalisation des accès aux données sensibles (`HAB-6`) | Must |
| `ENF-SEC-8` | Accès éditeur exceptionnel, motivé, tracé et notifié au tenant | Must |
| `ENF-SEC-11` | Correction des vulnérabilités critiques sous 15 j en CI | Should |

### 6.4 RGPD et conformité — prérequis bloquant 🔴

| Réf | Exigence | Niveau |
|-----|----------|--------|
| `ENF-RGPD-5` | **AIPD** pour RH, recrutement et pré-remplissage par signaux d'activité — **prérequis bloquant aux lots 1 (`EF-TMP-10`) et 4** | **BLOQUANT** |
| `ENF-RGPD-2` | Purge/anonymisation vérifiable techniquement | Must |
| `ENF-RGPD-3` | Droits des personnes traités en < 5 j ouvrés | Must |
| `ENF-RGPD-7` | Hébergement + inférence IA dans l'UE | Must |
| `ENF-RGPD-8` | Données tenant jamais utilisées pour l'entraînement sans accord explicite révocable | Must |
| `ENF-RGPD-9` | Réversibilité — export complet en autonomie | Must |
| `ENF-RGPD-10` | Information des personnes sur les traitements automatisés | Must |

### 6.5 IA — explicabilité bloquante 🔴

| Réf | Exigence | Niveau |
|-----|----------|--------|
| `ENF-IA-1` | **Explicabilité de toute suggestion/alerte/synthèse** — aucune MEP sans ce dispositif | **BLOQUANT** |
| `ENF-IA-2` | Non-substitution : l'IA propose, un humain décide | Must |
| `ENF-IA-3` | Séparation calcul/rédaction — aucun chiffre issu d'un LLM | Must |
| `ENF-IA-4` | Traçabilité (fonction, utilisateur, périmètre, coût d'inférence) | Must |
| `ENF-IA-5` | Suivi + plafond par tenant, dégradation gracieuse | Must |
| `ENF-IA-9` | Désactivation par tenant — produit pleinement fonctionnel sans IA | Must |

### 6.6 UX — critère bloquant du lot 1 🔴

| Réf | Exigence | Niveau |
|-----|----------|--------|
| `ENF-UX-1` | **Saisie de temps ≤ 2 min/semaine** — test utilisateur sur 5 profils | **BLOQUANT LOT 1** |
| `ENF-UX-2` | Autonomie sans formation — test sur 3 novices | Must |
| `ENF-UX-3` | Écrans collaborateurs utilisables sur mobile | Must |
| `ENF-UX-4` | Accessibilité WCAG niveau AA | Should |
| `ENF-UX-5` | FR + EN, aucune chaîne en dur | Should |
| `ENF-UX-6` | Actions destructives confirmées et réversibles | Must |

### 6.7 Maintenabilité — seuil bloquant CI 🔴

| Réf | Exigence | Niveau |
|-----|----------|--------|
| `ENF-MAINT-1` | **Couverture de tests ≥ 80 %** sur règles critiques (valorisation, marge, capacité, habilitations) — seuil bloquant CI | **BLOQUANT** |
| `ENF-MAINT-2` | CI/CD dev / recette / prod distincts — 11 étapes bloquantes (`ADR-12`) | Must |
| `ENF-MAINT-4` | API documentée et versionnée | Must |
| `ENF-MAINT-5` | Jeux de test représentatifs des 3 tailles de tenant, régénérables | Must |

### 6.8 Multi-tenant / exploitabilité SaaS

- `ENF-SAAS-1` : isolation logique + paramétrage indépendant par tenant
- `ENF-SAAS-2` : création d'un tenant < 15 min sans intervention infra
- `ENF-SAAS-5` : supervision (dispo, temps de réponse, erreurs, conso IA par tenant)
- `ENF-SAAS-6` : exploitable par 2-4 personnes sans astreinte 24/7 (`CTR-1`)

---

## 7. Périmètre et contraintes

### 7.1 Dans le périmètre (9 modules)

| Module | Code | Lot | Priorité dominante |
|--------|------|-----|-------------------|
| Référentiels et paramétrage | REF | 1 | Must |
| Projets et delivery | PRJ | 1 | Must |
| Temps et activité | TMP | 1 | Must |
| Planification et staffing | PLN | 2 | Must |
| Finance et rentabilité | FIN | 2 | Must |
| Avant-vente et pipeline | CRM | 3 | Should |
| Pilotage et reporting | PIL | 3 | Should |
| RH et cycle de vie collaborateur | RH | 4 | Should |
| Recrutement | REC | 4 | Should |

### 7.2 Hors périmètre — exclusions structurantes (`ARB-1`)

| Exclu | Traitement dans HotOnes |
|-------|------------------------|
| Comptabilité générale et déclarations fiscales | Export vers outil comptable (`EF-FIN-22`) |
| Calcul de paie | Export des éléments variables (`EF-RH-18`) |
| Gestion de tâches / tickets | Intégration Jira/Linear (`EF-PRJ-25`) |
| Signature électronique | Intégration tiers (`EF-CRM-19`) |
| Emailing / marketing automation | Hors proposition de valeur |
| Gestion documentaire d'entreprise | Stockage contextuel limité aux objets HotOnes |
| Portail client externe | Backlog futur — statut `W` |

> Ces exclusions sont **non rouvrables sans arbitrage formel**. Les remettre en cause multiplie le périmètre par 2 à 3 et dilue la proposition de valeur.

### 7.3 Contraintes

| Réf | Contrainte | Nature | Impact |
|-----|-----------|--------|--------|
| `CTR-1` | Opérable par produit + 2-4 dev, sans exploitation dédiée | Organisationnelle | Interdit architectures complexes distribuées |
| `CTR-2` | Conformité RGPD — données RH et d'évaluation | Réglementaire | AIPD obligatoire (lots 1 et 4) |
| `CTR-3` | **AI Act européen** — usages RH/évaluation à risque | Réglementaire | **Qualification juridique externe obligatoire avant conception RH/REC** (`ARB-14`) |
| `CTR-4` | Coût d'inférence IA compatible avec abonnement SaaS | Économique | Budget inférence par tenant + dégradation gracieuse |
| `CTR-5` | Souveraineté : hébergement + inférence dans l'UE | Contractuelle | Restreint les fournisseurs de modèles (`ARB-3`) |

### 7.4 Arbitrages structurants

- **`ARB-20`** (2026-08-31) : périmètre complet retenu malgré `HYP-15` (1 personne au démarrage). L'écart reste un risque actif (`RSQ-3`, `RSQ-9`, `RSQ-17`, `RSQ-20`).
- **`ARB-1`** : pas de gestion de tâches, pas de SIRH complet, pas de comptabilité.
- **`ARB-2`** : mesure des baselines OBJ-1..7 avant lot 1 — action immédiate.
- **`ARB-14`** : qualification juridique AI Act avant conception RH/REC.
- **`ARB-17`** : ouvrir l'exploration en langage naturel de manière bornée avant d'ouvrir l'exploration libre.

### 7.5 Hypothèses

- **`HYP-1`** : le MVP existant n'est pas en production avec des données vivantes. Si fausse → plan de bascule, +3 à 5 mois.
- **`HYP-2`** : une organisation pilote est identifiable et dispose d'un référent dédié (1 j/semaine minimum, `RSQ-10`).
- **`HYP-15`** : l'équipe de développement démarre à 1 personne. L'ambition du périmètre (`ARB-20`) suppose que l'équipe cible (~4,5 ETP) sera progressivement constituée.

### 7.6 Dépendances

| Dépendance | Type | Réf | Statut |
|-----------|------|-----|--------|
| Audit technique de l'existant | Prérequis lot 1 | `AUD-1`, `AUD-2` | À planifier |
| Mesure des situations de référence | Prérequis OBJ-* | `AUD-3` | À planifier |
| Qualification juridique AI Act | Prérequis lots RH/REC | `CTR-3`, `ARB-14` | À engager |
| AIPD pré-remplissage et RH | Prérequis `EF-TMP-10` et lot 4 | `ENF-RGPD-5` | À engager |
| Organisation pilote engagée | Prérequis lot 1 | `HYP-2` | À confirmer |

---

## 8. Trajectoire et jalons

### 8.1 Lotissement — 6 lots sur 18-22 mois

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
              L1 (M6)    L2 (M9)   L3 (M12)  externes
```

### 8.2 Détail des lots

| Lot | Intitulé | Durée | Modules | Charge indicative | Objectif pilote |
|-----|----------|-------|---------|------------------|----------------|
| **Lot 0** | Cadrage et fondations | 6-8 semaines | — | 85 j (60-115) | Pas de logiciel : audits, arbitrages, modèle de données, design system, CI/CD, squelette technique |
| **Lot 1** | Le cœur — projet, temps, référentiels | 4-5 mois | REF, PRJ, TMP + socle | 275 j (215-360) | Une org. pilote saisit son temps, suit ses projets, les chiffres sont justes |
| **Lot 2** | Capacité et argent | 3-4 mois | PLN, FIN | 200 j (160-260) | Le resource manager abandonne son tableur ; la direction voit ses marges |
| **Lot 3** | Amont commercial et pilotage | 3-4 mois | CRM, PIL + PRJ étendu | 200 j (160-260) | La boucle vente → production est fermée ; tableaux de bord décideurs disponibles |
| **Lot 4** | RH, compétences et recrutement | 3-4 mois | RH, REC + PLN étendu | 180 j (140-240) | La boucle capacité → compétence → recrutement est fermée |
| **Lot 5** | Industrialisation SaaS | 2-3 mois | Transverse | 120 j (90-170) | Le produit se vend et s'installe sans intervention de l'éditeur |
| **Total** | | **18-22 mois** | | **1 075 j (840-1 430)** | |

> Non inclus dans le chiffrage : design/UX (+15-20 %), conseil juridique externe, hébergement, effort pilote, commercialisation.

### 8.3 Jalons clés

| Jalon | Description | Mois cible | Prérequis |
|-------|-------------|-----------|---------|
| J0 | Démarrage lot 0 (cadrage) | M1 | `ARB-20` tranché ✓ |
| J1 | Fin lot 0 — feu vert technique | M2 | `AUD-1`, `AUD-2`, invariants posés |
| J2 | **MEP pilote lot 1** | ~M6 | Critères bloquants lot 1 verts |
| J3 | Taux saisie ≥ 80 % sur pilote à J+42 | ~M7 | Validation adoption avant lot 2 |
| J4 | MEP pilote lot 2 | ~M9 | Marge réconciliée ; tableur rm abandonné |
| J5 | MEP pilote lot 3 | ~M12 | Test intrusion PIL-19 réussi |
| J6 | MEP pilote lot 4 | ~M15 | Prérequis ARB-14 + AIPD clos |
| J7 | Commercialisation lot 5 | ~M18-22 | Modèle tarifaire arrêté |

**Règle de dépendance des lots 1-3 :** pas de parallélisation — dépendance de données stricte. Les lots 4 et 5 peuvent se recouvrir partiellement.

---

## 9. Risques et mitigations

Risques de criticité ≥ 15 (P × I, échelle 1-5) :

| Réf | Risque | P | I | Crit. | Mitigation principale |
|-----|--------|---|---|-------|----------------------|
| `RSQ-1` | **Saisie de temps non adoptée** → toute la chaîne financière et capacitaire est fausse | 4 | 5 | **20** | Budget ergonomie prioritaire ; `ENF-UX-1` bloquant lot 1 ; contrepartie visible P1 |
| `RSQ-3` | **Dérive du périmètre** — ambition > capacité de livraison | 4 | 4 | **16** | Exclusions écrites (`ARB-1`) ; règle 60 % Must par lot ; toute nouvelle EF passe par COPIL |
| `RSQ-9` | **Maintien du MVP en parallèle** → capacité de construction effondrée | 4 | 4 | **16** | `CDR-4` : arrêt MVP à la MEP lot 1 — décision sponsor explicite |
| `RSQ-10` | **Référent pilote indisponible** → l'équipe spécifie sans retour terrain | 4 | 4 | **16** | Engagement formel pilote avant lot 1 ; 1 j/semaine min ; escalade sponsor |
| `RSQ-17` | **La construction devient un refuge** — produit avance sans test utilisateur réel | 4 | 4 | **16** | Indicateur : jours depuis le dernier test d'usage (`ARC-100`) ; `MD-1/MD-2` |
| `RSQ-20` | **Capacité de relecture saturée** — code testé fait autre chose que voulu | 4 | 4 | **16** | `ADR-16` : un test nommé par `RG-*` ; `ARC-106` périmètre sécurité non délégué |
| `RSQ-22` | **Divergence silencieuse du modèle analytique** vs transactionnel | 4 | 4 | **16** | `ARC-112/113` reconstruction + test de non-divergence bloquant CI ; `ARC-114` réconciliation |
| `RSQ-2` | **Fuite de données inter-tenant via l'IA** — conséquence commerciale irréversible | 3 | 5 | **15** | `INV-1` sur toute entité ; filtrage à la source (`ARC-9`) ; test intrusion bloquant chaque lot IA |
| `RSQ-5` | **Chiffres financiers non réconciliés** avec la comptabilité → perte de confiance direction | 3 | 5 | **15** | `EF-FIN-23` écran contrôle écarts (lot 2) ; `INV-2/3` ; `ADR-9` schéma en étoile |
| `RSQ-15` | **Fuite d'état entre requêtes** en mode worker FrankenPHP | 3 | 5 | **15** | `ARC-47..50` ; parité worker en dev (`ADR-11`) ; tests en config worker en CI |
| `RSQ-21` | **Règle d'habilitation générée non relue** → accès indus sans erreur visible | 3 | 5 | **15** | `ARC-106` : périmètre sécurité écrit, relu, testé manuellement + intrusion humain |

> **Observation clé.** La majorité de ces risques sont organisationnels ou comportementaux, pas techniques. Le développement assisté par agent les renforce : en supprimant le facteur limitant qui ralentissait la production, il supprime le temps de relecture qui protégeait par la lenteur. Ce qui protégeait par la lenteur doit désormais protéger par l'outillage (`ADR-16`).

---

## 10. Traçabilité des exigences

### 10.1 Convention de nommage

Les exigences fonctionnelles du CDC utilisent le format **`EF-<MODULE>-n`** (ex. `EF-TMP-3`, `EF-PLN-10`). Ce sont les identifiants de référence qui doivent être portés dans :
- Les User Stories du backlog (`backlog/user-stories/`)
- Les tests unitaires (un test nommé par `RG-*` — `ARC-103`)
- Les critères d'acceptance Gherkin
- La documentation d'architecture (ADR)

Le format de traçabilité bidirectionnelle sera :

```
EF-<MODULE>-n (CDC)
  └── US-XXX (User Story)
        ├── T-XXX-YY (Tâche de développement)
        └── Test nommé RG-<MODULE>-n (ARC-103)
```

### 10.2 Volume et répartition par lot

| Lot | Modules | EF Must | EF Should | EF Could | Total approx. |
|-----|---------|---------|-----------|----------|---------------|
| Lot 1 | REF, PRJ, TMP + socle | ~75 | ~15 | ~5 | ~95 |
| Lot 2 | PLN, FIN | ~40 | ~10 | ~5 | ~55 |
| Lot 3 | CRM, PIL | ~35 | ~15 | ~5 | ~55 |
| Lot 4 | RH, REC | ~30 | ~15 | ~5 | ~50 |
| Lot 5 | Transverse SaaS | ~5 | ~20 | ~5 | ~30 |
| **Total** | **9 modules** | **~185** | **~75** | **~25** | **248** |

### 10.3 Couverture actuelle

| Métrique | Valeur | Remarque |
|----------|--------|----------|
| Total EF | 248 | Source : CDC |
| Couvertes par des US | 0 | Backlog non encore décomposé |
| Couvertes par du code | 0 | Développement non démarré |
| Couvertes par des tests | 0 | TDD appliqué à partir du lot 1 |

> Exécuter `/project:generate-backlog` pour démarrer la décomposition en US.
> Exécuter `/project:coverage-map` pour suivre la couverture au fil du développement.

---

## 11. Parties prenantes et validation

### 11.1 Parties prenantes

| Rôle | Responsabilité |
|------|---------------|
| Product Owner / AMOA | Vision produit, priorisation, validation fonctionnelle |
| Responsable technique | Faisabilité, architecture, validation technique |
| Designer UX/UI | Parcours, design system, tests utilisateurs |
| Organisation pilote (référent) | Retour terrain, tests d'usage, mesure baselines |
| Conseil juridique externe | Qualification AI Act (`CTR-3`), AIPD (`ENF-RGPD-5`) |
| Sponsor (direction) | Arbitrages stratégiques, décision arrêt MVP (`CDR-4`) |

### 11.2 Validation

| Rôle | Nom | Date | Signature |
|------|-----|------|-----------|
| Product Owner / AMOA | | 2026-08-31 | |
| Responsable technique | | | |
| Sponsor | | | |

---

## 12. Annexes

### 12.1 Glossaire

| Terme | Définition |
|-------|-----------|
| ERP | Enterprise Resource Planning — ici, pilotage opérationnel et financier d'une agence |
| ESN | Entreprise de Services Numérique |
| Tenant | Instance cliente isolée dans l'architecture multi-tenant |
| EF | Exigence Fonctionnelle — identifiée `EF-<MODULE>-n` dans le CDC |
| ENF | Exigence Non-Fonctionnelle |
| ADR | Architecture Decision Record |
| OBJ | Objectif de succès |
| CTR | Contrainte |
| RSQ | Risque identifié |
| ARB | Arbitrage — décision produit ou architecture tracée |
| INV | Invariant du modèle de données (non rétro-adaptable) |
| HAB | Règle d'habilitation transverse |
| AUD | Audit prérequis (à réaliser avant lot 1) |
| CDR | Condition de réussite |
| HYP | Hypothèse du projet |
| RAF | Reste à faire (projet) |
| RPO | Recovery Point Objective |
| RTO | Recovery Time Objective |
| AIPD | Analyse d'Impact sur la Protection des Données (RGPD) |
| Walking Skeleton | Fonctionnalité complète minimale traversant toutes les couches (lot 1) |

### 12.2 Documents de référence

- `project-management/cdc/` — Cahier des charges complet (source de vérité)
- `project-management/personas.md` — Personas P1-P6 détaillés
- `project-management/definition-of-done.md` — DoD US, Sprint, Lot
- `project-management/analysis/research-summary.md` — Synthèse d'analyse
- `project-management/analysis/constraints.md` — Contraintes et ENF
- `project-management/analysis/risks-opportunities.md` — Registre des risques
- `project-management/analysis/technical-options.md` — Stack et ADR

### 12.3 Historique des révisions

| Version | Date | Auteur | Modifications |
|---------|------|--------|--------------|
| 0.1 | 2026-08-31 | AMOA / Product Owner | Version initiale — rédaction post-analyse (`ARB-20` tranché) |
