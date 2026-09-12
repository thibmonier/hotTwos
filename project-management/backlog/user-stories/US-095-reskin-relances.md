# US-095: PG-REL-01 — Reskin relances

## Métadonnées
- **ID**: US-095
- **EPIC**: EPIC-003 (Temps & activité)
- **Sprint**: 22
- **Statut**: 🟢 Ready
- **Points**: 2
- **Persona**: P2/P3 (manager / responsable équipe)
- **Créé le**: 2026-09-12

## Traçabilité
- **Backlog reskin**: `backlog-reskin-priorise.md` — Should, PG-REL-01 (EPIC-003), 2 pts
- **Écran existant**: `templates/reminder/*` (relances, routes `reminders_page` GET / `reminders_update` POST, US-056)
- **Lien**: cible de « Relancer les retards » depuis la complétude (US-092 / US-092b)

## User Story
**En tant que** manager (P2/P3),
**je veux** l'écran de relances porté sur le socle tailsfadmin, cohérent avec la complétude,
**afin de** gérer les relances de saisie dans une interface homogène et accessible.

## Contexte (Conversation)
Écran existant (US-056) à porter sur les tokens tailsfadmin, en cohérence avec la complétude (US-092/092b)
qui y renvoie. Logique de relance inchangée ; présentation alignée sur le design system (process assoupli :
charte appliquée directement, pas de maquette HF dédiée S20).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : reskin conforme
```gherkin
GIVEN l'écran de relances
WHEN il est reskinné
THEN il n'utilise que des composants/tokens tailsfadmin
  AND la logique de relance (préférences, envoi) est préservée (tests existants verts)
```

### CA-2 (Alternatif) : cohérence avec la complétude
```gherkin
GIVEN l'arrivée depuis « Relancer les retards » de /completude
WHEN j'atterris sur /relances
THEN l'interface est cohérente (mêmes tokens, badges statut) avec l'écran de complétude
```

### CA-3 (Erreur) : sans permission
```gherkin
GIVEN un utilisateur non habilité
WHEN il accède à /relances
THEN l'accès est refusé/masqué conformément aux voters existants
```

## Notes sur les livrables
- Reskin `templates/reminder/*` sur tokens tailsfadmin ; hooks préservés.
- **Accessibilité** : WCAG 2.2 AA — vérifiée par le job US-093.

## Definition of Ready
- [x] Écran existant identifié ; cohérence avec US-092b (complétude) ciblée
- [x] INVEST : Estimable ✓ (2 pts) / Testable ✓

## Definition of Done
- [ ] Écran reskinné sur tokens tailsfadmin ; logique inchangée (tests existants verts)
- [ ] Cohérence visuelle avec la complétude ; états (sans-permission) couverts ; WCAG AA attesté (US-093)
- [ ] CI verte ; PR mergée
