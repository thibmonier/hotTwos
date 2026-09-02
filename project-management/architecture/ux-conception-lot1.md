# Plan de conception UX/UI — Lot 1 (EPIC-012 / US-062)

## 1. Objet et portée

Ce document cadre la **conception UX/UI des écrans du lot 1** (US-062) : parcours, matrice
personas × écrans, états à maquetter, traitement des écarts de recette, processus de validation
et stratégie d'accessibilité/recette d'ergonomie. Il ne produit pas les maquettes elles-mêmes
(exécution US-062) mais fixe le cadre qui les rend validables.

**Consigne PO (ferme, mémoire projet) :** la conception UX/UI — maquettes validées — précède tout
développement front. Concrètement pour EPIC-012 :

```
US-061 (charte/design system)
   → US-062 (CE DOCUMENT cadre, puis maquettes conçues et validées)
      → US-063 (intégration layout Skote)
         → US-064 (reskin des écrans — ne démarre écran par écran QUE si sa maquette est validée)
            → US-065 (audit accessibilité WCAG 2.2 AA sur le reskin)
               → US-066 (recette d'ergonomie / validation utilisateurs — clôture EPIC-012)
```

US-064 ne peut pas ouvrir le reskin d'un écran dont la maquette n'est pas tracée « validée »
(CA-5 de US-062). US-065 audite les écrans reskinnés ; US-066 valide l'ergonomie définitive et
conditionne l'ouverture du dev front des lots suivants.

**Écrans du lot 1 concernés** (walking skeleton livré, à concevoir/valider) :

| Écran | Route | Gabarit source |
|-------|-------|-----------------|
| Saisie hebdomadaire | `/saisie` | `templates/timesheet/week.html.twig` |
| Saisie quotidienne mobile | `/saisie/jour` | `templates/timesheet/day.html.twig` |
| Projets — liste | `/projets` | `templates/project/index.html.twig` |
| Projets — détail à onglets | `/projets/{id}` | `templates/project/show.html.twig` |
| Projets — création | `/projets/nouveau` | `templates/project/new.html.twig` |
| Complétude | `/completude` | `templates/completeness/index.html.twig` |
| Absences | `/absences` | `templates/absence/index.html.twig` |
| Relances | `/relances` | `templates/reminder/index.html.twig` |
| Synthèse d'activité | `/synthese` | `templates/home/index.html.twig` |
| Valorisation | `/valorisation` | `templates/pricing/index.html.twig`, `templates/valuation/index.html.twig` |
| Validation des temps | (par lot) | `templates/timesheet/validation.html.twig` |
| Organisation | `/organisation` | `templates/organization/index.html.twig` |
| Administration / périodes | `/administration/periodes` | `templates/period/index.html.twig` |

État actuel constaté (lecture des gabarits) : HTML sémantique brut, mobile-first, `aria-live` déjà
posé sur les zones dynamiques (bannière offline, total du jour), libellés de complétude déjà
doublés texte + emoji (pas de couleur seule) mais **identifiants utilisateur bruts** (`userId[:8]`)
en clair — cf. §5.

---

## 2. Matrice personas × écrans

| Écran | P1 Camille | P2 Marc | P3 Sophie | P4 Yann | P5 Nadia | P6 Élodie | Besoin ergonomique dominant |
|-------|:---:|:---:|:---:|:---:|:---:|:---:|------------------------------|
| Saisie hebdo `/saisie` | ●●● quotidien | ○ consultation | — | — | — | — | **Rapidité** (≤ 2 min, objectif US-051) |
| Saisie jour `/saisie/jour` | ●●● quotidien, mobile | — | — | — | — | — | Rapidité + tolérance offline |
| Projets — liste | ● | ●●● quotidien | ● | ○ (ses affaires) | — | ○ | Findability, filtre rapide |
| Projets — détail (onglets) | ● (son projet) | ●●● pilotage | ●● affectations | ○ marge (ses affaires) | — | ○ marge | **Lisibilité info clé** (budget/RAF), cloisonnement coût/marge par habilitation |
| Complétude `/completude` | — | ●● son équipe | ●●● (RM) | — | ○ | — | Identification rapide des retards (OBJ-1) — **F-S5-4** |
| Absences | ●● (déclarer) | ○ (son équipe) | ● | — | ●●● (gérer) | — | Simplicité de déclaration, pas de démarche lourde |
| Relances | — | ●● | ●●● | — | ●● (entretiens) | — | Action groupée, faible charge cognitive |
| Synthèse d'activité | ● (contrepartie visible) | ●● | ● | — | — | ● | Rendre visible la contrepartie de la saisie (anti-RSQ-1) |
| Valorisation | — | ●● | ● | ● (marge prévi.) | — | ●●● | **Sensibilité coût/marge** — masquage strict par habilitation |
| Validation des temps (lot) | — | ●●● | ●● | — | — | — | Traitement par lot, réversibilité (annuler validation) |
| Organisation | ○ | ○ | ● | — | ●● | ○ | Cartographie claire, peu de fréquence |
| Administration / périodes | — | — | — | — | — | — (admin tenant) | Configuration ponctuelle, pas d'optimisation UX poussée |

