# Audit et critique UX de l'existant — parcours de saisie collaborateur (P1)

> **Story** : US-082 (Sprint 20, EPIC-013) · **Statut** : 🟡 To Review (PO)
> **Alimente** : US-084 (maquettes haute-fidélité) — les recommandations priorisées ci-dessous cadrent les maquettes.
> **Auteur** : conception UX (agents `ux-ergonome` + `accessibility-expert`) · **Date** : 2026-09-14

---

## 1. Objectif et méthode

Analyse critique et outillée de l'ergonomie des **écrans existants** du parcours de saisie du temps
(persona **P1 — Camille, collaboratrice, 80 % des utilisateurs**), au regard des heuristiques de Nielsen,
de la charge cognitive et de l'accessibilité WCAG 2.2 AA. Le produit historique **hotones** sert de
**source d'inspiration** (patterns à reprendre / faiblesses à ne pas reproduire), **jamais de modèle à copier**.

Le but n'est pas de refondre le code (hors périmètre) mais de **produire des recommandations priorisées**
qui orientent les maquettes cibles (US-084) et **évitent de figer les défauts UX de l'existant dans le reskin**.

### 1.1 Grille d'analyse

| Axe | Contenu |
|-----|---------|
| **Heuristiques de Nielsen** | H1 Visibilité de l'état · H2 Correspondance monde réel · H3 Contrôle & liberté · H4 Cohérence & standards · H5 Prévention des erreurs · H6 Reconnaissance plutôt que rappel · H7 Flexibilité & efficience · H8 Design minimaliste · H9 Récupération des erreurs · H10 Aide & documentation |
| **Charge cognitive** | Nombre d'actions/clics pour l'objectif, densité, lisibilité, mémoire de travail sollicitée |
| **WCAG 2.2 AA** | 1.4.1 (info non portée par la seule couleur) · 1.4.3 (contraste ≥ 4.5:1) · 2.4.7 (focus visible) · 2.5.8 (cibles ≥ 24 px, viser 44 px) · 2.1.1 (tout au clavier) · 3.3.1/3.3.3 (erreurs identifiées + suggestion) · 4.1.3 (messages de statut) |

### 1.2 Sources

- **Existant produit** : lecture directe des gabarits Twig réels (référencés par `fichier:ligne`).
  - `templates/timesheet/week.html.twig` (PG-TMP-01)
  - `templates/timesheet/day.html.twig` (PG-TMP-02/03)
  - `templates/absence/index.html.twig` (PG-ABS-01)
  - `templates/completeness/index.html.twig` (PG-CPL-01)
- **hotones** : captures fournies par le PO dans `project-management/architecture/hotones-ref/` — comparées **là où un écran équivalent existe**.
- **Intrants S19** : `page-inventory.md` (US-080), `parcours-personas.md` (US-081), `page-component-mapping.md` (US-083).

### 1.3 Barème de sévérité

| Sévérité | Définition | Traitement en US-084 |
|----------|------------|----------------------|
| 🔴 **Bloquant** | Empêche/décourage la tâche, ou non-conformité WCAG AA franche | **Doit** être corrigé dans la maquette |
| 🟠 **Majeur** | Friction nette, coût cognitif ou temps significatif | **Doit** être corrigé ou écart justifié |
| 🟡 **Mineur** | Amélioration de confort, cohérence | **Peut** être intégré |

### 1.4 Note liminaire — qualité de l'existant

Les gabarits actuels sont **déjà d'un bon niveau d'accessibilité de base** : cibles tactiles `min-h-11`
(44 px), `inputmode="decimal"`, `aria-label`/`aria-live`, `<caption>` et `scope` sur les tables, indices
clavier explicites. L'audit porte donc surtout sur la **charge cognitive**, la **visibilité de l'état
système**, la **prévention/récupération d'erreurs** et **deux régressions ciblées** (F-S5-4, F-S5-5).

---

## 2. DSH-COLLAB — Dashboard collaborateur → **HORS AUDIT PRODUIT**

