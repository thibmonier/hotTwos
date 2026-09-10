# US-080: Référentiel exhaustif des pages cibles

## Métadonnées
- **ID**: US-080
- **EPIC**: EPIC-013 (Recensement & conception UX)
- **Sprint**: 19
- **Statut**: 🟢 Ready
- **Points**: 5
- **Persona**: Tous (P1–P6)
- **Créé le**: 2026-09-10
- **Mis à jour**: 2026-09-10 (affinage S19)

## Traçabilité
- **Implémente**: EPIC-013 (C1 — Recenser les pages cibles)
- **Dépend de**: ADR-0023 (socle tailsfadmin adopté — chantier préalable US-086)
- **Alimente**: US-081 (parcours par persona), US-083 (mapping composants), US-085 (backlog reskin)

## User Story

**En tant que** Product Owner / concepteur UX,
**je veux** un référentiel exhaustif de toutes les pages cibles du produit (pages existantes et pages à créer) organisé par module, avec pour chacune : objectif, persona(s) primaire(s), informations affichées, actions disponibles et données sources,
**afin de** disposer d'une source de vérité unique sur le périmètre écrans — socle de la cartographie des parcours, du mapping composants et du backlog de refonte.

## Contexte (Conversation)
HotOnes compte aujourd'hui ~30 écrans construits au fil du développement (walking skeleton + EPICs métiers),
sans vision d'ensemble formalisée. Certains écrans sont orphelins (sans persona clair), d'autres sont des
doublons fonctionnels, et les écrans des modules futurs (CRM, RH, recrutement…) ne sont nulle part listés.

Ce référentiel — livré dans `project-management/architecture/page-inventory.md` — recense toutes les routes
applicatives existantes ET les pages cibles manquantes (marquées « À créer »), organisé par module
(Auth, Temps, Projets, Finance, Staffing, Pilotage, CRM, RH, Référentiels, Administration…).
Il constitue le socle de US-081 (parcours par persona), US-083 (mapping composants tailsfadmin)
et US-085 (backlog reskin).

Décisions d'affinage (S19) — arrêtées :
- **Source de vérité des routes existantes** : `make console c="debug:router"` sur `main` (post-US-086) ; recouper avec `templates/**/*.html.twig` pour les vues sans route directe (modales, volets).
- **Taxonomie de modules figée** : Auth/Common, Temps, Projets, Finance, Staffing, Pilotage, Référentiels, Administration ; modules futurs (CRM, RH, Recrutement) présents avec statut « À créer ».
- **Pages transverses incluses** : Auth (connexion, mot de passe oublié), erreurs 403/404/500, profil utilisateur, tableau de bord d'accueil → section « Auth/Common ».
- **Format retenu** : tableau Markdown, colonnes (ID, Module, Nom, Route, Statut, Persona primaire, Objectif, Informations affichées, Actions, Données sources).
- **Accessibilité WCAG 2.2 AA** : la colonne « Objectif » signale les contraintes d'accessibilité des écrans à fort enjeu (ex. saisie P1 : cibles ≥ 44 px, pas de captcha temporel).
- **Hors périmètre** : routes Symfony internes (`_profiler`, `_wdt`) ; routes API Platform (documentées via OpenAPI).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : couverture exhaustive des pages existantes
```gherkin
GIVEN l'ensemble des routes applicatives actuellement définies dans le projet
WHEN le PO consulte le livrable `page-inventory.md`
THEN 100 % des routes hors routes internes Symfony sont listées dans le référentiel
  AND chaque entrée porte les colonnes obligatoires : ID, Module, Nom, Route, Statut, Persona primaire,
      Objectif, Informations affichées, Actions, Données sources
  AND le champ Statut vaut « Existant » pour toute page déjà implémentée
```

### CA-2 (Nominal) : identification des pages à créer
```gherkin
GIVEN les modules métiers prévus par les EPICs (Temps, Projets, Finance, Staffing, CRM, RH, Pilotage…)
WHEN le PO parcourt le référentiel
THEN toute page cible non encore implémentée est présente avec le statut « À créer »
  AND sa persona primaire et son objectif sont renseignés même pour les pages futures
  AND les pages à créer sont distinguées visuellement des pages existantes (colonne Statut ou section dédiée)
```