Légende : ●●● usage principal/quotidien · ●● fréquent · ● occasionnel · ○ consultation rare · — non concerné.

**Règle de priorisation (cf. personas.md) :** si P1 rejette l'écran de saisie, tout le reste perd
sa valeur — la saisie hebdo et la saisie jour sont donc les écrans à concevoir et valider en
premier, avant les écrans de pilotage.

**Sensibilité des données par habilitation à respecter dans les maquettes :**
- Coût/marge (`Projets — détail`, `Valorisation`) : visible uniquement aux rôles habilités (P2 sur ses projets, P3, P6, P4 sur ses affaires) — la maquette doit prévoir l'état « colonne/bloc masqué » pour P1.
- Contenu d'entretien (`HAB-2`, hors lot 1 mais à anticiper pour cohérence design system) : intéressé + manager direct + RH seulement.

---

## 3. Parcours clés à concevoir

Pour chaque parcours : objectif utilisateur, étapes, points de friction connus, critère de réussite.

### 3.1 Saisie complète à J+2 (P1)

- **Objectif** : déclarer son temps de la semaine/journée en un minimum de gestes.
- **Étapes** : arriver sur `/saisie` (ou `/saisie/jour` en mobilité) → voir le pré-remplissage/projets actifs → ajuster durée par projet → soumettre → confirmation visible.
- **Frictions connues** : saisie perçue comme un outil de surveillance (critère de rejet P1) ; absence de feedback immédiat sur ce que la saisie « rapporte » (cf. parcours 3.3) ; gestion de la reprise après coupure réseau (mobile).
- **Critère de réussite** : temps de complétion ≤ 2 min (mesuré en US-066), 0 clic superflu, confirmation immédiate et non intrusive (pas de modal bloquant).

### 3.2 Pilotage complétude + relance (P2/P3)

- **Objectif** : repérer en un coup d'œil qui est en retard et déclencher une relance.
- **Étapes** : ouvrir `/completude` → identifier les lignes en retard/partielles (visuellement ET textuellement) → sélectionner un ou plusieurs collaborateurs → déclencher une relance depuis `/relances` ou une action inline.
- **Frictions connues** : **F-S5-4** — collaborateurs actuellement indistinguables (identifiant tronqué) ; **F-S5-5** — code couleur partiel (état non totalement porté par une info textuelle/icône).
- **Critère de réussite** : identification d'un collaborateur en retard en < 5 secondes ; relance groupée en ≤ 3 clics.

### 3.3 Consultation de la valorisation (P2/P4/P6 — et P1 en version restreinte)

- **Objectif** : comprendre la valeur produite par le temps saisi (contrepartie pour P1 ; pilotage marge pour P2/P4/P6).
- **Étapes** : ouvrir `/valorisation` → vue adaptée au rôle (P1 : contribution/avancement projet, pas de coût ; P2/P6 : marge, budget, atterrissage).
- **Frictions connues** : risque d'exposer une donnée sensible (coût) à un rôle non habilité ; risque de chiffre non traçable/explicable (critère de rejet P6).
- **Critère de réussite** : chaque chiffre affiché est cliquable/traçable jusqu'à l'imputation source ; aucune fuite de donnée hors habilitation.