**Décision (affinage S20)** : le **dashboard collaborateur riche n'existe pas** dans le produit actuel
(écran typé *Build* au backlog reskin US-085). **Rien à auditer côté produit.** Le point d'entrée actuel
(`/`, `PG-CMN-01 home`) est une home générique, pas un dashboard de contrepartie.

hotones n'offre qu'une **home collaborateur** (`hotones-ref/home-collab.png`), que le PO qualifie de
**référence faible** : elle n'est **pas** un vrai dashboard de saisie. Elle est analysée ci-dessous **au titre
d'inspiration pour la conception d'US-084**, pas comme objet d'audit produit.

### Lecture d'inspiration de la home hotones (réf. faible)

| Pattern hotones | À reprendre (léger) | À NE PAS reproduire |
|-----------------|---------------------|---------------------|
| 3 cartes KPI en tête (« Mes heures cette semaine 30,0h », « Mes projets actifs », « Tâches en cours ») | ✅ Principe **StatCard** (G1) pour la semaine en cours | ❌ Chiffres non actionnables (53 projets actifs, 80 tâches) → surcharge |
| Bloc « Actions rapides » (Saisir mes temps, Voir mes tâches) | ✅ CTA **« Saisir mes temps »** proéminent | ❌ Vert + bleu côte à côte (couleur porteuse de sens) |
| « Mes temps récents » (liste horodatée) | ✅ Liste allégée « mes imputations récentes » | — |
| En-tête profil (avatar + stats) | — | ❌ Placeholder **« TODO : profile »** affiché à l'utilisateur (fuite dev, cf. **F1**) ; stats admin (« Clients 18 ») hors périmètre P1 |
| — | — | ❌ **Aucune contrepartie visible** (avancement projet, complétude perso, solde congés) — or c'est l'attente P1 (parcours US-081) |

➡️ **Reco pour US-084 (DSH-COLLAB)** : dashboard conçu **depuis le parcours P1** (US-081), centré sur la
**contrepartie visible** (complétude perso, solde congés, avancement du projet principal) et un **accès saisie
en 1 clic** — et non sur des compteurs de volume. Composants : **G1 `Ui:StatCard`** + **G4 `Layout:PageHeader`**
(gaps matérialisés + notés).

---

## 3. PG-TMP-01 — Saisie hebdomadaire

- **Route / gabarit** : `/saisie` (`timesheet_week`) · `templates/timesheet/week.html.twig` · **Existant**.
- **Objectif P1** : saisir/valider la semaine en **≤ 2 min** (US-051), sans souris possible, confirmation non intrusive.
- **Comparaison hotones** : capture comparable **disponible** (`hotones-ref/delivery-saisie-tps-collab.png`).

### 3.1 Comparaison hotones ↔ produit

| Aspect | hotones | Produit actuel | Verdict |
|--------|---------|----------------|---------|
| Grille | Projet › **Tâche** (2 niveaux), 7 jours **week-ends inclus** par défaut | Projet/lot × jours ouvrés, **Total** ligne/jour/semaine | Produit **plus lisible** (totaux) ; hotones plus granulaire (tâche) |
| Champs | Pré-remplis à **« 0 »** partout | **Vides** si non saisis | ✅ Produit meilleur (distingue vide ≠ zéro) |
| Totaux courants | **Absents** de la vue | Total ligne + jour + semaine (JS) | ✅ Produit meilleur |
| Actions | Exporter Excel, **Vue Calendrier**, **Dupliquer semaine**, **Afficher week-ends** (toggle) | Dupliquer semaine, Vue jour, Ma synthèse | 🔁 Reprendre de hotones : **toggle week-ends** et **vue calendrier** (patterns utiles) |
| Objectif ≤ 2 min | Pré-remplissage « 0 » = bruit, pas d'aide | Duplication semaine préc., autosave | Produit sur la bonne voie |

