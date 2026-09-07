# US-020: Journal d'audit du paramétrage

## Métadonnées
- **ID**: US-020
- **EPIC**: EPIC-001
- **Sprint**: 16
- **Statut**: 🟢 Ready
- **Points**: 3
- **Persona**: P-ADMIN (administrateur) / P6 (Dirigeant, lecture)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-09-07 (affinage S16 — recadrage journal immuable + lecture gated)

## Traçabilité
- **Implémente**: EF-REF-33 (journal qui/quoi/avant-après/quand), INV-7 (immuabilité), HAB-6 (accès restreint)
- **Dépend de**: US-001 (multi-tenant)
- **Réutilise** : l'infrastructure d'audit de sécurité existante (`SecurityAuditLogger` — lectures sensibles) comme point de départ du même journal.
- **Reporté (hors périmètre)** : reconstitution « point-in-time » d'un paramètre, alertes sur modifications hors heures ouvrables, export CSV, auto-journalisation des tentatives d'accès refusées → US ultérieures si besoin.

## User Story

**En tant qu'** administrateur tenant,
**je veux** que toute modification d'un **paramètre de configuration** soit enregistrée dans un **journal d'audit immuable** (qui, quoi, valeur avant, valeur après, quand),
**afin de** garantir la traçabilité du paramétrage et faciliter les audits de conformité.

## Contexte (Conversation)
Il existe un `SecurityAuditLogger` pour les lectures sensibles. Cette US ajoute un **journal des
changements de configuration** (append-only) et l'**instrumente** sur les écrans de paramétrage
livrés/existants (jours fériés US-012, compétences US-013, seuils de dérive marge/charge). L'objectif
est le socle traçable + une lecture réservée (HAB-6), pas l'exhaustivité de tous les paramètres dès ce sprint.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : une modification de paramètre est journalisée (qui/quoi/avant/après/quand)
```gherkin
GIVEN l'administrateur « alice@agence.fr » modifie le seuil d'alerte de dérive de charge « forfait » de 10 % à 8 %
WHEN la modification est enregistrée
THEN une entrée d'audit est créée avec : auteur, action=MODIFICATION, objet=seuil dérive charge (forfait),
     valeur_avant=10, valeur_après=8, horodatage UTC
  AND l'entrée est visible dans le journal du tenant
```

### CA-2 (Nominal) : le journal est consultable, filtré et trié (récent en premier)
```gherkin
GIVEN plusieurs entrées d'audit existent pour le tenant
WHEN un administrateur ou un dirigeant consulte le journal d'audit
THEN les entrées sont triées par date décroissante
  AND filtrables au minimum par type d'objet et par auteur
```

### CA-3 (Alternatif) : plusieurs types de paramètres alimentent le même journal
```gherkin
GIVEN l'administrateur ajoute un jour férié (US-012) puis crée une compétence (US-013)
WHEN il consulte le journal
THEN deux entrées distinctes apparaissent (objet=jour férié CREATION ; objet=compétence CREATION)
  AND chacune porte l'auteur et l'horodatage
```

### CA-4 (Alternatif) : isolation multi-tenant du journal
```gherkin
GIVEN le tenant A et le tenant B ont chacun des entrées d'audit
WHEN un administrateur du tenant A consulte le journal
THEN il ne voit que les entrées de A (RLS) ; jamais celles de B
```

### CA-5 (Erreur) : immuabilité — aucune modification ni suppression d'entrée (INV-7)
```gherkin
GIVEN une entrée d'audit existe
WHEN une modification ou une suppression de cette entrée est tentée (applicatif)
THEN l'opération est impossible : le journal est append-only (aucune route/commande d'édition ou de suppression)
  AND l'entrée reste intègre
```

### CA-6 (Erreur) : lecture du journal réservée (HAB-6)
```gherkin
GIVEN un utilisateur avec le rôle « Chef de projet » (sans habilitation d'audit)
WHEN il tente d'accéder au journal d'audit du tenant
THEN l'accès est refusé (403)
  AND aucune entrée n'est retournée
```

## Notes techniques (pour la décomposition)
- **Entité** `Domain\Audit\ConfigAuditEntry` (`TenantOwned`, RLS) : `actorUserId`, `action` (enum CREATION/MODIFICATION/SUPPRESSION/DESACTIVATION), `objectType`, `objectLabel`, `field` (nullable), `valueBefore` (nullable), `valueAfter` (nullable), `recordedAt`.
- **Append-only (INV-7)** : port `ConfigAuditRecorder` avec **seulement** `record(...)` + lecture ; **aucune** méthode d'update/delete. (La contrainte GRANT INSERT-ONLY PostgreSQL est notée comme durcissement infra ultérieur ; au niveau applicatif, pas de route/commande d'édition.)
- **Instrumentation** : appeler le recorder depuis les écrans de paramétrage de ce sprint (jours fériés, compétences) et les configs de seuils existantes (`ChargeDriftThresholdController`, `MarginDriftThresholdController`). Pattern réutilisable pour étendre plus tard.
- **Lecture** : page `/parametrage/audit` (Twig, gating **nouvelle permission d'audit** ou rôles ADMIN/Dirigeant — cf. HAB-6 ; à trancher en décomposition), filtres objet/auteur, tri desc.
- **Tests** : unit (recorder capture avant/après) + fonctionnels (journalisation d'une modif de seuil, isolation, 403, absence de route d'édition/suppression) ; ajouter l'entité aux SchemaTool.

## Definition of Ready
- [x] Description INVEST recadrée (socle append-only + lecture gated)
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Estimation 3 pts confirmée
- [x] Dépendances (US-001, réutilise SecurityAuditLogger) ; RLS/HAB-6 explicités

## Definition of Done
- [ ] CA validés (unit + fonctionnels), `make ci` vert (PHPStan max, Deptrac, couv. ≥ 80 %)
- [ ] Migration + RLS ; append-only garanti (pas de route d'édition/suppression) ; code review

---

## Notes
Rétention légale (7 ans) et archivage : hors périmètre (lot ultérieur). Reconstitution point-in-time,
alertes hors-heures et export CSV : reportés. Le journal est conçu pour être **étendu** progressivement
à tous les paramètres (EF-REF-33), en commençant par ceux touchés ce sprint.
