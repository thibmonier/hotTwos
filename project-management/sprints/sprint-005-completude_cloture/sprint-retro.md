# Rétrospective — Sprint 5 : Complétude et clôture du cycle temps

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-01 |
| Format | Sailboat ⛵ |
| Facilitateur | Scrum Master |
| Contexte | Dev piloté IA + **conception UX/UI en amont** + revues automatisées (`security-auditor`, `symfony-reviewer`, `accessibility-expert`) |

## Directive Fondamentale (Kerth)

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait
> du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences, des
> ressources disponibles et de la situation. »

## 🏝️ Île — Destination (objectifs du prochain sprint)

- **Brancher la livraison effective des notifications** (absences + relances) : sortir l'adaptateur
  `logging` au profit d'un envoi in-app/email réel.
- **US-037 (affectation)** pour activer le planning à venir de la synthèse (aujourd'hui dégradé).
- Décider de la **taxonomie d'activité** (type de projet) pour enrichir la synthèse.

## 💨 Vent — Ce qui nous a poussés

- **Conception UX/UI en amont, à chaque écran** : 3 phases (ux-ergonome + ui-designer +
  accessibility-expert) ont produit des specs concrètes avant le code, puis une revue WCAG 2.2 AA.
  L'action rétro S4 la plus structurante est devenue un réflexe.
- **Découpage fin commité par tâche** + `make ci` dockerisé vert à chaque étape : 372 tests, aucune
  régression, reprise triviale après `/clear`.
- **Moteur de relances déterministe** (horloge injectée) : plancher, escalade et arrêt testables sans
  dépendre du temps réel.
- **Garde de parité RLS async** (action S4) appliquée d'emblée : chaque handler écrivant en base a son
  test d'intrusion via consume.
- **Revues à esprit critique** : on a corrigé de vrais défauts (plancher par jour ouvré, fuite
  listeners, double requête) et **argumenté les rejets** (hashage `userId`, `<dialog>` non-modal).

## ⚓ Ancre — Ce qui nous a freinés

- **Reviewers tronqués / limite de tours** : plusieurs revues se sont arrêtées avant conclusion →
  round-trips `SendMessage` pour obtenir le verdict.
- **Specs de conception parfois divergentes** (drawer : dialog modal vs panneau non-modal) → un
  arbitrage explicite a été nécessaire côté implémentation.
- **Collisions de noms en test** (`run`, `count`, `countOf` sont des méthodes finales de PHPUnit) →
  quelques allers-retours avant de trouver un nom libre.
- **Cache dev cassé par `cache:clear --no-warmup`** : a fait échouer le hook pre-commit (PHPStan
  Symfony a besoin du container XML dev) → warmup nécessaire.
- **Piège #5 récurrent** : chaque nouveau service interrogé depuis `/saisie` (bandeau relance, synthèse)
  oblige à ajouter l'entité au `$schema` de tous les tests fonctionnels de saisie.

## 🪨 Récifs — Risques à éviter

- **Notifications non livrées** : absences et relances sont journalisées mais **pas envoyées** — à ne
  pas prendre pour une fonctionnalité complète côté destinataire.
- **Planning « à venir » dégradé** : placeholder tant qu'US-037 n'existe pas — ne pas le présenter
  comme actif.
- **Taxonomie d'activité binaire** (production/absence) : la synthèse « par type » restera pauvre sans
  champ type sur les projets.
- **SSR de la synthèse sur `/saisie`** : une requête d'agrégation 4 semaines à chaque affichage de la
  page — acceptable au volume actuel, à surveiller (cache/lazy si besoin).

## 🎯 Actions Sprint 6

### Action 1 : Livrer un canal de notification effectif

| Attribut | Valeur |
|----------|--------|
| Description | Implémenter un adaptateur d'envoi réel (in-app puis email) derrière `ReminderNotifier` et les messages d'absence, en remplacement du `logging`. |
| Responsable | Tech Lead + Dev |
| Deadline | Sprint 6 |
| DoD | Au moins un canal effectif testé (in-app) ; les messages `AbsenceDeclared/Decided` et les relances aboutissent à une notification consultable. |
| Priorité | Haute |

### Action 2 : Conserver la phase de conception UX/UI (pérenniser l'acquis S4/S5)

| Attribut | Valeur |
|----------|--------|
| Description | Maintenir systématiquement la phase de conception UX/UI (ux-ergonome + ui-designer + accessibility-expert) avant tout écran, et **reprendre les écrans antérieurs** (`/saisie`, `/profils`, `/valorisation`, `/absences`, `/administration/periodes`, `/completude`) dans cette démarche + design system. |
| Responsable | Product Owner + UX/UI |
| Deadline | Continu ; reprise planifiée sur les sprints suivants |
| DoD | Chaque écran (nouveau ou repris) a une spec de conception + une revue a11y WCAG 2.2 AA. |
| Priorité | Haute (demande PO maintenue) |

### Action 3 : Réduire les frictions de revue automatisée

| Attribut | Valeur |
|----------|--------|
| Description | Cadrer les prompts de revue pour une **conclusion directe** (verdict + findings) afin d'éviter les arrêts à la limite de tours et les round-trips. |
| Responsable | Scrum Master |
| Deadline | Sprint 6 |
| DoD | Les revues rendent un verdict en une passe dans la majorité des cas. |
| Priorité | Moyenne |

### Action 4 : Fiabiliser le cache dev vis-à-vis du hook pre-commit

| Attribut | Valeur |
|----------|--------|
| Description | Éviter les `cache:clear --no-warmup` qui cassent le container XML dev requis par PHPStan-Symfony ; documenter le `cache:warmup` de récupération. |
| Responsable | Dev |
| Deadline | Sprint 6 |
| DoD | Note dans la doc CI/dev ; incident non reproduit. |
| Priorité | Basse |

## Suivi des actions précédentes (Sprint 4)

| Action | Description | Status |
|--------|-------------|--------|
| S4-1 | Garde de parité RLS pour tout handler async (test via consume) | ✅ Fait (`ReminderWorkerRlsTest` sur US-056) |
| S4-2 | Solder le lot de hardening `set_config` (paramètre lié) | ✅ Fait (T-TECH-03) |
| S4-3 | Tracer/lever la dépendance US-057 (stub de clôture global) | ✅ Fait (US-057 remplace le stub par une clôture par tenant) |
| S4-4 | Intégrer une phase de conception UX/UI à chaque sprint | ✅ Fait (3 phases UX/UI ce sprint, revues a11y) |

## Check-out

- **ROTI** : 5/5 — 100 % du périmètre livré (22/22 points, 44/44 tâches), les 4 actions S4 soldées, et
  la conception UX/UI enfin ancrée dans le processus.
- **Ce qu'on emporte** : « une fonctionnalité n'est finie côté utilisateur que lorsque la notification
  part vraiment — journaliser n'est pas livrer. »
