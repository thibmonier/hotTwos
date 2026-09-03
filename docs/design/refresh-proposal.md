# Proposition de refresh design — au-delà du portage Tailwind

**Statut :** proposition (à trancher par un humain sur les points §5).
**Périmètre :** direction visuelle pour le lot 2 (rafraîchissement), après le portage 1:1 Bootstrap→Tailwind
(ADR-0019, lot 1, 13 écrans). Ne remet pas en cause les tokens de `design-system.md` ni le `@theme` de
`assets/styles/tailwind.css` — les complète sur un point identifié en écart (§3.1) et propose des
compositions plus abouties avec les tokens existants.
**Démonstrateur :** `templates/valuation/index.html.twig` (voir §6).

---

## 1. Constat de départ

Le lot 1 a fidèlement porté Bootstrap/Skote vers Tailwind v4 : mêmes structures, mêmes tokens, aucune
régression fonctionnelle ni a11y (WCAG 2.2 AA déjà vérifié, cf. `design-system.md` §5). C'est un socle
solide — mais un **portage 1:1 reproduit aussi les limites visuelles de l'original** : composition plate,
densité uniforme, hiérarchie faible. L'objectif ici n'est pas de réviser les tokens (contraste-vérifiés,
ne pas y toucher) mais la **composition** : comment ces tokens sont assemblés à l'écran.

## 2. Diagnostic par catégorie

### 2.1 Hiérarchie visuelle
Tous les blocs de contenu ont le même traitement : `rounded-md border border-border bg-surface p-4`. Une
carte KPI, un état vide, une table, un panneau d'audit trail — tout se ressemble. Sur un dashboard financier
(`valuation/index`), le chiffre le plus important (CA reconnu) n'est pas visuellement prioritaire par
rapport aux trois autres indicateurs : même taille de carte, même poids de police (`text-2xl` partout).

### 2.2 Profondeur / séparation du fond
Aucune carte n'a d'ombre : uniquement `border-border` (un gris très clair, `#eff2f7`) sur un fond `--color-bg`
(`#f6f7f9`) à peine plus foncé. Le contraste carte/fond repose donc presque uniquement sur la bordure — la
page paraît plate. Or les tokens `--shadow-sm` / `--shadow-md` existent bien dans `design-system.md` (§3.4)
mais **n'ont jamais été reportés dans `@theme`** (`assets/styles/tailwind.css`) : les classes `shadow-sm`
utilisées ailleurs dans le projet retombent donc sur l'échelle d'ombre par défaut de Tailwind, non
thémée — un vrai écart au principe « tokens = source unique » (règle 11 / ADR-0019 §2).

### 2.3 Densité
La densité est uniforme sur tous les écrans (`p-4`, `gap-3`, `px-4 py-3`), qu'il s'agisse d'une grille de
saisie dense (`timesheet/week`, où c'est justifié — cf. `design-system.md` §3.2 « densité compacte ») ou
d'un dashboard de synthèse (`valuation/index`, `home/index`), qui gagnerait à respirer davantage pour
mettre en avant les chiffres clés.

### 2.4 Iconographie
Incohérente selon les écrans : `valuation/index` utilise de vraies icônes SVG (style *outline*, trait 2px,
cohérent) pour les cartes KPI, mais la sidebar (`base.html.twig`) affiche un point générique `•` en guise
d'icône de navigation pour toutes les entrées de menu. C'est le décalage le plus visible entre écrans.
*(Non traité dans le démonstrateur — composant partagé, cf. §7 « hors scope ».)*

### 2.5 Retour d'état (vide / erreur)
Les états vides (`Aucun projet actif…`, `Aucune donnée de saisie…`) sont tous un simple paragraphe gris
dans un cadre bordé — fonctionnels mais peu engageants, aucune différenciation visuelle entre un état
« normal, rien à afficher pour l'instant » et un état qui appelle une action.

### 2.6 Ce qui fonctionne déjà et doit être préservé
- Le motif badge « bordé » (`.status-badge-*`) : contraste 4.5:1+ garanti, ne pas passer en fond plein.
- Les cibles tactiles ≥44px (`min-h-11`) déjà posées sur les contrôles interactifs.
- Le focus visible (`--color-focus`, anneau 3px) déjà cohérent partout.
- La palette sémantique (primary/success/warning/danger/info) : fonctionnellement correcte, on joue sur sa
  *composition* (opacité, taille, poids), pas sur ses valeurs.

---

## 3. Direction proposée

### 3.1 Combler l'écart de tokens (prérequis, fichier partagé)
Reporter `--shadow-sm` et `--shadow-md` de `design-system.md` (§3.4) dans `@theme`
(`assets/styles/tailwind.css`), en surchargeant l'échelle native de Tailwind (syntaxe v4 : `--shadow-*`
dans `@theme` remplace directement les utilitaires `shadow-sm`/`shadow-md`). Aucune nouvelle valeur
inventée — on branche un token déjà spécifié et validé, qui manquait à l'implémentation.

### 3.2 Hiérarchiser au lieu d'uniformiser
- Identifier **un** indicateur « vedette » par écran de synthèse (ici : CA reconnu) et lui donner plus de
  poids : carte teintée (`bg-primary/5 border-primary/20` — modificateur d'opacité Tailwind natif sur le
  token `--color-primary`, pas un nouveau token), chiffre plus grand (`text-3xl md:text-4xl` au lieu de
  `text-2xl`).
- Les cartes secondaires restent neutres (`bg-surface`) mais gagnent `shadow-sm` pour se détacher du fond.
- Icon-chips agrandis (`h-10 w-10`, `rounded-lg`) et cohérents entre cartes vedette/secondaires.

