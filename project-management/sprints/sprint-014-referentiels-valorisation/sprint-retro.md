# Rétrospective — Sprint 14 (Référentiels de valorisation)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-06 |
| Format | Starfish |
| Facilitateur | Scrum Master |

## Directive Fondamentale

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des ressources disponibles, et de la situation. » — Norman Kerth

## Rappel du Sprint

- **Sprint Goal atteint** : EPIC-001 valorisation (US-015 taux multi-niveaux, US-016 devises) + export pilotage (US-079a).
- **~11 pts** livrés (US-015, US-016, US-079a). PR #74 → #78. 605 → 628 tests.
- **US-079c (courbe) et US-079b (seuil par type) reportés S15** — décision Tech Lead.

## ⭐ Observations (Starfish)

### 🟢 Continuer
- **Réutilisation du socle Pricing** : US-015 s'est branchée sur `RateResolver`/`ProfileRate`/`EffectivePeriod` et le patron `DefineProfileRate` — un résolveur de priorité unique, zéro duplication.
- **Découper une story parapluie et livrer par tranche** : US-079 découpée en export/courbe/seuil ; l'export (valeur immédiate, contenu) livré, le reste reporté sans bloquer le sprint.
- **cs-fixer/rector préventifs** avant `make ci` : réflexe désormais systématique, moins d'allers-retours.

### 🟡 Commencer
- **Anticiper l'impact « une nouvelle donnée sur une page partagée casse les schémas de tests »** : ajouter clients/projets à `/profils` a cassé 2 tests fonctionnels (schémas incomplets). Réflexe : avant d'enrichir un contrôleur de page, lister les tests fonctionnels qui l'atteignent et compléter leurs `SchemaTool`.

### 🔴 Arrêter
- **Forcer une intégration invasive pour une valeur Should** : US-079c (courbe) imposait ≥5 nouvelles dépendances dans `ComputeProjectMargins` (handler critique). Reportée plutôt que dégrader un cœur métier. Bon réflexe — à garder comme critère de décision.

### ⬆️ Plus de
- **Décisions de périmètre explicites en cours de décomposition** : la sous-estimation d'US-079 (8 pts pour ~13 réels) a été détectée au « Comment » et tracée (export livré, reste reporté), pas subie.

### ⬇️ Moins de
- **Pièges Twig/PHP récents** : `for…if` supprimé en Twig 3 (→ `|filter`) ; `fputcsv` exige `$escape` explicite (PHP 8.4) ; `getOneOrNullResult()` renvoie `mixed` (→ `instanceof` avant retour typé). À mémoriser.

## 📚 Learnings clés

- **Un référentiel bien conçu se réutilise transversalement** : `EffectivePeriod` + le pattern « historisé à date + résolveur » servent Pricing, SellingRate ET les taux de change.
- **La devise se consolide sans reconvertir le stock** : montants en centimes inchangés, conversion à la lecture — migration douce, risque minimal.
- **Reporter vaut mieux que dégrader** : une story Should ne justifie pas d'alourdir un handler cœur ; le découpage parapluie rend le report propre.
- **Rappels (reconduits)** : `git switch -c` avant de coder ; compléter les schémas des tests fonctionnels quand un contrôleur de page gagne une dépendance ; `make cache-dev` après `cache:clear`.

## 🎯 Actions Sprint 15

| # | Action | Priorité |
|---|--------|----------|
| 1 | Décision PO : finir US-079 (courbe US-079c + seuil US-079b) vs poursuivre EPIC-001 (calendrier/compétences/onboarding) | À trancher (PO) |
| 2 | Si US-079c retenu : cadrer une capture d'atterrissage **légère** (éviter d'alourdir `ComputeProjectMargins`) | Moyenne |
| 3 | `MAILER_DSN` staging à exécuter réellement (report S12) | Moyenne |

## Suivi des actions Sprint 13

| Action S13 | Statut |
|-----------|--------|
| Finir EPIC-002 (US-079) vs nouvel EPIC | 🟡 En cours — US-079a livré, US-079b/c reportés S15 |
| Circuit de validation d'avenant | ⏸️ Non prioritaire |
| MAILER staging | ❌ Reconduit (Action 3) |

## Check-out

ROTI (auto-évaluation solo) : **4/5** — EPIC-001 valorisation livré (taux multi-niveaux + devises, socle Pricing réutilisé), export pilotage bouclé. Sprint plus léger (11 pts) assumé : report d'US-079c sur critère d'ingénierie (ne pas alourdir un handler cœur). Irritant : schémas de tests fonctionnels à compléter après enrichissement de `/profils`.
À emporter : « Reporter une story Should vaut mieux que forcer une intégration invasive dans un cœur métier. »
