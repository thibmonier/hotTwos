# US-065: Audit et mise en conformité accessibilité (WCAG 2.2 AA)

## Métadonnées
- **ID**: US-065
- **EPIC**: EPIC-012
- **Sprint**: À planifier (fast-track)
- **Statut**: ✅ Done (livré Sprint 7)
- **Points**: 5
- **Persona**: Tous (P1 à P6) — interface utilisable par tous, y compris avec aides techniques
- **Créé le**: 2026-09-02
- **Mis à jour**: 2026-09-02

## Traçabilité
- **Implémente**: EPIC-012 (D5 — conformité WCAG 2.2 AA), OBJ-7 (adoption — accessibilité comme critère produit)
- **Dépend de**: US-061 (design system — contrastes des tokens), US-063 (layout), US-064 (écrans reskinnés)
- **Spec Technique**: WCAG 2.2 niveau AA

## User Story

**En tant que** utilisateur, y compris en situation de handicap ou utilisant une aide technique,
**je veux** que les écrans du lot 1 soient audités et mis en conformité WCAG 2.2 niveau AA (contrastes, cibles tactiles, focus visible, ARIA, navigation clavier, lecteurs d'écran),
**afin de** pouvoir utiliser l'application pleinement, l'accessibilité étant un critère produit et non une option.

## Critères d'Acceptation

### CA-1 (Nominal) : Audit WCAG 2.2 AA réalisé et écrans conformes

```gherkin
GIVEN les écrans du lot 1 sont reskinnés (US-064)
WHEN l'audit d'accessibilité WCAG 2.2 AA est mené (accessibility-expert)
THEN chaque écran du lot 1 est audité sur les critères AA (contraste, focus, cibles, ARIA, structure sémantique)
  AND les non-conformités relevées sont corrigées ou tracées avec un plan de remédiation
  AND un rapport d'audit documente le statut de conformité par écran
```

### CA-2 (Alternatif) : Navigation clavier et focus visible

```gherkin
GIVEN un utilisateur navigue au clavier uniquement
WHEN il parcourt un écran du lot 1
THEN tous les éléments interactifs sont atteignables et actionnables au clavier
  AND l'indicateur de focus est visible en permanence (contraste suffisant)
  AND l'ordre de tabulation est logique et cohérent avec la lecture visuelle
  AND aucun piège au clavier n'est présent (on peut toujours sortir d'un composant)
```

### CA-3 (Alternatif) : Lecteurs d'écran et sémantique ARIA

```gherkin
GIVEN un utilisateur emploie un lecteur d'écran
WHEN il consulte un écran (formulaire de saisie, tableau de complétude)
THEN les champs de formulaire ont des libellés associés et les erreurs sont annoncées
  AND les tableaux de données ont une structure sémantique correcte (en-têtes associés)
  AND les composants dynamiques (drawer, infobulles) exposent les rôles/états ARIA nécessaires
  AND l'information portée par la couleur est également disponible en texte/icône
```

### CA-4 (Erreur) : Contraste et cibles insuffisants détectés et corrigés

```gherkin
GIVEN un composant présente un contraste texte/fond < 4.5:1 ou une cible tactile < 44 px
WHEN l'audit le détecte
THEN le composant est corrigé pour atteindre le seuil AA (ou le token corrigé en amont — US-061)
  AND la correction est vérifiée à nouveau (re-test)
  AND aucun écran du lot 1 ne conserve de non-conformité AA bloquante à la clôture
```

### CA-5 (Erreur) : Non-régression accessibilité outillée

```gherkin
GIVEN des corrections d'accessibilité ont été apportées
WHEN les écrans évoluent (reskin, nouveaux composants)
THEN un contrôle automatisé d'accessibilité (ex. axe-core) est exécutable sur les écrans du lot 1
  AND il ne remonte aucune violation critique/sérieuse sur le périmètre audité
  AND le contrôle est documenté pour réutilisation sur les écrans des lots suivants
```

## Critères UI/UX

### Web
- Focus visible sur tous les éléments interactifs, ordre de tabulation cohérent.
- Contrastes AA, information jamais portée par la seule couleur, libellés explicites.

### Mobile
- Cibles tactiles ≥ 44 × 44 px, gestes alternatifs disponibles (pas d'action réservée au survol).
- Compatibilité avec les lecteurs d'écran mobiles (VoiceOver / TalkBack) sur les parcours clés.

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Audit WCAG 2.2 AA réalisé (rapport par écran)
- [ ] Non-conformités bloquantes corrigées et re-testées
- [ ] Navigation clavier, focus, ARIA, lecteurs d'écran vérifiés
- [ ] Contrôle automatisé (axe-core) sans violation critique/sérieuse
- [ ] `make ci` vert

---

## Notes

**Niveau visé** : WCAG 2.2 **AA** (critère bloquant EPIC-012). L'agent `accessibility-expert` conduit l'audit ; les corrections de tokens remontent en US-061.

**Acquis** : F-S5-1 (cibles tactiles mobile) déjà conforme — à confirmer dans l'audit global.

**Réutilisabilité** : le dispositif de contrôle (axe-core + check-list) est documenté pour être appliqué aux écrans des lots suivants.
