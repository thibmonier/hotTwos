# Rétrospective — Sprint 20

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-09-25 (fin de sprint nominale ; livraison effective 2026-09-11) |
| Format | Starfish (⭐) |
| Facilitateur | Scrum Master |
| Participants | PO / dev (Thibaut) + facilitation IA |
| Sprint | 20 — Conception UX du parcours de saisie (EPIC-013, bouclage) |

## Directive Fondamentale

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux
> qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des
> ressources disponibles, et de la situation. » — Norman Kerth

## Rappel du Sprint

- **Sprint Goal** : auditer + concevoir (maquettes HF validées PO) le parcours de saisie P1, prêtes à développer.
- **Résultat** : ✅ atteint — 13/13 pts, US-082 (#122) + US-084 (#124) livrées et validées PO à 100 %.
- **Événements marquants** : accès hotones débloqué (dépendance levée) ; **EPIC-013 bouclé (6/6)**.

---

## ⭐ Observations (Starfish)

### 🟢 Continuer (ce qui fonctionne)
- **Conception UX validée avant dev** : la règle « maquettes validées avant tout dev front » a été respectée ;
  le gate `VALIDATION.md` écran par écran donne une frontière nette avant le reskin S21.
- **Délégation aux sous-agents** : Explore pour digérer les intrants S19, `accessibility-expert` pour l'audit
  WCAG des maquettes → contexte principal propre et qualité renforcée (6 bloquants a11y captés avant livraison).
- **Audit ancré dans le réel** : lecture des templates Twig existants + findings recette antérieurs
  (F-S5-4, F-S5-5, F1) plutôt qu'un audit « à vide » → recommandations directement actionnables.
- **Format design-canvas** : `.dc.html` versionnés + sortie assemblée gitignorée + Artifact publié pour la revue
  PO ; traçabilité audit → maquette claire.

### 🟡 Commencer (nouvelles idées)
- **Développer les gaps bundle en tête du S21** : G1 `Ui:StatCard` et G4 `Layout:PageHeader` (absents du bundle,
  mobilisés par presque tous les écrans) avant le portage des écrans, pour ne pas les réinventer par page.
- **Checklist « tokens & contraste AA » dès l'authoring** : l'audit a trouvé 6 bloquants (contrastes gris/bleu,
  alternatives textuelles) *après* le 1er jet ; une checklist en amont éviterait la reprise.

### 🔴 Arrêter (ce qui ne marche pas)
- **PR empilée + `--delete-branch` sur la base** : supprimer la base d'une PR empilée a **fermé la PR enfant
  (#123)** et imposé un rebase + re-création (#124). → ne pas supprimer la base tant que les PR enfants ne sont
  pas reciblées, ou éviter l'empilement et séquencer (merger la 1re, rebaser la 2e sur `main`).

### ⬆️ Plus de
- **Traçabilité audit → maquette** : la table recos → choix de maquette a bien cadré US-084 ; à systématiser.
- **Passes de revue automatisées ciblées** (a11y, cohérence tokens) sur les livrables de conception.

### ⬇️ Moins de
- **Suite qualité complète sur des commits purement documentaires** : PHPStan max + Deptrac + 697 tests (~3 min)
  se déclenchent même quand aucun fichier PHP n'est touché → coût de cycle élevé pour de la doc/maquette.

---

## Thèmes priorisés

1. **Outillage bundle** (gaps G1/G4) — condition du reskin S21. ●●●
2. **Qualité de conception en amont** (contraste/WCAG dès l'authoring). ●●
3. **Friction Git/CI** (PR empilées, hooks docs-only). ●●

---

## 🎯 Actions Sprint 21

### Action 1 : Lot bundle G1/G4 en ouverture du Sprint 21
| Attribut | Valeur |
|----------|--------|
| Description | Développer `tsf:Ui:StatCard` (G1) et `tsf:Layout:PageHeader` (G4) dans le bundle tailsfadmin **avant** le portage des écrans |
| Responsable | dev |
| Deadline | Sprint 21 (début) |
| DoD | 2 composants Twig livrés + testés + documentés, adoptés dans ≥ 1 écran reskinné |
| Priorité | Haute |

### Action 2 : Appliquer les findings d'audit au reskin
| Attribut | Valeur |
|----------|--------|
| Description | Reporter les recos bloquantes/majeures d'`audit-ux-existant.md` dans le dev : F-S5-4 (e-mail sur `/completude`, plus de `userId[:8]`), F-S5-5 (icônes de badge), totaux rendus serveur, état d'erreur de saisie |
| Responsable | dev |
| Deadline | Sprint 21 |
| DoD | Chaque écran reskinné coche ses recommandations d'audit (traçabilité dans la PR) |
| Priorité | Haute |

### Action 3 : Checklist « tokens & contraste AA » à l'authoring
| Attribut | Valeur |
|----------|--------|
| Description | Ajouter aux règles design une checklist vérifiée avant PR : contraste AA (≥ 4.5:1), cibles 44px, statut texte+icône+couleur, focus visible — appliquée dès la 1re version |
| Responsable | dev |
| Deadline | Sprint 21 |
| DoD | Checklist présente dans les rules/design ; 0 bloquant a11y découvert en revue tardive |
| Priorité | Moyenne |

### Action 4 : Alléger le hook pre-commit sur les commits docs-only
| Attribut | Valeur |
|----------|--------|
| Description | Conditionner PHPStan/Deptrac/PHPUnit à la présence de fichiers `*.php` (ou `src/`, `tests/`) dans le commit ; garder gitleaks/secrets systématique |
| Responsable | dev (via `/update-config` / hook) |
| Deadline | Sprint 21 |
| DoD | Un commit purement documentaire ne lance plus la suite PHP (gain ~3 min/commit) |
| Priorité | Moyenne |

## Suivi des actions précédentes (Sprint 19)

| Sprint | Action | Status |
|--------|--------|--------|
| S19 | Livrer audit + maquettes une fois hotones débloqué (US-082/084) | ✅ Fait (S20, #122/#124) |

## Check-out

- **Ce qu'on emporte** : EPIC-013 est bouclé ; le S21 démarre sur une cible visuelle validée, pas une
  interprétation au fil de l'eau. La priorité claire = outiller le bundle (G1/G4) avant de porter les écrans.
- **ROTI** : conception cadrée et validée sans reprise majeure — retour élevé sur le temps investi.
