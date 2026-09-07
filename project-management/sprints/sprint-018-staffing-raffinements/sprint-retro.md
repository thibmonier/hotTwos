# Rétrospective — Sprint 18 (Staffing + finitions Review & qualité)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-07 |
| Format | Starfish ⭐ |
| Sprint | 18 — démarrage EPIC-004 + finitions |
| Points livrés | 20 / 20 (+ QUAL-3) |
| Contexte | Solo dev (cérémonies = jalons documentaires) |

## Directive Fondamentale
> Chacun a fait de son mieux compte tenu de ce qu'il savait, de ses compétences, des ressources et de la situation. — Norman Kerth

## Rappel du Sprint
Goal atteint (20/20). 4 items mergés (#105–#108). 685 → **697 tests**. EPIC-004 amorcé.

## ⭐ Observations

### 🟢 CONTINUER
- **QUAL-3 en premier** : le trait `ProvisionsFullSchema` a servi immédiatement aux tests d'US-041/040 (plus de liste d'entités par test).
- **Réutilisation en cascade maîtrisée** : US-040 réutilise `ViewWorkloadPlan` (US-041), qui réutilise `WorkingDaysCalculator` (US-021/022) — capitalisation nette sur les sprints précédents.
- **Périmètre borné assumé** : charge probable (CRM) et scoring avancé explicitement reportés → stories livrables.
- **cs-fix + rector-fix avant commit** systématique.

### 🟡 COMMENCER
- **Migrer davantage de tests vers `ProvisionsFullSchema`** au fil de l'eau (adoption incrémentale) pour éteindre définitivement la cascade.

### 🔴 ARRÊTER
- Rien de bloquant ce sprint.

### ⬆️ PLUS DE
- **Read models d'application composables** (`ViewWorkloadPlan` réutilisé par `SearchStaffing`) : bon pattern à généraliser.

### ⬇️ MOINS DE
- **Cascade SchemaTool résiduelle** : US-023 a encore exigé d'ajouter `ConfigAuditEntry` à 2 tests API non migrés. Le trait existe désormais → migrer ces tests règlera le sujet.

## Thème & analyse
### Instrumentation transverse (audit) — effet de bord sur les tests · Votes ●●●
**Problème** : brancher `ConfigAuditRecorder` dans des use cases partagés (profils, org) fait écrire une
nouvelle table → 500 dans les tests API non provisionnés.
**Solution** : QUAL-3 (trait) est la réponse structurelle ; adopter le trait dans les tests API restants.

## 🎯 Actions Sprint 19
1. **Adoption incrémentale de `ProvisionsFullSchema`** dans les tests API/fonctionnels restants (au fil des touches). — Basse/continue.
2. **Arbitrage PO** : poursuite EPIC-004 (détection sur/sous-charge avancée, alerte recrutement OBJ-5) vs EF-REF-24 vs nouvel EPIC. — PO.
3. **MAILER staging** : enfin planifier (dette reconduite depuis S10). — Moyenne.

## Suivi actions précédentes
| Sprint | Action | Status |
|--------|--------|--------|
| S16/S17 | Spike SchemaTool mutualisé | ✅ Fait (QUAL-3 #105) |
| S15 | cs-fix/rector-fix pré-commit | ✅ Adopté |
| S10→ | MAILER staging | ❌ Reconduit (action S19) |

## Vélocité
S11→S18 : 23 / 16 / 21 / 10 / 16 / 21 / (S17) / **20**. Rythme stable haut de fourchette.

## ROTI
Solo dev — **5/5** (exécution fluide ; QUAL-3 a payé immédiatement).

## Prochaine étape
→ `/clear` puis `/workflow:start 019` (arbitrage EPIC-004 suite / EF-REF-24 / nouvel EPIC).
