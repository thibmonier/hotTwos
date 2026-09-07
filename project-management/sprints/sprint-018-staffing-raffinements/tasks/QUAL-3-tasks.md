# Tâches - QUAL-3 : Schéma de test mutualisé

## Informations
- **Type** : story technique (rétro S16/S17) · **Points** : 2 · **Sprint** : sprint-018 · **Ordre** : #1

## Objectif
Tarir la cascade SchemaTool : centraliser la liste des `ClassMetadata` dans un trait réutilisable par
les WebTestCase, afin qu'une nouvelle table ne nécessite plus une édition test par test.

## Vue d'ensemble des tâches

| ID | Type | Tâche | Est. | Dépend | Statut |
|----|------|-------|------|--------|--------|
| T-QUAL3-01 | [TEST] | Trait `ProvisionsFullSchema` (liste centralisée des entités + create/drop) | 2.5h | - | 🔲 |
| T-QUAL3-02 | [TEST] | Migrer 3–4 tests fonctionnels pilotes vers le trait (preuve de concept) | 2h | 01 | 🔲 |
| T-QUAL3-03 | [DOC]+[REV] | Doc d'usage (quand l'utiliser) + review + `make ci` | 1h | 02 | 🔲 |

**Total estimé** : ~5.5h

## Détails clés
- **T-QUAL3-01** : `tests/Support/Schema/ProvisionsFullSchema.php` (trait) exposant `provisionSchema(EntityManagerInterface): void` et `dropSchema()`, avec la **liste complète** des entités du domaine (une seule source). Utiliser `$em->getMetadataFactory()->getAllMetadata()` si fiable, sinon liste explicite maintenue.
- **T-QUAL3-02** : convertir des tests représentatifs (occupation, absence, config) ; **ne pas** tout migrer (YAGNI) — prouver le pattern, laisser l'adoption incrémentale.
- **Non-régression** : les tests migrés doivent rester verts sans changer leurs assertions.

## Résumé
| Couche | Tâches | Heures |
|--------|--------|--------|
| [TEST] | 2 | 4.5h |
| [DOC]/[REV] | 1 | 1h |
| **TOTAL** | **3** | **~5.5h** |
