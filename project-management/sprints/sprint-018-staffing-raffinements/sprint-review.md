# Sprint Review — Sprint 18 (Staffing + finitions Review & qualité)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-07 |
| Sprint Goal | « Amorcer le staffing (recherche par compétence + capacité/charge ferme), finaliser les suites de Review (fériés mobiles, audit étendu) et tarir la dette de test (schéma mutualisé). » |
| EPIC | EPIC-004 (Planification & Staffing) — **démarrage** + finitions EPIC-001 |

## 🎯 Atteinte du Sprint Goal

**Sprint Goal atteint : ✅ OUI. 20/20 pts livrés** (+ QUAL-3). EPIC-004 est amorcé (premier incrément
buildable) ; les suites de Review sont livrées ; la cascade SchemaTool est outillée.

- **QUAL-3** — trait `ProvisionsFullSchema` : une nouvelle entité est prise en compte automatiquement dans les tests (3 pilotes migrés).
- **US-023** — fériés mobiles (calcul de Pâques) provisionnés à l'onboarding ; audit étendu aux paramètres org & profils/taux.
- **US-041** — plan de charge : capacité (jours ouvrés nets, par régime) vs charge ferme (affectations) ; détection de surcharge.
- **US-040** — recherche de staffing par compétence + niveau minimal, croisée avec la disponibilité.

## 📦 Livré

| ID | Titre | Points | PR |
|----|-------|--------|-----|
| QUAL-3 | Schéma de test mutualisé (trait) | 2 | #105 |
| US-023 | Fériés mobiles onboarding + audit étendu org/profils | 5 | #106 |
| US-041 | Plan de charge — capacité vs charge ferme | 5 | #107 |
| US-040 | Recherche de staffing par compétence & disponibilité | 8 | #108 |

**Points livrés : 20 / 20 engagés** (capacité 22).

## 📈 Métriques

| Métrique | Valeur |
|----------|--------|
| Tests | 685 → **697** |
| Couverture | ≥ 80 % gardée en CI |
| `make ci` | vert à chaque merge (PHPStan max, Deptrac, cs, rector, gitleaks) |
| Nouveaux écrans | `/planification/charge`, `/planification/recherche` |

## 🎬 Démonstration
1. **Plan de charge** — `/planification/charge` : capacité vs charge ferme, surcharges signalées.
2. **Recherche staffing** — `/planification/recherche` : « Java niveau ≥ Avancé » → candidats + disponibilité.
3. **Onboarding** — `tenant:init` provisionne désormais les fériés mobiles (Pâques/Ascension/Pentecôte).
4. **Audit** — modifier un profil/taux ou une unité d'organisation apparaît dans `/parametrage/audit`.

## 💬 Feedback à collecter
1. **Charge probable** (pipeline pondéré, INV-5) : prioriser EPIC-006 (CRM) pour l'alimenter ?
2. Recherche de staffing : besoin d'un **scoring/ranking** multi-critères (au-delà du filtre ET) ?
3. Poursuivre EPIC-004 (détection de sur/sous-charge avancée, suggestions de ré-affectation) ou autre EPIC ?

## Impact backlog
- **EPIC-004** : démarré (recherche + charge ferme). Reste : charge probable (dépend EPIC-006), détection/suggestions avancées, alerte recrutement (OBJ-5).
- **EPIC-001** : suites Review livrées (fériés mobiles, audit étendu). Reste EF-REF-24 (transitions de statut) + EF-REF-7 complet (entité/pays/forfait).
- **Dette** : QUAL-3 livré (schéma mutualisé) — adoption incrémentale des autres tests possible.

## Prochaines étapes
1. Rétrospective S18 (`sprint-retro.md`).
2. Sprint 19 : suite EPIC-004 ou nouvel EPIC (arbitrage PO). MAILER staging reconduit.
