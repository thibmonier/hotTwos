# Synthèse d'activité (US-059)

Synthèse en lecture, accessible en 1 clic depuis l'écran de saisie : répartition du temps par projet
et par type, taux d'occupation, planning à venir (dégradé). Contrepartie visible de la saisie →
adhésion (RSQ-1). `EF-TMP-26`, `EF-TMP-27`.

## Modèle & calcul

| Élément | Rôle |
|---------|------|
| `Domain\Activity\ActivityReport` | Objet de lecture : période, `byProject`, `byType`, minutes production/absence/attendues, `occupationRate()`, `isEmpty()`. |
| `Domain\Activity\ProjectActivity` | Part d'un projet : id, libellé, minutes. |
| `Domain\Activity\ActivityType` | `production` / `absence`. |
| `Application\Activity\ActivitySummary` | Construit le rapport sur une période glissante alignée sur les semaines. |

**Calcul** :
- Période = `weeks` semaines glissantes (défaut 4), du lundi de la 1ʳᵉ semaine à aujourd'hui.
- **Statuts inclus** : VALIDÉ **et** SOUMIS (`ValidationStatus::VALIDATED`/`PENDING`) ; les **refusés
  sont exclus** (RG-TMP-4).
- **Type** : `absence` si l'imputation porte sur le projet système « Absence » (code `ABSENCE`), sinon
  `production`. Le modèle ne porte pas de taxonomie d'activités plus fine (limitation — cf. suite).
- **Taux d'occupation** = production imputée / temps ouvré attendu (jours ouvrés × 7 h). Borné à 0 si
  rien n'est attendu.

## Périmètre (RBAC — CA-4)

**Strictement personnel** : l'API sert toujours le collaborateur courant. Une requête `?user_id=`
visant un **autre** collaborateur → **403** (même sur un projet partagé). Aucune permission spéciale
(chacun voit sa propre activité).

## API & Web

| Opération | Effet |
|-----------|-------|
| `GET /api/activity-summary` | Synthèse JSON du collaborateur courant (`?weeks=` 1–12 ; `?user_id=` d'autrui → 403). État vide explicite (pas de 500). `planning.available=false` tant qu'US-037 est absente. |
| Panneau `/saisie` | « Ma synthèse » : `<dialog>` natif ouvert en 1 clic, **rendu serveur** (lecture seule), barres CSS avec valeurs textuelles, top 7 projets + « Autres projets (N) ». |

Erreurs : **401** (anonyme), **403** (`user_id` d'autrui).

## Accessibilité & non-perturbation (CA-5)

- `<dialog>` natif (`showModal`) : piège de focus et fermeture Échap natifs ; le focus est **restauré**
  sur le déclencheur à la fermeture (contrôleur Stimulus `activity-summary`).
- Panneau **en lecture seule** : aucun champ de formulaire, aucune soumission implicite — les valeurs
  de saisie en cours restent intactes.
- Barres de répartition : la valeur est portée par le **texte** adjacent (durée + %), jamais par la
  seule couleur/longueur (WCAG 1.4.1). Planning dégradé : bloc **non interactif** (pas de faux onglet).
- Responsive : bottom-sheet mobile → panneau latéral droit desktop.

## Tests

- `ActivitySummaryTest` : agrégation projet/type, exclusion des refusés (RG-TMP-4), état vide, taux
  d'occupation.
- `ActivitySummaryApiTest` : 401, self, **403 sur `user_id` d'autrui**, `user_id` propre autorisé,
  état vide non-erreur, planning dégradé.
- `TimesheetPageTest` : panneau SSR présent, **lecture seule** (aucun `<input>` dans le `<dialog>` —
  CA-5).

## Limites connues / suite

- **Taxonomie d'activité** : réduite à production/absence faute de champ « type » sur les projets ;
  une taxonomie fine (type de projet) est un enrichissement ultérieur.
- **Planning à venir** : dégradé tant qu'US-037 (affectation) n'est pas livrée — la synthèse **passée**
  est le cœur livrable.
- **Drawer sur la vue jour mobile** (US-052) : le panneau est actuellement sur la vue semaine ; son
  intégration à la vue jour est un raffinement.
- **Performance** : `ActivitySummary` lit les imputations de la période puis agrège en mémoire ; N+1
  non concerné (une requête plage), cohérent avec la phase 1.

## Revue (T-059-06) — findings traités

Revue `symfony-reviewer` — **GO**. Architecture hexagonale respectée, cloisonnement 403 fonctionnel.

- **[Mineur — corrigé] Couverture** : ajout d'un cas « uniquement des imputations refusées → rapport
  vide » (`ActivitySummaryTest`), en complément de l'exclusion des refusés déjà testée.
- **[Majeur — déjà correct] Statuts RG-TMP-4** : une imputation naît `PENDING` (= soumise) puis
  devient VALIDATED/REJECTED ; `VALIDATED || PENDING` inclut bien « validés et soumis » et exclut les
  refusés (test à l'appui).
- **[Majeur — déjà correct, bonne SoC] Top 7 + « Autres »** : le service renvoie **tous** les projets
  triés (aucune décision d'affichage dans le Domain/Application) ; le top 7 + « Autres projets (N) »
  est porté par la **présentation** (`TimesheetPageController::summaryView`).
- **[Mineur — déjà correct] SSR** : `summaryView` ne fait que **formater** le rapport renvoyé par
  `ActivitySummary` — aucune requête directe en base.
- **[Mineur — rejeté] `filter_var` UUID sur `user_id`** : la comparaison stricte au collaborateur
  courant renvoie **403** pour toute valeur non conforme ; aucune requête n'utilise l'entrée. Cohérent
  avec le projet.

**Conclusion : GO** — findings de correction (couverture) traités ; les autres étaient des demandes de
vérification, confirmées conformes.