### 3.4 Validation des temps par lot (P2)

- **Objectif** : valider en masse les temps saisis par l'équipe sur une période.
- **Étapes** : ouvrir la validation des temps → filtrer par période/équipe → sélectionner un lot → valider (ou renvoyer en correction) → confirmation + traçabilité.
- **Frictions connues** : action groupée irréversible perçue comme risquée ; besoin de voir le détail avant de valider en masse sans devoir ouvrir chaque ligne.
- **Critère de réussite** : validation d'un lot en ≤ 5 clics, action réversible ou confirmée explicitement (pas de perte de contrôle utilisateur — heuristique Nielsen n°3).

### 3.5 Déclaration d'absence (P1, gestion P5)

- **Objectif** : poser une absence/congé sans démarche administrative lourde (frustration actuelle P1 : email + attente).
- **Étapes** : ouvrir `/absences` → sélectionner dates/type → soumettre → statut visible (en attente/validé) sans ressaisie ni canal externe.
- **Frictions connues** : incertitude sur le statut de la demande ; pas de vision du solde disponible au moment de la saisie.
- **Critère de réussite** : déclaration en < 1 minute ; statut de la demande visible sans action supplémentaire.

---

## 4. États d'écran à maquetter

Pour **chaque écran du lot 1** (tableau §1), les états suivants sont obligatoires, en déclinaison
**mobile ET desktop** :