**À reprendre de hotones** : toggle « Afficher week-ends » (masquer par défaut) ; entrée « Vue Calendrier » comme alternative.
**Faiblesses hotones à éviter** : pré-remplissage « 0 » ; absence de totaux ; densité brute sans repère d'objectif.

### 3.2 Fiches de constat

| # | Constat | Heuristique / Critère | Sévérité | Recommandation (→ maquette) | Réf. |
|---|---------|-----------------------|----------|------------------------------|------|
| TMP1-01 | Les totaux ligne/jour/semaine affichent **« — »** tant que le JS n'a pas tourné (`week.html.twig:150,159,162`) ; aucun état de chargement explicite. | H1 Visibilité de l'état · 4.1.3 | 🟠 Majeur | Rendre les totaux **côté serveur** (valeur initiale correcte), le JS ne fait que recalculer. Sinon, squelette/« calcul… » explicite. | — |
| TMP1-02 | **Aucun repère d'objectif/complétude** de la semaine sur l'écran de saisie (l'occupation n'existe que dans le dialog « Ma synthèse », `:63`). L'utilisateur ne sait pas d'un coup d'œil si sa semaine est complète. | H1 · H6 Reconnaissance | 🟠 Majeur | Ajouter une **barre de complétude / objectif d'heures** visible en tête (StatCard/ProgressBar), sans ouvrir de dialog. | parcours P1 |
| TMP1-03 | Le **retour de sauvegarde** repose uniquement sur un texte `aria-live` discret (`:183`). Sur autosave, l'utilisateur peut douter que « c'est enregistré ». | H1 · 4.1.3 | 🟡 Mineur | Confirmation **inline non intrusive** par cellule (coche fugace + `aria-live`), pas de modal. | — |
| TMP1-04 | Pas de distinction visuelle **jour ouvré vs week-end/férié** ni indication des jours attendus ; l'utilisateur doit se souvenir de son calendrier. | H6 · H2 | 🟡 Mineur | Marquer visuellement week-ends/fériés (colonne atténuée + libellé), aligné sur `WorkingDaysCalculator`. | — |
| TMP1-05 | **Aucun état d'erreur de validation** matérialisé dans le gabarit (dépend entièrement du JS) : pas de motif d'erreur inline (ex. > 24 h/jour, saisie sur jour de fermeture). | H5 Prévention · H9 Récupération · 3.3.1/3.3.3 | 🟠 Majeur | Concevoir un **état « erreur de validation »** (cellule en erreur + message + focus), à maquetter explicitement (CA-4 US-084). | — |
| TMP1-06 | Largeur des champs `w-14` (~56 px) pour des valeurs comme « 7,5 » ; hauteur OK (`min-h-11`) mais cible **visuelle** étroite. | 2.5.8 · H8 | 🟡 Mineur | Élargir légèrement la cible de saisie (≈ 64–72 px) ; conserver la hauteur 44 px. | — |
| TMP1-07 | Le **code projet** n'est qu'en `title=` (`:129`), invisible au clavier/tactile ; seul le nom est lu. | H6 · 1.4.13 (contenu au survol) | 🟡 Mineur | Afficher le code en libellé secondaire discret (comme la vue jour, `day.html.twig:61`), pas seulement en tooltip. | — |

---

## 4. PG-TMP-02 / PG-TMP-03 — Saisie du jour (mobile)

- **Routes / gabarit** : `/saisie/jour/{date}` (`timesheet_day`) et `/saisie/jour` (`timesheet_day_today`) · `templates/timesheet/day.html.twig` · **Existant**.
- **Objectif P1** : saisie **en mobilité**, tolérante au hors-ligne, ≤ 2 min.
- **Comparaison hotones** : **pas de capture mobile comparable** → audit de l'existant seul (signalé, CA-2).

### 4.1 Fiches de constat

