# Module Complétude de saisie (US-058)

Tableau de bord de pilotage de la complétude : taux de saisie **par collaborateur et par semaine**
en une vue, avec repérage visuel des retards. Outil principal de l'objectif `OBJ-1` (complétude
≥ 90 % à J+2) et déclencheur des relances (US-056). `EF-TMP-24`.

## Modèle & calcul

| Élément | Rôle |
|---------|------|
| `Domain\Completeness\WeekCompleteness` | Complétude d'une (collaborateur, semaine) : jours attendus, jours saisis, `rate()`, état. |
| `Domain\Completeness\CompletenessState` | `submitted` / `partial` / `empty_late` / `in_progress`. |
| `Application\Completeness\CompletenessGrid` | Construit la grille sur N semaines glissantes. |
| `Application\Completeness\CompletenessScope` | Résout le périmètre (soi-même vs équipe). |

**Calcul de l'état** (par collaborateur × semaine, Lun-Ven) :
- `jours attendus` = 5 − jours d'**absence validée** dans la semaine.
- `jours saisis` = jours distincts avec au moins une imputation.
- `submitted` si saisis ≥ attendus (ou rien d'attendu) ; sinon selon le délai **J+2** : `in_progress`
  si le délai n'est pas atteint, sinon `empty_late` (0 saisi) ou `partial`.

## Périmètre (RBAC — CA-5)

`CompletenessScope` : un collaborateur ne voit que **lui-même** ; le périmètre **équipe** exige
`VIEW_TEAM_COMPLETENESS` (403 sinon via l'API). L'écran s'adapte à l'habilitation sans erreur.
> Simplification : « équipe » = tous les collaborateurs du tenant. Un périmètre managérial/BU réel
> (via la hiérarchie US-010) est un raffinement ultérieur.

## API & Web

| Opération | Effet |
|-----------|-------|
| `GET /api/completude` | Grille JSON (périmètre soi-même ; `?scope=team` → 403 sans habilitation). |
| `GET /api/completude/export` | Export **CSV** protégé contre l'injection (champ `=/+/-/@` préfixé d'une apostrophe). |
| `GET /completude` | Écran : grille couleur 4 états + légende, lien d'export, état vide explicite. |

Erreurs : **401** (anonyme), **403** (`scope=team` non habilité).

## Tests

- `CompletenessGridTest` (états soumise/partielle/vide/en cours, semaine entièrement absente).
- `CompletenessScopeTest` (soi-même / équipe / 403).
- `CompletenessApiTest` (401, grille self, 403 team, CSV), `CompletenessPageTest` (401, périmètres).

## Limites connues / suite

- **Performance** : la grille interroge par (collaborateur, semaine) — acceptable en itération 1 ;
  pour de grandes équipes, batcher par collaborateur sur toute la plage ou introduire un cache
  applicatif (rafraîchi ~15 min) comme prévu par la story.
- **J+2 ouvré** : approximé par un offset calendaire ; l'exclusion des jours fériés du tenant viendra.
- **Blocage demi-journée** : la complétude compte un jour saisi dès qu'une imputation existe.
- **Relance directe depuis une cellule** (CA-2) : câblée avec US-056 (relances).

## Revue (T-058-06) — findings traités

Revue `symfony-reviewer` (axe performance) — **« module architecturalement solide »** (hexagonal,
immutabilité, RBAC, anti-injection CSV OK). Findings :

- **[Élevé — accepté/documenté] N+1** : `CompletenessGrid::build` interroge par (collaborateur,
  semaine) → ~800 requêtes pour 100 collaborateurs × 4 semaines. **Accepté en phase 1** (dashboard
  consulté ponctuellement, sous le SLA ≤ 3 s aux volumes actuels). **Phase 2 prioritaire** : requêtes
  **batch** (`findValidatedOverlappingForUsers`, `TimeEntry` par plage globale, reformatage en
  mémoire) et/ou cache applicatif ~15 min — comme prévu par la story.
- **[Faible — différé] Affichage** : la grille montre l'identifiant tronqué plutôt que le **nom** du
  collaborateur. À traiter dans la **phase de conception UX/UI** (consigne PO) via un map
  `userId → nom`.

**Conclusion : GO** — architecture saine, RBAC et anti-injection conformes ; la dette de performance
N+1 est connue, mesurée acceptable en l'état et planifiée en phase 2.
