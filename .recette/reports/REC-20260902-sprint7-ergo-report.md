# Rapport de recette d'ergonomie — US-066 (EPIC-012, lot 1 Tailwind v4)

- **Session** : REC-20260902-sprint7-ergo
- **Date** : 2026-09-02
- **Périmètre** : recette d'utilisabilité des écrans reskinnés (ADR-0019), parcours saisie / complétude / valorisation / validation
- **Personas testés** : P1 Camille (camille@demo.test — Collaboratrice) · P2 Marc (marc@demo.test — Chef de projet)
- **Environnement** : http://localhost:8080 (FrankenPHP + demo seed), viewport desktop 1280–1519px
- **Méthode** : Claude in Chrome, grille heuristique + touchpoints WCAG 2.2 AA, plan `.recette/plans/sprint-007-plan.yaml`

> **Nature** : recette d'utilisabilité (facilité d'usage), pas un pass/fail fonctionnel. Les anomalies
> fonctionnelles éventuelles suivent la Règle d'Or ; les irritants purement ergonomiques alimentent le
> registre de retours ci-dessous.

> ⚠️ **Caveat CSS périmé (découvert en fin de session)** : le conteneur dev servait un **CSS Tailwind
> compilé désynchronisé de la source** (ex. sidebar rendue claire alors que la source la définit sombre,
> `--color-sidebar: #2a3042`, WCAG-vérifiée US-065). Les captures ci-dessous ont donc été prises sur un
> CSS obsolète. Les findings **structurels / logique / a11y-sémantique** (M1 minutes, M2 totaux, M3
> brouillon/soumis, M4, noms accessibles) **restent valides**. L'**évaluation purement visuelle**
> (couleurs, hiérarchie, profondeur) est à **re-vérifier après `make tailwind` + purge cache + redémarrage
> app** — cf. boucle de dev en fin de rapport.

---

## Décision (CA-1)

**Ergonomie NON déclarée définitivement validée en l'état → plan de correction priorisé.**
Motifs : (1) irritants majeurs sur le parcours critique de saisie (unité en minutes, absence de total
hebdo, ambiguïté brouillon/soumis) ; (2) présentation d'erreur sans-permission non habillée (JSON brut) ;
(3) états peuplés valorisation/validation non rejoués en recette (données de démo non validées) — à couvrir
lors d'une passe complémentaire. Aucun blocage critique (CA-4) : toutes les tâches testées sont réalisables.

---

## Points forts confirmés

