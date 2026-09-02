# Sprint 6 : Projets & delivery (cycle de vie, structure, affectation, clôture)

## Informations

| Attribut | Valeur |
|----------|--------|
| Numéro | 6 |
| Début | 2026-10-27 |
| Fin | 2026-11-07 |
| Durée | 10 jours ouvrés |
| Capacité (prévision) | ~22 points (vélocité S1-S5 : 29/20/23/21/22 → moy. ~23) |
| Base git | `main` (Sprint 5 mergé, PR #7) |
| Branche | `feature/sprint-6-planning` |

## Sprint Goal

> « Un chef de projet **crée et structure un projet** (cycle de vie à 7 statuts, lots/jalons,
> engagements externes), y **affecte des collaborateurs** qui seuls peuvent imputer, et le **clôture**
> avec traçabilité — complétant le delivery projet (EPIC-002) et débloquant le planning d'US-059. »

Ce sprint **ouvre EPIC-002 (Projets & delivery)**, chemin critique du lot 1 et prérequis du lot 2
(planification, finance). L'entité `Project` — aujourd'hui volontairement minimale (code, nom, actif,
responsable) — devient un **agrégat métier** : client, budget, contractualisation, dates, statut. Le
cycle de vie et l'affectation **conditionnent l'imputation** (par-dessus le blocage de période clôturée
d'US-057) et la clôture projet réutilise le pattern de clôture/réouverture d'US-057.

## Definition of Done (rappel + actions rétro S5)

- [ ] Tests unitaires + intégration verts, couverture ≥ 80 % sur le code touché
- [ ] `make ci` vert (PHPStan max, Deptrac 0, cs-fixer, Rector, gitleaks) ; `schema:validate` OK
- [ ] Isolation multi-tenant (filtre ORM + RLS prod) : toute nouvelle table a sa policy RLS + un test d'intrusion `*RlsRuntimeTest`
- [ ] **[Action rétro S4/S5]** Tout `#[AsMessageHandler]` écrivant en base a un test d'intrusion RLS **via consume**
- [ ] **[Action rétro S4/S5]** Tout écran est **précédé d'une phase de conception UX/UI** (ux-ergonome + ui-designer + accessibility-expert) et d'une revue **WCAG 2.2 AA**
- [ ] Habilitation vérifiée côté serveur (ARC-19/ARC-106) ; traçabilité des opérations sensibles (HAB-6)
- [ ] Dégradations documentées là où un module amont manque (facturation EPIC-005, budget US-033, RAF US-035, plan de charge EPIC-004)
- [ ] Documentation module mise à jour ; revues `security-auditor` + `symfony-reviewer`

## Sprint Backlog

| Priorité | ID | Titre | Points | Persona | Statut |
|----------|-----|-------|--------|---------|--------|
| 🔴 Must | US-030 | Création de projet et cycle de vie (racine EPIC-002) | 5 | P2 Marc | 🔵 To Do |
| 🔴 Must | US-031 | Structure en lots et jalons | 5 | P2 Marc | 🔵 To Do |
| 🔴 Must | US-037 | Affectation et restriction d'imputation | 5 | P2 Marc / P3 Sophie | 🔵 To Do |
| 🔴 Must | US-034 | Engagements externes rattachés au projet | 3 | P2 Marc | 🔵 To Do |
| 🔴 Must | US-038 | Clôture opérationnelle du projet | 3 | P2 Marc | 🔵 To Do |

**Total engagé : 21 points** (dans la cible 20-40 ; sous la vélocité moyenne ~23 — marge pour la
complexité d'ouverture d'un nouveau module).

## Dégradations assumées (modules amont non livrés)

| US | CA touché | Module manquant | Traitement |
|----|-----------|-----------------|------------|
| US-031 | CA-2 (jalon → facture) | Facturation (EPIC-005) | Le jalon **enregistre l'intention** de facturation + trace ; pas d'émission réelle. |
| US-034 | CA-1 (marge complète) | Budget vente US-033 | CRUD engagements + coûts inclus ; vue **marge** partielle (coûts internes valorisés US-060 + coûts externes), budget de vente à brancher avec US-033. |
| US-037 | CA-3 (plan de charge) | Planification (EPIC-004) | Affectation avec charge prévisionnelle stockée ; alimentation du plan de charge ultérieure. |
| US-038 | CA-2/CA-5 (agrégats/facture) | Reporting / Facturation | Clôture + lecture seule + audit ; suivi financier post-clôture partiel. |

## Notes

Décision PO (arbitrage périmètre) : chaîne EPIC-002 complète orientée delivery + affectation, retenue
parmi 3 variantes (vs « PRJ + Budget US-033 » 23 pts, vs « socle minimal + dette notifications »
16 pts). US-030 est la **racine obligatoire** (dépendance non livrée de toutes les autres).
