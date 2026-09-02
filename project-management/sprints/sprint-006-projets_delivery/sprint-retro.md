# Rétrospective — Sprint 6 : Projets & delivery (EPIC-002)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-02 |
| Format | Sailboat ⛵ |
| Facilitateur | Scrum Master |
| Contexte | Dev piloté IA + conception UX/UI en amont + revues automatisées (`security-auditor`, `symfony-reviewer`, `accessibility-expert`) |

## Directive Fondamentale (Kerth)

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait
> du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences, des
> ressources disponibles et de la situation. »

## 🏝️ Île — Destination (objectifs du prochain sprint)

- **Livrer un canal de notification effectif** (action S5 toujours ouverte) : absences + relances.
- Poursuivre EPIC-002 : **budget de vente / marge par lot (US-033)**, RAF (US-035), atterrissage
  (US-036) — pour lever les dégradations de marge/facturation.
- **Durcissements** identifiés en revue : ancrer `userId` sur l'acteur dans `RecordTimeEntry`, borne
  basse de fenêtre de réouverture.

## 💨 Vent — Ce qui nous a poussés

- **Un socle réutilisé, pas réinventé** : la clôture/réouverture 4-eyes d'US-057 a servi de patron à
  US-038 ; la RLS + tests d'intrusion sont devenus mécaniques.
- **Évolution d'agrégat sans régression** : `Project` minimal → agrégat métier, avec statut par défaut
  « En cours » et restriction d'affectation inactive sans affectation → **zéro test existant cassé**.
- **Conception UX/UI en amont** (onglets WAI-ARIA, badges accessibles) : l'action rétro S5 est ancrée.
- **Checkpoint par US** (commit + `make ci` vert) : 5 US enchaînées « sans pause » avec reprise sûre.
- **Revues à esprit critique** : durcissements réels appliqués (L3/L4/O(n²)), dettes documentées, et
  rejets argumentés (statut d'engagement figé = YAGNI).

## ⚓ Ancre — Ce qui nous a freinés

- **Piège #5 récurrent et coûteux** : chaque nouvelle table interrogée par `RecordTimeEntry` (ou lue
  par `/saisie`) oblige à l'ajouter au `$schema` de **tous** les tests fonctionnels de saisie — source
  répétée d'échecs « table absente ».
- **Churn du constructeur de `RecordTimeEntry`** : 3 nouveaux ports (affectation, ouverture,
  réouverture) sur le sprint → 3 passes de mise à jour des mêmes tests unitaires.
- **Outillage d'édition en masse** : les substitutions `sed`/`perl` via le proxy n'ont pas pris (edits
  silencieusement non appliqués, puis import dupliqué) → bascule sur des edits ciblés.
- **Revues tronquées à la limite de tours** : conclusions obtenues seulement après relance
  `SendMessage` (action S5-3 non résolue).

## 🪨 Récifs — Risques à éviter

- **`RecordTimeEntry` devient un carrefour de gardes** : 5 règles s'y accumulent — surveiller la
  lisibilité ; extraire une politique si une 6ᵉ arrive.
- **`ProjectPageController` à 10 dépendances** : agrégation de lecture des 5 onglets — à refactorer
  (presenter/query) avant d'y ajouter encore.
- **Dégradations en cascade** : marge, facturation, plan de charge dépendent de modules non livrés —
  ne pas les présenter comme complets.
- **`userId` libre dans le use case de saisie** (defense-in-depth) : à ancrer sur l'acteur.

## 🎯 Actions Sprint 7

### Action 1 : Helper de schéma de test « saisie » (piège #5)

| Attribut | Valeur |
|----------|--------|
| Description | Centraliser la liste des entités SchemaTool des tests de saisie (trait ou base commune) pour qu'un nouveau port interrogé par `RecordTimeEntry` n'oblige plus à éditer N tests. |
| Responsable | Tech Lead |
| Deadline | Sprint 7 |
| DoD | Un trait `TimesheetSchemaTrait` (ou équivalent) fournit le `$schema` complet ; les tests de saisie l'utilisent. |
| Priorité | Haute |

### Action 2 : Ancrer `userId` sur l'acteur authentifié dans `RecordTimeEntry`

| Attribut | Valeur |
|----------|--------|
| Description | Passer l'objet `User` (ou dériver l'identité) au use case de saisie plutôt qu'un `userId` libre (defense-in-depth, anti-IDOR). |
| Responsable | Dev |
| Deadline | Sprint 7 |
| DoD | `RecordTimeEntry`/`RecordWeek` ne prennent plus de `userId` arbitraire ; tests mis à jour. |
| Priorité | Moyenne |

### Action 3 : Livrer un canal de notification effectif (report S5)

| Attribut | Valeur |
|----------|--------|
| Description | Implémenter un adaptateur d'envoi réel (in-app puis email) derrière `ReminderNotifier` et les messages d'absence. |
| Responsable | Tech Lead + Dev |
| Deadline | Sprint 7 |
| DoD | Au moins un canal effectif testé ; les relances et décisions d'absence aboutissent à une notification consultable. |
| Priorité | Haute |

### Action 4 : Conclusions de revue en une passe

| Attribut | Valeur |
|----------|--------|
| Description | Cadrer les revues pour un verdict direct (findings + GO/NO-GO) sans atteindre la limite de tours (relances `SendMessage` évitées). |
| Responsable | Scrum Master |
| Deadline | Sprint 7 |
| DoD | La majorité des revues rendent leur verdict sans relance. |
| Priorité | Basse |

## Suivi des actions précédentes (Sprint 5)

| Action | Description | Status |
|--------|-------------|--------|
| S5-1 | Livrer un canal de notification effectif | 🔁 Reporté (S6 = EPIC-002 ; repris en action S7-3) |
| S5-2 | Conserver la phase de conception UX/UI | ✅ Fait (conception UX/UI du module projet en amont) |
| S5-3 | Conclusions de revue en une passe | 🔁 Partiel (revues encore tronquées → action S7-4) |
| S5-4 | Fiabiliser le cache dev vs hook pre-commit | ✅ Fait (`cache:warmup` systématique avant CI, incident non reproduit) |

## Check-out

- **ROTI** : 5/5 — un module entier (EPIC-002, 5 US, 21 pts) ouvert et livré vert, sans régression sur
  l'existant, avec revues et durcissements appliqués.
- **Ce qu'on emporte** : « faire évoluer un agrégat central se paie en test-churn : centraliser les
  fixtures de schéma et ancrer les identités au plus tôt. »
