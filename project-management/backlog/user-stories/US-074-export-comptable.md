# US-074: Export comptable au format FEC

## Métadonnées
- **ID**: US-074
- **EPIC**: EPIC-005 (Finance & rentabilité)
- **Sprint**: Sprint 10
- **Statut**: ✅ Done (livré Sprint 10 — PR #49, revue approuvée, `make ci` vert)
- **Points**: 8 *(réestimé depuis 5 : conformité FEC = écritures équilibrées + 18 champs normés + mapping de comptes configurable, au-delà d'un simple CSV)*
- **Persona**: P6 (Directeur financier / contrôleur de gestion)
- **Créé le**: 2026-09-04
- **Mis à jour**: 2026-09-05

## Traçabilité
- **Implémente**: EF-FIN-22 (export vers la comptabilité), OBJ-3 (reporting financier automatisé, zéro ressaisie)
- **Dépend de**: US-071 (marge/CA/coût figés par projet à la clôture), US-073 (consolidation), US-057 (clôture de période)
- **Décision PO (2026-09-05)**: le format d'export est le **FEC** (Fichier des Écritures Comptables, art. A47 A-1 du LPF) — norme légale française, opposable à l'administration fiscale.

## User Story

**En tant que** directeur financier (P6),
**je veux** exporter les écritures de rentabilité d'une **période clôturée** au **format FEC** (norme légale),
**afin de** transmettre à la comptabilité / à l'expert-comptable un fichier opposable, sans ressaisie ni perte de donnée.

## Décision de conception (à acter en ADR léger — T-074-01)

L'application n'est pas une comptabilité en partie double : elle produit du **CA reconnu** et du **coût
valorisé** figés (US-071). L'export FEC génère des **écritures équilibrées** (débit = crédit) à partir de
ces montants, via un **mapping de comptes configurable par tenant** (compte de produit, compte de
tiers/client, compte de charge, compte de contrepartie). Ce n'est pas un grand livre complet mais un
**export normé FEC des écritures de rentabilité reconnues** — le raccordement à une compta en partie
double réelle reste une évolution ultérieure.

## Critères d'Acceptation

### CA-1 (Nominal) : Export FEC d'une période clôturée

```gherkin
GIVEN la période "2026-11" est clôturée et ses marges figées (US-071)
  AND un mapping de comptes est configuré pour le tenant (produit, tiers, charge, contrepartie)
WHEN un directeur financier exporte la période au format FEC
THEN un fichier FEC est généré, nommé "<SIREN>FEC<AAAAMMJJ>.txt" (JJ = dernier jour de la période)
  AND il contient les 18 champs obligatoires dans l'ordre normé, séparés par tabulation, encodés UTF-8,
      première ligne = en-tête des noms de champs
  AND chaque reconnaissance (CA, coût) produit une écriture équilibrée (somme débit = somme crédit)
  AND les montants sont au format décimal FEC (séparateur virgule, 2 décimales, pas d'arrondi destructeur)
  AND EcritureDate / PieceDate / ValidDate sont au format AAAAMMJJ, cohérentes avec la clôture
```

### CA-2 (Contrainte) : Conformité stricte des 18 champs FEC

```gherkin
GIVEN le fichier FEC généré
WHEN il est contrôlé (structure normée)
THEN les colonnes sont exactement, dans l'ordre : JournalCode, JournalLib, EcritureNum, EcritureDate,
     CompteNum, CompteLib, CompAuxNum, CompAuxLib, PieceRef, PieceDate, EcritureLib, Debit, Credit,
     EcritureLet, DateLet, ValidDate, Montantdevise, Idevise
  AND EcritureNum est séquentiel et unique par journal
  AND un champ non applicable est vide (jamais "N/A"), Debit XOR Credit non nul par ligne
  AND le total Debit = total Credit sur l'ensemble du fichier (INV-2 — centimes entiers en interne)
```

### CA-3 (Habilitation) : Réservé finance/direction (HAB-1)

```gherkin
GIVEN un utilisateur sans habilitation coût/finance (ni VIEW_PROJECT_FINANCIALS + VIEW_COLLABORATOR_COST)
WHEN il tente d'exporter le FEC
THEN l'export est refusé (deny-by-default, 403)
  AND tout export réussi est tracé (auteur, période, date — HAB-6)
```

### CA-4 (Erreur) : Période non clôturée ou mapping absent

```gherkin
GIVEN une période non clôturée (marges provisoires) OU un mapping de comptes non configuré
WHEN un utilisateur tente l'export FEC
THEN l'export est refusé avec un message explicite
  ("période non clôturée — export non opposable" / "mapping de comptes requis")
  AND aucun fichier partiel n'est produit
```

## Critères UI/UX

### Web
- Bouton « Export FEC » sur le tableau de bord finance (US-073), avec sélecteur de période (mois **clôturé** uniquement) ; téléchargement serveur (aucun calcul front).
- Écran de configuration du mapping de comptes (par tenant) accessible aux rôles habilités.

### Mobile
- Hors périmètre (fonction desktop de contrôle de gestion).

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| T-074-01 | [DOC] | ADR-0021 « périmètre export FEC » | ✅ | 1h |
| T-074-02/03 | [DB] | `FecConfiguration` (SIREN + comptes) + migration RLS | ✅ | 4h |
| T-074-04 | [BE] | `FecLine` + `FecGenerator` (18 champs, débit=crédit) | ✅ | 4h |
| T-074-05 | [BE] | `ExportFec` (période clôturée + gating HAB-1/HAB-6) | ✅ | 3h |
| T-074-06 | [FE-WEB] | Téléchargement `/finance/export/fec` + config `/finance/config-fec` | ✅ | 3h |
| T-074-07 | [TEST] | Conformité 18 champs, équilibre, gating, période/config, RLS | ✅ | 3h |
| T-074-09 | [REV] | Revue de clôture (`symfony-reviewer` — approuvé, réserves traitées) | ✅ | 1h |

## Progression

7/7 tâches complétées (100%) — livré PR #49

## Definition of Done

- [ ] Fichier FEC conforme (18 champs, encodage, nommage, débit=crédit) vérifié par test
- [ ] Mapping de comptes configurable par tenant · gating HAB-1/HAB-6 · période clôturée uniquement
- [ ] ADR léger « périmètre export FEC » (T-074-01)
- [ ] `make ci` vert · revue de clôture · recette sur données peuplées (QUAL-1)

---

## Notes

**Interface comptable réelle** (connecteur logiciel compta) : hors périmètre — on produit le fichier FEC,
pas une intégration directe. **Compta en partie double complète** : évolution ultérieure d'EPIC-005.