| # | Constat | Heuristique / Critère | Sévérité | Recommandation (→ maquette) | Réf. |
|---|---------|-----------------------|----------|------------------------------|------|
| TMP2-01 | Bon socle mobile : cartes par projet, bannières **hors-ligne** + **resync** (`day.html.twig:33-41`), navigation 44 px. **Point fort à préserver.** | H1 · H3 | ✅ Atout | Reprendre tel quel dans la maquette mobile ; en faire un pattern de référence. | — |
| TMP2-02 | Le **total du jour** est un `<p>` vide tant que le JS n'a pas tourné (`:30`) — même problème que TMP1-01. | H1 · 4.1.3 | 🟠 Majeur | Total du jour rendu serveur + `aria-live` pour les mises à jour. | — |
| TMP2-03 | Aucun **objectif journalier** ni indicateur de complétude du jour. | H1 · H6 | 🟡 Mineur | Afficher l'objectif du jour (ex. « 7 h attendues ») + progression. | parcours P1 |
| TMP2-04 | Le commentaire est un `<input type="text">` (`:79`) mono-ligne pour 500 caractères. | H2 · H8 | 🟡 Mineur | `Form:Textarea` (multi-ligne) pour un commentaire potentiellement long. | — |
| TMP2-05 | Pas d'**état d'erreur** de validation matérialisé (idem TMP1-05). | H5 · H9 · 3.3.1 | 🟠 Majeur | Décliner l'état erreur sur la carte projet (mobile). | — |

---

## 5. PG-ABS-01 — Mes absences

- **Route / gabarit** : `/absences` (`absence_page`) · `templates/absence/index.html.twig` · **Existant**.
- **Objectif P1** : poser un congé en **libre-service, ≤ 1 min**, statut visible sans canal externe.
- **Comparaison hotones** : **pas de capture comparable** → audit de l'existant seul (signalé, CA-2).

### 5.1 Fiches de constat

| # | Constat | Heuristique / Critère | Sévérité | Recommandation (→ maquette) | Réf. |
|---|---------|-----------------------|----------|------------------------------|------|
| ABS-01 | Statuts de demande = **texte + icône + couleur** via `status-badge-*` (`:166-178`). **Conforme F-S5-5 / WCAG 1.4.1.** Point fort. | 1.4.1 · H4 | ✅ Atout | Conserver le pattern badge dans la maquette. | F-S5-5 |
| ABS-02 | Compteurs (`:17-44`) et formulaire de déclaration sont **dissociés** : au moment de choisir les dates, le **solde n'est pas contextualisé** (impact de la demande sur le solde projeté non montré). | H1 · H6 | 🟠 Majeur | Rapprocher **solde projeté ↔ formulaire** : montrer l'impact de la demande en cours (ex. « après validation : 12 j »). | parcours P1 |
| ABS-03 | Dates via `<input type="date">` natif (`:79,90`), **sans vue calendrier** montrant fériés, fermetures d'entreprise et absences déjà posées → risque de conflit non anticipé. | H5 Prévention · H6 | 🟠 Majeur | Utiliser un **`Calendar` / `Form:Datepicker`** (dispo bundle) affichant fériés/fermetures/absences existantes ; prévenir les conflits. | — |
| ABS-04 | Demi-journées : deux cases « Commence le matin » / « Finit l'après-midi » **cochées par défaut** (`:99-108`) — logique du « jour plein » peu évidente. | H2 · H6 | 🟡 Mineur | Reformuler en choix explicite (journée entière / demi-journée) plus lisible. | — |
| ABS-05 | La colonne **« Motif de refus »** (`:157`) est présente pour toutes les lignes et vaut « — » la plupart du temps → table peu dense en information. | H8 Minimalisme | 🟡 Mineur | Afficher le motif **au niveau de la ligne refusée** (détail/expansion), pas en colonne permanente. | — |
| ABS-06 | Avertissement RGPD **« aucune donnée de santé »** bien présent (`:121-125`). Point fort à conserver. | H10 · conformité | ✅ Atout | Conserver dans la maquette. | — |

---

## 6. PG-CPL-01 — Complétude (manager)