### CA-3 (Alternatif) : regroupement par module lisible
```gherkin
GIVEN le référentiel `page-inventory.md`
WHEN un membre de l'équipe cherche toutes les pages du module Finance
THEN il peut localiser ces pages sans lire l'intégralité du document
  AND le nombre de pages par module est indiqué (résumé en en-tête ou table des matières)
```

### CA-4 (Alternatif) : pages transverses incluses dans un module dédié
```gherkin
GIVEN les pages transverses (connexion, mot de passe oublié, tableau de bord d'accueil,
      profil utilisateur, pages 403/404/500)
WHEN le référentiel est constitué
THEN ces pages figurent dans une section « Auth » ou « Common » dédiée
  AND elles mentionnent le layout applicable (layout auth ou layout admin tailsfadmin)
```

### CA-5 (Erreur) : route existante absente du référentiel
```gherkin
GIVEN une route applicative existante absente du référentiel
WHEN le PO effectue la revue de validation
THEN il identifie la page manquante et retourne la story en révision
  AND la story ne peut pas être marquée Done tant que la couverture est inférieure à 100 %
```

### CA-6 (Erreur) : champ obligatoire manquant
```gherkin
GIVEN une ligne du référentiel dont le champ « Persona primaire » ou « Objectif » est vide
WHEN le PO valide le livrable
THEN il refuse la validation et demande la complétion du champ
  AND aucun écran ne peut être listé sans persona primaire identifié
```

## Notes sur les livrables
- **Livrable** : `project-management/architecture/page-inventory.md`
- **Format** : tableau Markdown ; une ligne = une page ; colonnes : ID, Module, Nom, Route, Statut (`Existant` / `À créer`), Persona primaire, Objectif, Informations affichées, Actions, Données sources.
- **Accessibilité** : la colonne Objectif signale les exigences WCAG 2.2 AA spécifiques (ex. formulaires de saisie P1 : cibles ≥ 44 px, focus visible, labels explicites, pas d'action critique au survol uniquement).
- **Hors périmètre** : routes Symfony internes (`_profiler`, `_wdt`) ; routes API Platform (documentées séparément via OpenAPI).
- **Validation** : revue PO en fin de story ; fichier versionné sur la branche de sprint 19.

## Definition of Ready
- [x] Description INVEST (livrable documentaire borné ; persona « Tous » via PO ; estimé 5 pts)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Format du livrable défini (tableau Markdown, colonnes identifiées)
- [x] **Source de vérité arrêtée** : `debug:router` sur `main` + scan `templates/` (affinage S19)
- [x] **Taxonomie de modules figée** (Auth/Common, Temps, Projets, Finance, Staffing, Pilotage, Référentiels, Administration, + CRM/RH/Recrutement « À créer »)
- [x] **Condition d'entrée** : aucune (US-080 est la 1ʳᵉ story du sprint — **démarrable immédiatement**, seul ADR-0023/US-086 requis, livré)
- [x] Validation INVEST : Independent ✓ (aucune US à compléter pour démarrer, seul ADR-0023 requis) / Negotiable ✓ (format adaptable) / Valuable ✓ (socle de toute la conception) / Estimable ✓ (5 pts — inventaire exhaustif des routes) / Sized ✓ (≤ 8 pts) / Testable ✓ (couverture 100 % vérifiable)
- **DoR : ✅ LEVÉE — prête pour `/sprint:dev`**

## Definition of Done
- [ ] `project-management/architecture/page-inventory.md` créé et commité
- [ ] 100 % des routes applicatives existantes recensées, statut « Existant »
- [ ] Pages cibles manquantes identifiées, statut « À créer »
- [ ] Champs obligatoires complets sur toutes les lignes (aucune case vide)
- [ ] Revue et validation PO formalisées (commentaire de PR ou annotation dans le fichier)
- [ ] Branche versionnée, PR mergée sur la branche de sprint 19