### 3.3 Densité contextuelle
Conserver la densité compacte des écrans de saisie/grilles (elle est justifiée et déjà documentée) ;
donner plus d'air aux dashboards de synthèse : `gap-3` → `gap-4`/`gap-5` entre cartes, `mb-4` → `mb-6`
entre sections, ajout d'un léger sous-titre de section pour ponctuer la lecture (« Indicateurs clés »,
« Détail des opérations ») sans réintroduire un h1 visible dupliqué avec le topbar.

### 3.4 Feedback plus lisible
Réserver `shadow-sm` aux blocs de contenu réel (cartes KPI, tables), pas aux états vides — pour que ces
derniers restent visuellement en retrait (c'est le comportement recherché), tout en clarifiant leur
message (garder le texte, pas d'invention d'illustration qui alourdirait le CSS sans justification produit).

### 3.5 Avant / après (dashboard valorisation)

| | Avant (portage 1:1) | Après (direction proposée) |
|---|---|---|
| CA reconnu | Carte identique aux 3 autres, `text-2xl`, fond blanc plat | Carte vedette teintée `primary/5`, `text-3xl md:text-4xl`, `shadow-sm` |
| Cartes secondaires | `border` seule, aucune ombre | `border` + `shadow-sm` (détachement du fond) |
| Icon-chips | 32px (`h-8 w-8`), coin `rounded` (4px) | 40px (`h-10 w-10`), coin `rounded-lg` |
| Espacement inter-cartes | `gap-3` (12px) | `gap-4` (16px), `mb-6` entre sections |
| Barre de progression | `h-1.5`, pas de repère de section | `h-2`, sous-titre de section au-dessus |
| Tableau audit trail | `border` seule | `shadow-sm` sur le conteneur |

---

## 4. Contraintes non négociables — respectées

- **WCAG 2.2 AA** : aucune valeur de couleur touchée (on ne fait que composer les tokens existants avec
  opacité/taille/ombre) ; les ratios vérifiés dans `design-system.md` §5 restent valides à l'identique.
  Les ombres n'affectent pas le contraste texte, seulement la perception de profondeur.
- **Cibles ≥44px** : aucun contrôle interactif retouché dans le démonstrateur (les cartes KPI ne sont pas
  cliquables) ; les `min-h-11` existants ailleurs ne sont pas modifiés.
- **Tokens = source unique** : aucun hex en dur ajouté. Les seules valeurs nouvelles (`--shadow-sm/md`)
  sont reprises telles quelles de `design-system.md`, pas inventées. Les teintes de carte vedette utilisent
  le modificateur d'opacité Tailwind sur `--color-primary` (`bg-primary/5`), pas une nouvelle couleur.
- **Poids CSS** : Tailwind v4 est purgé en JIT (`@source "../../templates"`) — les classes ajoutées sont
  des utilitaires déjà dans le vocabulaire du projet (`shadow-sm`, `rounded-lg`, `gap-4`…), coût marginal
  quasi nul, aucune police/icon-font supplémentaire chargée.

## 5. Points de décision à trancher (goût / marque)

1. **Teinte de la carte vedette** : `bg-primary/5` (très subtil) proposé — à valider si ce n'est pas trop
   discret, ou si une bordure plus marquée (`border-primary/40`) serait préférable.
2. **Ampleur du agrandissement du chiffre vedette** : `text-3xl`/`text-4xl` proposé — à confirmer que ça ne
   déséquilibre pas la grille sur les profils qui voient les 4 cartes (contrôle de gestion, `canViewCost`).
3. **Icônes de la sidebar** (`•` → SVG outline) : diagnostiquée comme incohérence la plus visible, mais
   **non traitée ici** car composant partagé à tous les écrans — nécessite une décision d'ensemble (jeu
   d'icônes à retenir : Lucide/Heroicons, cf. règle `12-context-management` — aucune lib d'icônes n'est
   encore installée dans le projet, à choisir en cohérence avec l'inline SVG déjà en usage).
4. **Généralisation `shadow-sm`** : une fois validée sur l'écran démonstrateur, l'appliquer aux autres
   écrans de type « dashboard/liste » (`home`, `project/index`, `completeness`, `organization`, `pricing`)
   dans un lot séparé, ou l'étendre à toutes les cartes `bg-surface` du projet d'un coup ?

## 6. Écran démonstrateur

`templates/valuation/index.html.twig` — dashboard financier, représentatif (cartes KPI + tableau détaillé +
états conditionnels par permission). Modifications : voir diff — carte CA reconnu mise en avant, ombres
`shadow-sm` sur les cartes/tableau, icon-chips agrandis, espacements desserrés, sous-titres de section.

Fichier partagé touché *a minima* et signalé : `assets/styles/tailwind.css` (§3.1, ajout de
`--shadow-sm`/`--shadow-md` dans `@theme` — sans quoi `shadow-sm` utilisé dans le démonstrateur retomberait
sur l'échelle Tailwind par défaut, non tokenisée).

## 7. Hors scope (ce lot)

- Sidebar / navigation (icônes, cf. §5.3) — composant partagé, décision d'ensemble nécessaire.
- Thème sombre du démonstrateur — les tokens dark existent déjà et s'appliquent automatiquement
  (`bg-primary/5` fonctionne aussi en dark, `--color-primary` étant retinté), pas de vérification visuelle
  poussée faite ici.
- Déploiement aux 12 autres écrans — plan indicatif au §5.4, à confirmer écran par écran.

---

**Date :** 2026-09-02
**Auteur :** UI Designer (agent), démonstrateur de direction pour le refresh post-lot-1 (ADR-0019).
