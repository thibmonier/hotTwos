# Chantier F-S5-4 — Affichage des noms de collaborateurs (complétude & écrans d'équipe)

| Attribut | Valeur |
|----------|--------|
| Origine | Recette Sprint 5 (`/qa:recette --scope=sprint 5`), finding **F-S5-4** |
| Statut | 🟡 Ouvert (à cadrer) |
| Sévérité | Moyen (utilisabilité) |
| US concernée | US-058 (tableau de complétude) ; impacte aussi US-037 (affectations), US-057 (audit), US-054 |
| Écrans | `/completude` (prioritaire), `/projets/{id}` onglet Équipe, journaux d'audit |
| Créé le | 2026-09-02 |

## Problème observé

Sur `/completude`, les collaborateurs sont affichés par les **8 premiers caractères de leur UUID**.
Les UUID v7 générés proches dans le temps (cas du tenant de démo) partagent ce préfixe → **les 3
lignes affichent toutes « 01a0613d »** et deviennent **indistinguables**. L'écran, dont la finalité
est d'identifier qui est en retard (OBJ-1), perd sa valeur.

Cause directe : `templates/completeness/index.html.twig:44` → `<code>{{ userId[:8] }}</code>`.
Même symptôme (identifiants bruts) sur l'onglet Équipe des projets (US-037) et les traces d'audit.

## Analyse d'impact

- **Non bloqué par US-014** : US-014 est le référentiel **clients**, pas l'annuaire utilisateur.
  `App\Domain\User\User` porte déjà `email` (et des rôles) — la résolution d'un identifiant
  collaborateur → libellé lisible est faisable **sans US-014**.
- **Manque** : aucune notion de « nom / prénom » dans `app_user` aujourd'hui (colonnes : id, tenant_id,
  roles, email, password). L'affichage lisible immédiatement disponible est donc l'**email**.
  Un vrai nom d'affichage supposerait d'enrichir le modèle utilisateur (hors périmètre de ce chantier).
- **Périmètre technique** : le read model / la requête de complétude (et les vues Équipe/audit)
  doivent exposer un libellé (email) par `userId`, via un résolveur `userId → email` cloisonné tenant
  (respect RBAC, pas de fuite inter-tenant).

## Options

| Option | Description | Effort | Remarque |
|--------|-------------|--------|----------|
| **A (recommandée)** | Afficher l'**email** du collaborateur (résolveur `userId → email` tenant-aware) sur `/completude`, Équipe et audit | Faible | Débloque l'utilisabilité immédiatement, sans changer le modèle |
| B | Ajouter un **nom d'affichage** (displayName) à `User` + migration + saisie | Moyen | Meilleur libellé, mais élargit le modèle utilisateur (à arbitrer PO) |
| C | Statu quo + tooltip UUID complet au survol | Très faible | Ne résout pas la lisibilité de fond (rejeté) |

## Contrainte de process (consigne PO)

> **Phase de conception UX/UI obligatoire avant tout dev front** (ux-ergonome + ui-designer +
> accessibility-expert). Ce chantier touche l'affichage de plusieurs écrans (complétude, Équipe, audit) :
> il doit passer par une maquette validée (colonne collaborateur, troncature/tooltip, accessibilité)
> **avant** implémentation. Voir la mémoire projet « phase de conception UX/UI ».

## Definition of Done (proposée)

- [ ] Conception UX/UI validée (affichage collaborateur homogène sur complétude / Équipe / audit).
- [ ] Résolveur `userId → libellé` cloisonné tenant (test d'intrusion : pas de résolution cross-tenant).
- [ ] `/completude` affiche un libellé distinctif par collaborateur (email en option A).
- [ ] Test fonctionnel : deux collaborateurs distincts apparaissent avec des libellés distincts.
- [ ] `make ci` vert.

## Références

- Finding : `.recette/regression/registry.yaml` (REG-S5-003).
- Rapport : `.recette/reports/REC-20260902-sprint5-report.md`.
- Cause : `templates/completeness/index.html.twig:44`.
