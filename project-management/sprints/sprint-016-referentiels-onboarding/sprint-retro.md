# Rétrospective — Sprint 16 (Référentiels & mise en route)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-07 |
| Format | Starfish ⭐ |
| Sprint | 16 — Référentiels de paramétrage & mise en route (EPIC-001) |
| Points livrés | 16 / 16 |
| Contexte | Solo dev (cérémonies = jalons documentaires) |

## Directive Fondamentale
> Chacun a fait de son mieux compte tenu de ce qu'il savait, de ses compétences, des ressources et de la situation. — Norman Kerth

## Rappel du Sprint
Goal atteint (16/16). 4 stories EPIC-001 mergées (#89–#92). 645 → **672 tests**. 5 migrations (+ RLS).

## ⭐ Observations

### 🟢 CONTINUER
- **Affinage ancré sur le code avant dev** (exploration S16) : périmètres recadrés = zéro re-spécification de déjà-livré, estimations tenues.
- **Refactor DRY d'US-012** payant : un `WorkingDaysCalculator` unique remplace 4 duplications ; les fériés se propagent partout gratuitement.
- **Ordre d'exécution respecté** (012→013→020→019) : US-020 a pu instrumenter les écritures d'US-012/013, US-019 provisionner leurs défauts.
- **`make cs-fix` + `rector-fix` avant chaque commit** (action rétro S15) : moins d'allers-retours CS/Rector.

### 🟡 COMMENCER
- **Recenser en amont les tests atteignant une page impactée** : chaque nouvelle table lue par un chemin partagé (occupation, dashboard, config) doit être ajoutée aux SchemaTool concernés — anticiper plutôt que corriger sur échec CI.

### 🔴 ARRÊTER
- **Sous-estimer l'effet de bord d'un service transverse** : brancher `authorizer->can` dans le `HomeController` a cassé `AuthWebTest` (schéma minimal sans `Role`). Un contrôleur « socle » touché a un rayon d'impact large.

### ⬆️ PLUS DE
- **Idempotence explicite** (US-019 `tenant:init`) : rejouable sans doublon, testée.
- **Ports append-only prouvés par réflexion** (US-020) : verrouille l'invariant d'immuabilité.

### ⬇️ MOINS DE
- **Cascade SchemaTool** : le piège s'est répété (US-012 : +8 tests ; US-020 : +3 ; US-019 : AuthWebTest). Récurrent → checklist d'impact au moment d'ajouter un read partagé.

## Thèmes & analyse

### Thème — Rayon d'impact des chemins partagés · Votes ●●●●
**Problème** : refactor (jours ouvrés) et enrichissement (checklist home, audit) touchent des chemins
exercés par de nombreux tests fonctionnels → cascades de 500 (SchemaTool) et de deps de constructeur.
**5 Pourquoi** : SchemaTool liste les entités **par test** → tout nouveau read partagé oblige à mettre à
jour chaque test l'atteignant. **Solution** : au design, lister les points d'entrée touchés et leurs
tests ; ou envisager un helper de schéma partagé (hors périmètre immédiat).

## 🎯 Actions Sprint 17
1. **Checklist d'impact « read partagé »** : avant d'ajouter une lecture dans un service transverse
   (home, occupation, config), lister les tests fonctionnels l'atteignant et leurs schémas. — Moyenne.
2. **Évaluer un `SchemaTool` mutualisé** (trait de test listant le schéma complet) pour réduire la
   cascade — spike court en S17. — Basse.
3. **Trancher EF-REF-7/9** (calendriers différenciés, fermeture entreprise) au planning S17. — PO.

## Suivi actions précédentes
| Sprint | Action | Status |
|--------|--------|--------|
| S15 | `.gitignore compose.override.yaml` | ✅ Fait (S16) |
| S15 | `make cs-fix`+`rector-fix` avant commit | ✅ Adopté |
| S15 | Coloration escalade courbe | ✅ Fait (#85) |
| S10→ | MAILER staging | ❌ Reconduit |

## Vélocité
S9→S16 : 21 / 11 / 23 / 16 / 21 / 10 / **16**. Retour à un rythme nominal (16/22).

## ROTI
Solo dev — **4/5** (bonne exécution ; cascades SchemaTool évitables).

## Prochaine étape
EPIC-001 : reste **US-017** (+ EF-REF-7/9). → `/workflow:start 017`.