- **Route / gabarit** : `/completude` (`completeness_page`) · `templates/completeness/index.html.twig` · **Existant**.
- **Persona dominant** : **manager P2/P3** (dans EPIC-003 mais pas P1). Objectif : repérer un retard en **< 5 s**, relancer en **≤ 3 clics**.
- **Comparaison hotones** : **pas de capture comparable** → audit de l'existant seul (signalé, CA-2).

### 6.1 Fiches de constat

| # | Constat | Heuristique / Critère | Sévérité | Recommandation (→ maquette) | Réf. |
|---|---------|-----------------------|----------|------------------------------|------|
| CPL-01 | **Régression F-S5-5** : la légende (`:37-40`) et les badges de cellule (`:75-78`) portent **texte + couleur mais PLUS d'icône** (les ✅⚠️❌⏳ du lot 1 ont disparu). Le texte évite la violation stricte 1.4.1, mais la décision PO exigeait **texte + icône + couleur**. | 1.4.1 · H4 · **F-S5-5** | 🟠 Majeur | **Rétablir l'icône** dans chaque badge d'état (légende + cellules), conformément à la décision PO ratifiée. | **F-S5-5** |
| CPL-02 | **F-S5-4 non résolu** : repli sur **`userId[:8]`** (identifiant technique tronqué) quand le nom d'affichage manque (`:68`). | 1.1.1 · H2 · **F-S5-4** · **F1** | 🔴 Bloquant | Toujours résoudre en **e-mail** (résolveur tenant-aware) ; **ne jamais** afficher `userId[:8]`. Prévoir la bascule vers nom/prénom sans redesign. | **F-S5-4 / F1** |
| CPL-03 | **Aucun indicateur de synthèse** (combien de collaborateurs en retard / partiels) — le manager doit scanner toute la grille. Gap **G1 `Ui:StatCard`** non exploité. | H1 · H6 · charge cognitive | 🟠 Majeur | Ajouter une **rangée de StatCards** (retard / partiel / soumis) pour un diagnostic < 5 s. | G1 |
| CPL-04 | **Pas de relance en ligne** : la seule action est « Exporter CSV » (`:27`) ; la relance vit sur un autre écran (`/relances`). L'objectif « relance ≤ 3 clics » n'est pas atteignable ici. | H7 Flexibilité | 🟠 Majeur | Prévoir une **action de relance inline** (sélection de lignes → relancer) dans la maquette. | parcours P2/P3 |
| CPL-05 | **Ni tri ni filtre** sur la grille (repérer un retard « < 5 s » impossible si l'équipe est grande). Gap **G3 `Ui:FilterBar`**. | H7 · charge cognitive | 🟡 Mineur | Barre de filtre/tri (état, collaborateur) au-dessus de la grille. | G3 |
| CPL-06 | En scroll horizontal (`overflow-x-auto`, `:49`), la **colonne collaborateur ne reste pas figée** → perte de repère sur beaucoup de semaines/colonnes. | H6 · charge cognitive | 🟡 Mineur | Colonne « Collaborateur » **sticky** dans la maquette. | — |

---

## 7. Ancrage sur les findings recette antérieurs

| Finding | Signification | Écran(s) | Statut au regard de cet audit |
|---------|---------------|----------|-------------------------------|
| **F-S5-4** | Collaborateurs indistinguables (identifiant tronqué) → e-mail résolu tenant-aware | PG-CPL-01 | **Confirmé** (CPL-02) — non résolu dans le gabarit ; recommandation bloquante |
| **F-S5-5** | Statut de complétude = texte + icône + couleur (jamais couleur seule) | PG-CPL-01 | **Régression détectée** (CPL-01) — icône retirée depuis le lot 1 ; à rétablir |
| **F1** | Aucun identifiant technique brut affiché à l'utilisateur | PG-CPL-01 (`userId[:8]`), DSH réf. hotones (« TODO: profile ») | **Confirmé** — rattaché à CPL-02 et à la lecture d'inspiration §2 |
| **R-01** (S12) | 1er clic sur l'onglet « Suivi budgétaire » ne bascule pas | Fiche projet `/projets/{id}` | **Hors périmètre P1** — n'appartient pas au parcours de saisie ; noté pour mémoire, à traiter au reskin EPIC-002 |

> Aucun autre finding recette (`F-S5-*`, `R-*`) ne concerne directement les 4 écrans de saisie P1.

---

## 8. Synthèse priorisée des recommandations (→ US-084)

Regroupées par écran, priorisées par sévérité. **Bloquant/Majeur = à intégrer dans la maquette ou écart justifié** (CA-3 US-084).

### 🔴 Bloquant
- **CPL-02** — PG-CPL-01 : supprimer `userId[:8]`, résoudre en e-mail (F-S5-4 / F1).

### 🟠 Majeur (priorité de conception)
- **DSH-COLLAB** : dashboard de **contrepartie** (complétude perso, solde congés, avancement projet) + saisie 1 clic ; **pas** des compteurs de volume à la hotones.
- **TMP1-01 / TMP2-02** : totaux rendus **serveur** (fin du « — » au chargement).
- **TMP1-02 / TMP2-03** : **repère d'objectif/complétude** visible sur l'écran de saisie (hors dialog).
- **TMP1-05 / TMP2-05** : concevoir explicitement l'**état « erreur de validation »** (saisie hebdo = obligatoire, CA-4).
- **ABS-02** : contextualiser le **solde projeté** au moment de la déclaration.
- **ABS-03** : **vue calendrier** des dates (fériés/fermetures/absences existantes) pour prévenir les conflits.
- **CPL-01** : **rétablir les icônes** des badges d'état (F-S5-5).
- **CPL-03** : **StatCards** de synthèse (retard/partiel/soumis) pour diagnostic < 5 s.
- **CPL-04** : **relance inline** (≤ 3 clics).

### 🟡 Mineur (confort, si budget de conception)
- TMP1-03 (confirmation inline), TMP1-04 (week-ends/fériés), TMP1-06 (largeur champ), TMP1-07 (code projet lisible), TMP2-04 (textarea commentaire), ABS-04 (demi-journées), ABS-05 (motif refus en détail), CPL-05 (filtre/tri), CPL-06 (colonne sticky).

### Patterns hotones retenus (inspiration, à améliorer)
- Saisie hebdo : **toggle week-ends** (masqués par défaut) · entrée **« Vue Calendrier »**.
- DSH-COLLAB : principe **StatCard** en tête · bloc **« Actions rapides »** (CTA saisie) · **« Mes temps récents »**.
- **À ne jamais reproduire** : pré-remplissage « 0 », absence de totaux, placeholder « TODO: profile », stats non actionnables, sens porté par la seule couleur des boutons.

### Gaps composants mobilisés par les maquettes (à matérialiser + noter, dev au S21)
- **G1 `Ui:StatCard`** (DSH-COLLAB, PG-CPL-01) · **G4 `Layout:PageHeader`** (DSH-COLLAB et convention transverse). Secondaires : G3 `Ui:FilterBar` (CPL-05), G6 `Ui:EmptyState`.

---

## 9. Couverture (contrôle DoD US-082)

| Écran du parcours P1 | Audité | Comparaison hotones |
|----------------------|--------|---------------------|
| PG-TMP-01 (saisie hebdo) | ✅ §3 | ✅ capture comparable |
| PG-TMP-02/03 (saisie jour) | ✅ §4 | ⚠️ pas de capture — existant seul |
| PG-ABS-01 (absences) | ✅ §5 | ⚠️ pas de capture — existant seul |
| PG-CPL-01 (complétude) | ✅ §6 | ⚠️ pas de capture — existant seul |
| DSH-COLLAB | ➖ hors audit (inexistant) — §2 | inspiration faible documentée |

> **4/4 écrans existants du parcours P1 audités** ; DSH-COLLAB explicitement hors audit produit (CA-1/CA-5).
> Recommandations priorisées, actionnables, rattachées à une heuristique/critère et une sévérité (CA-3/CA-6).