| État | Exigence de conception |
|------|-------------------------|
| **Nominal** (chargé, données présentes) | Cas courant, hiérarchie visuelle claire de l'information dominante par persona (§2) |
| **Vide** | Message explicite + action possible (pas un simple "aucune donnée") — ex. `/saisie/jour` a déjà ce traitement pour "Aucun projet actif" |
| **Erreur** | Message actionnable, jamais de stack trace (cf. règle sécurité — mishandling exceptional conditions) |
| **Chargement** | Skeleton/spinner selon durée estimée ; pas de gel d'interface |
| **Sans-permission** | Élément masqué ou dégradé selon habilitation (pas un message d'erreur brutal — ex. bloc marge absent plutôt que "Accès refusé" pour P1 sur `/projets/{id}`) |

**Écrans avec exigence mobile renforcée** (usage réel en mobilité) : `/saisie/jour` (déjà mobile
mais à concevoir/valider formellement), `/absences` (déclaration rapide), `/completude` (consultation
par P2 en déplacement). Les autres écrans (administration, organisation) sont desktop-prioritaires
mais doivent rester utilisables en mobile (pas de régression).

**Parité tactile et cibles** (déjà une exigence transverse US-062/US-065) : aucune action clé
réservée au survol ; cibles interactives ≥ 44 × 44 px sur tous les écrans, y compris desktop
tactile (tablette hybride).

---

## 5. Traitement des écarts de recette

### F-S5-4 — Collaborateurs indistinguables (`/completude`, onglet Équipe, audit)

- **Décision d'ergonomie recommandée** : **Option A du chantier** — remplacer l'identifiant tronqué
  (`userId[:8]`) par l'**email** du collaborateur, résolu tenant-aware. Choix pragmatique : aucune
  notion de nom/prénom n'existe aujourd'hui dans `App\Domain\User\User` (Option B nécessiterait une
  migration de modèle, hors périmètre de ce lot).
- **Traçage dans la maquette** : colonne « Collaborateur » de `/completude`, colonne équivalente sur
  l'onglet Équipe (`/projets/{id}`) et sur les vues d'audit — même traitement partout (cohérence,
  heuristique Nielsen n°4).
- **Point d'ergonomie complémentaire** : si l'email est long, prévoir une troncature visuelle avec
  la valeur complète disponible (tooltip **et** équivalent accessible — `title` ne suffit pas seul,
  prévoir `aria-label` ou texte complet au focus). Ne jamais réintroduire un identifiant technique
  brut, même en secours.
- **Suite** : si le PO valide ultérieurement un enrichissement du modèle (nom/prénom), la maquette
  doit anticiper la bascule email → nom sans redesign (même emplacement, même largeur de colonne).

### F-S5-5 — Code couleur de complétude partiel

- **Constat** : les libellés de complétude (`✅ Complète`, `⚠️ Partielle`, `❌ En retard`, `⏳ En cours`)
  combinent déjà emoji + texte + `aria-label`, mais la **couleur de fond de cellule** (classe
  `state-*`) porte une partie de l'information sans être systématiquement doublée dans les cellules
  du tableau elles-mêmes (les emojis apparaissent, mais leur redondance avec la couleur de fond
  n'est pas vérifiée cellule par cellule).
- **Décision d'ergonomie recommandée** : chaque cellule d'état conserve **texte + icône + couleur**
  (jamais couleur seule — WCAG 1.4.1), et le token de couleur sémantique (US-061 : succès/alerte/
  erreur/neutre) remplace toute valeur de couleur en dur. La légende en tête de tableau reste
  affichée en permanence (pas seulement au survol).
- **Traçage dans la maquette** : variantes de cellule pour les 4 états, avec contraste vérifié
  (US-061 CA-5) et icône/texte lisibles même en désaturation (test de simulation daltonisme à
  prévoir en revue).

### Rappel F1 — Identifiants techniques bruts jamais présentés à l'utilisateur

- **Décision d'ergonomie** : règle transverse, non limitée à `/completude`. Toute maquette du lot 1
  qui affiche une référence (projet, utilisateur, période, ligne d'audit) utilise un **libellé
  métier lisible** (nom de projet, code projet, email, date formatée) — jamais un UUID ou une
  référence technique, y compris tronquée.
- **Traçage** : checklist de revue de maquette (§6) inclut un point de contrôle explicite « aucun
  identifiant technique brut visible ».

---

## 6. Processus de conception et validation

### Rôles

| Agent | Responsabilité |
|-------|-----------------|
| `uiux-orchestrator` | Coordonne la séquence, arbitre les dépendances entre écrans, s'assure que la traçabilité de validation est complète avant transmission à US-064 |
| `ux-ergonome` | Parcours, architecture d'interaction, arbitrages ergonomiques (dont §5), wireframes |
| `ui-designer` | Habillage des wireframes avec les tokens/composants du design system (US-061), maquettes haute-fidélité |
| `accessibility-expert` | Revue a11y des maquettes en amont (contraste, cibles, structure), avant l'audit post-reskin (US-065) |

### Séquence de production (par écran)

1. **Wireframe** (`ux-ergonome`) : structure, contenu, états (§4), parcours associé (§3).
2. **Habillage** (`ui-designer`) : application stricte des tokens/composants US-061 — aucun élément
   hors design system (CA-1 de US-062).
3. **Revue a11y amont** (`accessibility-expert`) : contrôle rapide contraste/cibles/structure avant
   validation (l'audit exhaustif reste US-065, mais les non-conformités évidentes sont corrigées
   dès la maquette pour ne pas les reporter au reskin).
4. **Revue croisée** : vérification de la checklist de contrôle (ci-dessous).
5. **Validation** : décision explicite, tracée.

### Checklist de contrôle avant validation d'une maquette

- [ ] Parcours couvert et cohérent avec §3 (objectif, étapes, critère de réussite)
- [ ] Tous les états requis présents (nominal, vide, erreur, chargement, sans-permission) — §4
- [ ] Déclinaison mobile ET desktop produite
- [ ] Aucun élément hors design system US-061 (tokens/composants uniquement)
- [ ] Aucun identifiant technique brut visible (rappel F1)
- [ ] Aucune information portée par la seule couleur (F-S5-5 et transverse)
- [ ] Cibles tactiles ≥ 44 × 44 px, aucune action réservée au survol
- [ ] Données sensibles (coût/marge, contenu d'entretien) masquées/adaptées selon habilitation (§2)
- [ ] Écart de recette concerné (le cas échéant, §5) explicitement adressé

### Traçabilité de la validation (condition de démarrage de US-064)

Chaque maquette porte, à sa validation, un enregistrement minimal :

```
Écran : /completude
Version maquette : v1
Validée le : AAAA-MM-JJ
Validée par : <rôle/nom>
Checklist : conforme (lien vers checklist remplie)
Statut US-064 correspondant : ouvert / bloqué
```

Ce registre (tableau récapitulatif, à tenir dans le suivi US-062) est la **source de vérité** que
`uiux-orchestrator` consulte avant d'autoriser l'ouverture du reskin d'un écran (CA-5 de US-062).
Un écran sans ligne « validée » dans ce registre reste bloqué pour US-064.

### Ordre de conception recommandé

Conséquence directe de la matrice §2 (priorité à l'adoption de P1, puis aux écrans à fort écart de
recette, puis au reste) :

1. **Saisie hebdo `/saisie`** et **saisie jour `/saisie/jour`** (P1, critique adoption)
2. **Complétude `/completude`** (F-S5-4 + F-S5-5, débloque le pilotage P2/P3)
3. **Absences** (P1/P5, simplicité de déclaration)
4. **Relances** (suite logique de la complétude, P2/P3)
5. **Projets — liste et détail à onglets** (P2 quotidien, sensibilité coût/marge à cadrer tôt)
6. **Validation des temps par lot** (P2, action groupée)
7. **Synthèse d'activité** (contrepartie visible, renforce l'adoption P1)
8. **Valorisation** (P4/P6, sensibilité marge la plus forte — bénéficie des arbitrages posés en 5)
9. **Organisation, profils, administration/périodes** (fréquence faible, peuvent suivre en dernier sans bloquer l'essentiel)

---

## 7. Stratégie accessibilité WCAG 2.2 AA (cadre US-065)

### Critères prioritaires portés dès la conception (lot 1)

| Critère | Exigence AA | Application dans les maquettes |
|---------|-------------|----------------------------------|
| Contraste texte/fond | ≥ 4.5:1 (texte normal), ≥ 3:1 (texte large) | Vérifié sur les tokens US-061 avant usage en maquette |
| Focus visible | Indicateur visible en permanence, contraste suffisant | Spécifié comme état de composant (pas seulement décrit) dans le design system |
| Navigation clavier | Tous les éléments interactifs atteignables/actionnables, pas de piège | Ordre de tabulation documenté dans les wireframes des écrans à interactions complexes (`/completude` sélection multiple, validation par lot) |
| Cibles ≥ 44 × 44 px | Mobile et desktop tactile | Contrôlé dans la checklist §6 |
| ARIA / lecteurs d'écran | Libellés associés, erreurs annoncées, tableaux avec en-têtes associés, composants dynamiques (drawer, infobulles) avec rôles/états ARIA | Déjà amorcé dans le code existant (`aria-live`, `aria-label` sur `/saisie/jour`, `/completude`) — à maintenir et généraliser dans toutes les maquettes |
| Information non portée par la seule couleur | Texte/icône systématique en complément | Traité §5 (F-S5-5) et transverse à tout badge/statut |

### Outillage `axe-core`

- **Portée** : contrôle automatisé exécuté sur les écrans du lot 1 (§1), en environnement de recette,
  après intégration (US-063/US-064) — cadré ici, exécuté en US-065.
- **Seuil de conformité** : **zéro violation critique ou sérieuse** sur le périmètre audité.
- **Intégration recommandée** : exécution scriptée (ex. `axe-core` via Playwright/Puppeteer ou
  extension navigateur en mode manuel pour la première passe), rapport par écran versé dans
  `.recette/` à l'image des rapports de recette fonctionnelle déjà en place.
- **Réutilisabilité** : le script/la configuration `axe-core` et la check-list ci-dessous sont
  documentés pour être réappliqués tels quels aux écrans des lots suivants (US-065 CA-5).

### Check-list réutilisable (lots suivants)

- [ ] Contraste de chaque paire texte/fond ≥ AA (vérifié sur les tokens, pas juste visuellement)
- [ ] Focus visible sur 100 % des éléments interactifs, testé au clavier (Tab/Maj+Tab) sans piège
- [ ] Structure sémantique correcte (landmarks, hiérarchie de titres, tableaux avec `scope`/`th`)
- [ ] Libellés de formulaire associés (`label for`/`aria-labelledby`), erreurs annoncées (`aria-live` ou équivalent)
- [ ] Composants dynamiques (drawer, modales, infobulles) avec rôle/état ARIA correct
- [ ] Information jamais portée par la seule couleur
- [ ] Cibles ≥ 44 × 44 px, aucune action réservée au survol
- [ ] `axe-core` exécuté : zéro violation critique/sérieuse

---

## 8. Recette d'ergonomie (cadre US-066)

### Parcours à tester, par persona

| Parcours (§3) | Persona(s) testeur(s) | Mesure |
|----------------|------------------------|--------|
| Saisie complète à J+2 | P1 | Temps de réalisation vs objectif ≤ 2 min (US-051) |
| Pilotage complétude + relance | P2, P3 | Temps d'identification d'un retard, taux de réussite de la relance groupée |
| Consultation valorisation | P2, P4, P6 | Compréhension du chiffre affiché, capacité à en retrouver la source |
| Validation des temps par lot | P2 | Taux de réussite, absence d'erreur de validation par excès |
| Déclaration d'absence | P1, P5 | Temps de déclaration, clarté du statut de la demande |

### Mesure de la rapidité de saisie

- Chronométrage de la saisie complète d'une journée par un panel représentatif de P1 sur l'écran
  reskinné (`/saisie/jour` et `/saisie`).
- Comparaison à l'objectif ~2 minutes (US-051) ; tout écart significatif (> 20 %) déclenche une
  recommandation d'ergonomie tracée et, si nécessaire, un retour en conception (US-062) avant
  nouvelle validation.

### Collecte et priorisation des retours

- Retours structurés par irritant/confusion/satisfaction, rattachés à l'écran et au parcours
  concernés (pas de retour générique non actionnable).
- Priorisation : **bloquant** (tâche non réalisable ou confusion majeure) / **majeur** / **mineur**.
- Les points **bloquants** sont renvoyés en conception (US-062) puis correction (US-064) avant toute
  validation d'ergonomie — l'ergonomie n'est jamais déclarée définitive avec un bloquant ouvert
  (CA-4 de US-066). Les **mineurs** sont versés au backlog des lots suivants.

### Critère de validation de l'ergonomie définitive

- Tous les parcours clés testés sur les personas concernés, taux de réussite documenté.
- Objectif de rapidité de saisie atteint ou écart expliqué et traité.
- Aucun retour bloquant ouvert.
- Procès-verbal de validation signé (ou plan de correction acté pour les majeurs restants), qui
  conditionne la clôture d'EPIC-012 et l'ouverture du dev front des lots suivants.

---

## 9. Risques et points ouverts

| Sujet | Risque | Mitigation proposée |
|-------|--------|----------------------|
| F-S5-4 — email comme libellé | L'email peut être long ou peu lisible sur mobile ; ne résout pas totalement le besoin d'un « nom » humain | Prévoir troncature accessible (§5) ; ouvrir un arbitrage PO ultérieur sur l'enrichissement du modèle utilisateur (Option B), hors périmètre lot 1 |
| Sensibilité coût/marge par rôle | Une maquette conçue « générique » peut fuiter une donnée sensible si l'état sans-permission n'est pas maquetté explicitement | Rendre l'état « sans-permission » obligatoire dans la checklist (§4/§6), pas optionnel |
| Fast-track vs qualité | La consigne « poser vite » (EPIC-012) peut pousser à valider des maquettes incomplètes | La checklist §6 est un filtre non négociable ; « vite » porte sur l'ordre de traitement (§6), pas sur la rigueur de chaque maquette |
| Dépendance à US-061 | Les maquettes ne peuvent pas être finalisées avant que les tokens/composants soient stabilisés | Débuter les wireframes (structure/parcours) en parallèle de US-061, réserver l'habillage (`ui-designer`) à la disponibilité des tokens |
| Modèle utilisateur incomplet | Toute future demande de « nom d'affichage » redessinera potentiellement la colonne collaborateur | Maquette conçue pour bascule email → nom sans changement de structure (§5) |

---

**Auteur** : agent `ux-ergonome` — **Date** : 2026-09-02 — **Statut** : cadre de conception, à valider par `uiux-orchestrator` avant lancement de la production des maquettes (US-062).