| # | Écran | Constat |
|---|-------|---------|
| F+1 | Saisie | **Nommage accessible exemplaire** : chaque cellule expose « Projet — Jour (minutes) » (lecteur d'écran OK). |
| F+2 | Saisie | **Navigation clavier bi-axiale** : `Tab` = cellule suivante, `Entrée` = valider + descendre (colonne). Saisie sans souris réelle. Focus visible (anneau). |
| F+3 | Saisie | **Autosave avec feedback** (« Enregistré. ») + duplication de semaine précédente. |
| F+4 | Saisie | **Panneau « Ma synthèse »** : taux d'occupation + répartition type/projet en heures → répond à l'objectif P1 « comprendre à quoi sert ma saisie ». |
| F+5 | Complétude | **Statuts jamais portés par la seule couleur** (badge texte + % : Soumis/Partiel/Vide/En cours) → WCAG 1.4.1 OK. Légende claire, export CSV. |
| F+6 | Valorisation | **Message HAB clair** : « Le détail des coûts et des taux est réservé au contrôle de gestion » (aperçu vs détail sensible). |
| F+7 | Global | **Nav filtrée RBAC** : Marc voit Validation/Valorisation/Relances ; Camille ne les voit pas (US-063 CA vérifié). |

---

## Retours priorisés (CA-3)

### 🟥 Majeurs (à corriger avant clôture EPIC-012)

- **M1 — Saisie en MINUTES** (`/saisie`, H5/H6). Les cellules attendent des minutes (`type=number`, « 420 » = 7h)
  alors que Camille pense en heures. Conversion mentale à chaque saisie → friction directe sur le critère de
  rejet P1 (« si ça dépasse 2 min, j'arrête »). Incohérence renforcée : « Ma synthèse » affiche des **heures**.
  *Reco* : saisie en heures décimales (7,5) ou HH:MM, ou double affichage h↔min ; a minima suffixe d'unité visible dans/à côté de la cellule.
- **M2 — Aucun total visible sur la grille** (`/saisie`, H1). Pas de total par jour (colonne) ni par semaine.
  Camille ne peut pas vérifier « ai-je bien 35h ? » d'un coup d'œil ; le total n'existe que dans le panneau
  « Ma synthèse » (derrière un clic, et sur une période glissante ≠ semaine affichée). *Reco* : ligne/colonne de totaux + total semaine en pied de grille.
- **M3 — Ambiguïté brouillon vs soumis** (`/saisie`, H1/H3). L'autosave affiche « Enregistré. » et un bouton
  « Enregistrer la semaine » « soumet tout en une fois ». Vocabulaire identique (« Enregistré »/« Enregistrer »)
  pour deux états distincts → risque que l'utilisateur croie avoir soumis alors que le temps reste en brouillon.
  *Reco* : distinguer clairement « Brouillon enregistré » vs « Semaine soumise pour validation » + indicateur d'état de la semaine.
- **M4 — Erreur sans-permission en JSON brut** (`/valorisation` sans `VIEW_PROJECT_FINANCIALS`, H9 + sécu).
  Rendu = `{"error":"Permission refusée : view:project_financials"}` en texte nu, sans page habillée, sans
  navigation de retour, et **fuite du slug de permission interne**. Route web (pas `/api`) → doit rendre une
  page HTML d'erreur habillée. → **anomalie**.
  **✅ CORRIGÉ (TDD, 2026-09-02)** : `AccessDeniedExceptionListener` négocie le format (HTML habillé pour le
  web via `templates/error/403.html.twig`, JSON générique pour l'API), slug journalisé côté serveur mais
  jamais renvoyé. Test `tests/Functional/Web/ValuationDashboardAccessTest.php` (@regression) + probe ajusté ;
  vérifié navigateur (page 403 habillée avec sortie, `leak=false`) ; **suite 419 tests verte**, `make ci` OK.

### 🟧 Mineurs (backlog lots suivants)

- **m1 — Feedback autosave discret** : « Enregistré. » en petit texte gris en bas → peu perceptible.
- **m2 — Double item de menu actif** : sur `/validation`, « Saisie » **et** « Validation » apparaissent actifs (état actif non exclusif).
- **m3 — Période « Ma synthèse » ≠ semaine affichée** (10/08→02/09 vs 31/08→06/09) → confusion possible.
- **m4 — En-têtes de semaine techniques** : « Sem. 2026-08-10 » (complétude) vs « Semaine du 10/08 » plus lisible pour un pilote (H2).
- **m5 — Boucle pilotage→action non inline** : pas de relance déclenchable depuis une cellule « Vide » de la complétude (écran `/relances` séparé) (H7).

### 🔎 Sécurité / défense en profondeur

- **V1 — `/validation` accessible en direct à un Collaborateur (200)** alors que la nav le masque
  (`validate:time`). Écran scopé « vos projets » (vide pour un Collaborateur, pas de fuite) + mutation déjà
  gardée → pas de faille réelle, mais écart de défense en profondeur et incohérence avec `/valorisation`.
  **✅ CORRIGÉ (TDD, 2026-09-02)** : `ensureCan(VALIDATE_TIME)` ajouté à `ValidationPageController` → 403
  habillée pour les non-habilités. Test `ValidationPageAccessTest` (@regression). `make ci` vert (421 tests).
  - `/completude` accessible à tous est **volontaire** (nav `perm:null` ; le contrôleur dégrade via
    `can(VIEW_TEAM_COMPLETENESS)` équipe↔perso) → pas un écart.

---

## Couverture par parcours

| Parcours | Persona | État | Verdict |
|----------|---------|------|---------|
| PC-SAISIE (`/saisie`) | P1 | peuplé (saisie réelle jouée) | ✅ testé en profondeur — M1/M2/M3/m1 |
| PC-COMPLETUDE (`/completude`) | P2 | peuplé (statuts démo) | ✅ testé — F+5, m4, m5 |
| PC-VALORISATION (`/valorisation`) | P1 (403) + P2 | **vide** (aucune imputation validée en démo) | 🟡 partiel — F+6, M4 ; **état peuplé non rejoué** |
| PC-VALIDATION (`/validation`) | P2 | **vide** (aucune imputation en attente) | 🟡 partiel — état vide OK, m2 ; **validation en lot non rejouée** |

> Les états peuplés valorisation/validation exigent le flux complet saisie→soumission→validation→valorisation
> async. Comportement fonctionnel attesté par les suites US-055 / US-060. **Passe complémentaire recommandée**
> avec un jeu de données validées pour couvrir : bandeau `missing_rate`, audit trail du taux (`<details>`),
> validation/refus en lot avec motif.

---

## Règle d'Or — anomalie détectée

- **ANO-1 (M4)** — classe : **interaction / sécurité (mishandling exceptional conditions, OWASP #7)**.
  - Attendu : navigation web sans permission → page d'erreur HTML habillée (403), message métier, sans exposer le slug de permission.
  - Observé : corps JSON `{"error":"Permission refusée : view:project_financials"}` rendu en brut.
  - Test de régression à générer (via `/qa:fix` ou `/qa:tdd`) : test fonctionnel — `GET /valorisation` en tant que
    Collaborateur → réponse HTML 403 (pas `application/json`), sans le slug `view:project_financials` dans le corps.
  - Registre : à ajouter dans `.recette/regression/registry.yaml` (@regression).

---

## Suites recommandées

1. ~~`/qa:fix` sur **ANO-1 (M4)**~~ → **✅ FAIT** (page 403 habillée + test @regression, `make ci` vert).
2. Arbitrage produit sur **M1/M2/M3** (saisie) — ce sont des choix d'ergonomie structurants du parcours le plus utilisé (80 % des utilisateurs).
3. **Passe complémentaire** valorisation/validation sur données peuplées, **sur CSS rebuildé** (cf. caveat).
4. Vérifier **V1** (contrôle d'accès direct des routes de pilotage).
5. Audit dark theme (partiellement fait) → rattaché à **US-065** (S8).

## Boucle de dev front (piège rencontré — à documenter, reliquat S7)

Le conteneur tourne en **`APP_ENV=dev` mais `debug=false`** + **FrankenPHP worker**. Conséquences pour voir
une modif front en local :

1. **CSS/tokens ou classes Tailwind** modifiés → `make tailwind` (le CSS compilé `var/tailwind` n'est pas régénéré à la volée).
2. **Template Twig** modifié → purge cache (`docker compose exec app php bin/console cache:clear`) car Twig ne recompile pas avec `debug=false`.
3. **Code PHP** (listener, contrôleur…) modifié → **redémarrer le conteneur** (`docker compose restart app`) : le worker garde les classes en mémoire.

> Effet de bord PHPStan : un `cache:clear --no-warmup` supprime `var/cache/dev/App_KernelDevDebugContainer.xml`
> dont l'extension phpstan-symfony a besoin → régénérer avec `APP_DEBUG=1 … cache:warmup`.
