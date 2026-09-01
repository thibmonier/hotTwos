# Rétrospective — Sprint 3 (Première saisie de temps)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-01 |
| Format | Starfish (⭐) |
| Facilitateur | Scrum Master |
| Périmètre | US-050, US-051, US-055, TECH-3 (23 pts) |

> **Directive Fondamentale (Norm Kerth).** « Indépendamment de ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait le meilleur travail possible, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des ressources disponibles et de la situation du moment. »

## Rappel du Sprint

- **Goal atteint** : première valeur métier livrée (saisie + validation), `RSQ-17` honoré.
- **Résultat** : 23/23 pts, 112 tests verts, staging à jour, smoke 5/5.
- **Marquant** : premier sprint métier (entités de domaine réelles) ; décisions PO cadrées en amont (enregistrement « les deux », absences minimales) ; TECH-3 préparé sans risquer la prod.

## Observations (Starfish)

### 🟢 Continuer
- **Cadrer les décisions PO avant de coder** (via questions ciblées) : enregistrement batch+autosave, absences minimales, projet minimal — a évité les allers-retours et tenu la taille du sprint.
- **TDD par tranches commitées + `make ci`** : chaque tranche verte ; 0 CI rouge sur le code.
- **Ports/DIP + Deptrac** : les frontières ont tenu ; les FQCN insérés par Rector ont été raccourcis systématiquement.
- **Sonde/probe puis métier** : réutiliser l'`Authorizer` (US-003) pour la validation (US-055) a été immédiat — la charpente des Sprints 1-2 paie.

### 🟡 Commencer
- **Harnais E2E navigateur** (Panther/Playwright) : indispensable pour *mesurer* le critère ≤ 2 min (`RSQ-1`), aujourd'hui seulement prouvé comme *capacité*.
- **Fixtures de démo par tenant** : projets + collaborateurs + une semaine type, pour démontrer la saisie/validation sans montage manuel.
- **Endpoint d'administration des projets** (responsable, actif) : aujourd'hui les projets sont semés en test ; il manque un écran/endpoint pour les créer en vrai.

### 🔴 Arrêter
- **Utiliser des littéraux non-UUID en test fonctionnel** : `'camille'`/`'un-autre-chef'` passaient en mémoire mais cassaient sur colonnes UUID réelles — toujours des UUID valides côté base.
- **Laisser Rector décider des FQCN** sans import : petit coût récurrent de nettoyage.

### ⬆️ Plus de
- **Tests négatifs d'habilitation** (403 hors périmètre, 422 sans motif) : ils prouvent la sécurité serveur (ARC-106), pas seulement le chemin nominal.
- **Préparer les gestes ops risqués sans les exécuter à l'aveugle** (TECH-3 : code + procédure + rollback, bascule laissée au PO) — sain pour une opération de sécurité en prod.

### ⬇️ Moins de
- **Écart entre « capacité livrée » et « validé en usage »** : US-051 est livrée en capacité mais le ≤ 2 min réel n'est pas mesuré — réduire cet écart avec l'E2E.

## Thème principal : capacité vs validation d'usage (votes ●●●●)

**Problème** : le critère bloquant `RSQ-1` (≤ 2 min) est prouvé structurellement (batch, duplication, clavier), mais pas mesuré sur un parcours humain réel.
**Solution** : installer un E2E navigateur chronométré au Sprint 4 et le brancher en CI (étape 10 d'ADR-12, déjà prévue).

## Actions Sprint 4

### Action 1 : Harnais E2E + mesure ≤ 2 min
| Attribut | Valeur |
|----------|--------|
| Description | Installer Panther (ou Playwright), écrire un parcours de saisie de semaine chronométré, l'ajouter en CI |
| Responsable | Équipe technique |
| Deadline | Sprint 4 |
| DoD | Test E2E vert mesurant une semaine 5 projets ≤ 2 min |
| Priorité | Haute |

### Action 2 : Finaliser la bascule RLS prod (report TECH-3)
| Attribut | Valeur |
|----------|--------|
| Description | Poser le mot de passe `hotones_app` + basculer `DATABASE_URL`, vérifier l'isolation en prod, étendre la RLS aux tables métier (DBT-SEC-1) |
| Responsable | PO (ops) + Équipe technique |
| Deadline | Sprint 4 |
| DoD | App en prod sous `hotones_app`, intrusion cross-tenant → 0 ligne |
| Priorité | Haute |

### Action 3 : Fixtures de démo + endpoint projets
| Attribut | Valeur |
|----------|--------|
| Description | Jeu de données synthétique par tenant + création de projets (responsable) |
| Responsable | Équipe technique |
| Deadline | Sprint 4 |
| DoD | Démo saisie→validation sans montage manuel |
| Priorité | Moyenne |

## Suivi des actions Sprint 2

| Action S2 | Statut |
|-----------|--------|
| A1 · Smoke de déploiement automatisé | ✅ Fait (`make smoke`, TECH-3) |
| A2 · IaC Railway (.railway/railway.ts) | ❌ Non fait (report — `railway.toml` fonctionne jusqu'au 2026-12) |
| A3 · Activer la RLS runtime en production | 🔶 Préparé (TECH-3) ; bascule ops reportée (Action 2 ci-dessus) |

## Check-out — ROTI

| Perspective | Score | Commentaire |
|-------------|-------|-------------|
| Équipe technique | 5/5 | Première valeur métier livrée, socle qui paie, 23/23 |

**À emporter :** « Livrer la capacité en TDD, puis la valider en usage (E2E chronométré). »

## Métriques à surveiller (CDC)

- Jours depuis le dernier test d'usage réel (`RSQ-17`) — **réinitialisé** : la saisie est livrée ; maintenir un usage réel régulier.
- Adoption `RSQ-1` — à mesurer (E2E + usage pilote).
- Couverture règles critiques (`ENF-MAINT-1`) — habilitation validation, plafond, isolation couvertes.
