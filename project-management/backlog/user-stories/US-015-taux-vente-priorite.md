# US-015: Taux de vente et règle de priorité

## Métadonnées
- **ID**: US-015
- **EPIC**: EPIC-001
- **Sprint**: Sprint 2
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: ADMIN / P4 Yann (Commercial)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-REF-19, RG-REF-1
- **Dépend de**: US-011 (référentiel profils taux), US-014 (comptes clients)
- **Spec Technique**: EF-REF-19 (taux par profil/client/projet, règle de priorité projet > client > profil)

## User Story

**En tant que** commercial (P4 Yann) et administrateur tenant,
**je veux** définir des taux de vente à trois niveaux de granularité (profil, client, projet) avec une règle de priorité explicite projet > client > profil systématiquement affichée lors du chiffrage,
**afin de** permettre des accords commerciaux personnalisés par client ou projet tout en conservant un référentiel tarifaire par défaut cohérent, et de garantir la transparence sur le taux effectivement appliqué à chaque situation.

## Critères d'Acceptation

### CA-1 (Nominal) : Règle de priorité affichée lors du chiffrage — taux projet prioritaire
```gherkin
GIVEN le profil "Consultant Senior" a un taux standard de 750 €/j (niveau profil)
  AND un taux client de 720 €/j est configuré pour le client "Airbus" (niveau client)
  AND un taux projet de 700 €/j est configuré pour le projet "MRO Digital" chez Airbus (niveau projet)
WHEN P4 Yann ouvre le module de chiffrage pour le projet "MRO Digital" et sélectionne le profil "Consultant Senior"
THEN le taux affiché est 700 €/j (priorité projet)
  AND un encart "Taux appliqué" indique : "700 €/j — Taux projet (priorité 1) | Taux client disponible : 720 €/j | Taux profil : 750 €/j"
  AND Yann peut visualiser les trois niveaux en un clic sans quitter l'écran de chiffrage
```

### CA-2 (Nominal) : Taux client appliqué en l'absence de taux projet
```gherkin
GIVEN le profil "Développeur Junior" a un taux standard de 500 €/j (niveau profil)
  AND un taux client de 480 €/j est configuré pour le client "Société Générale" (niveau client)
  AND aucun taux projet spécifique n'est défini pour "Projet Core Banking" chez SG
WHEN Yann chiffre le projet "Projet Core Banking" avec le profil "Développeur Junior"
THEN le taux affiché est 480 €/j (priorité client, en l'absence de taux projet)
  AND l'encart indique : "480 €/j — Taux client (priorité 2) | Taux profil : 500 €/j | Aucun taux projet configuré"
  AND la différence avec le taux profil est affichée en valeur absolue (−20 €/j, soit −4 %)
```

### CA-3 (Alternatif) : Création d'un taux client avec date d'effet
```gherkin
GIVEN le client "Renault" n'a pas de taux spécifique configuré pour le profil "Chef de Projet"
WHEN l'ADMIN crée un taux client de 680 €/j pour Renault / Chef de Projet avec date d'effet 01/09
THEN ce taux est actif à partir du 01/09 uniquement
  AND les devis et projets antérieurs au 01/09 qui référencent ce couple Renault/Chef de Projet conservent l'ancien taux profil (750 €/j)
  AND la fiche client Renault affiche l'historique des taux par profil avec dates d'effet
```

### CA-4 (Alternatif) : Simulation d'impact d'un changement de taux client
```gherkin
GIVEN le taux client "Capgemini / Architecte" est de 850 €/j
  AND 3 projets actifs utilisent ce couple client/profil pour un total de 45 jours planifiés
WHEN l'ADMIN simule un passage à 900 €/j (sans enregistrer)
THEN le simulateur affiche : "Impact : +50 €/j × 45 jours = +2 250 € sur les projets actifs"
  AND la liste des 3 projets impactés est affichée avec leur volume respectif
  AND l'ADMIN peut confirmer ou annuler sans effet tant que la simulation n'est pas validée
```

### CA-5 (Erreur) : Taux projet incohérent (supérieur au taux profil de plus de 50 %) → avertissement
```gherkin
GIVEN le profil "Consultant Junior" a un taux profil de 500 €/j
WHEN Yann tente de créer un taux projet à 800 €/j (soit +60 % par rapport au taux profil)
THEN le système affiche un avertissement non bloquant : "Le taux projet (800 €/j) dépasse de 60 % le taux profil (500 €/j). Confirmer ce taux inhabituellement élevé ?"
  AND Yann peut confirmer et enregistrer le taux malgré l'avertissement (c'est un avertissement, pas un refus)
  AND l'avertissement est journalisé dans le log d'audit avec l'identifiant de l'utilisateur
```

### CA-6 (Erreur) : Aucun taux applicable trouvé lors du chiffrage → blocage explicite
```gherkin
GIVEN le profil "Data Scientist" n'a aucun taux profil configuré (champ absent ou à 0 €/j)
  AND aucun taux client ni taux projet n'est défini pour ce profil dans le contexte du chiffrage courant
WHEN P4 Yann tente de chiffrer le projet "IA Analytics" en sélectionnant le profil "Data Scientist"
THEN le système bloque le chiffrage et affiche : "Aucun taux applicable trouvé pour le profil 'Data Scientist' (ni taux projet, ni taux client, ni taux profil). Configurez un taux avant de poursuivre."
  AND le bouton "Valider le chiffrage" est désactivé tant qu'aucun taux n'est défini à au moins un des trois niveaux
  AND un lien direct vers la configuration du taux profil "Data Scientist" est proposé dans le message d'erreur
```

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Code reviewé
- [ ] Tests unitaires passent
- [ ] Tests d'intégration passent
- [ ] Documentation mise à jour

---

## Notes

La règle de priorité projet > client > profil (EF-REF-19) est implémentée dans un service dédié `TarifResolutionService` qui est l'unique point d'entrée pour déterminer le taux effectif. Ce service est utilisé par le module de chiffrage, la facturation et les rapports de rentabilité. Toute évolution de la règle de priorité ne modifie que ce service.

La transparence de la règle de priorité (affichage des 3 niveaux lors du chiffrage) est un invariant fonctionnel non négociable : l'utilisateur doit toujours savoir POURQUOI ce taux est appliqué, pas seulement QUEL taux est appliqué.

L'historisation des taux s'appuie sur le même mécanisme que US-011 (date d'effet). Cette US dépend fonctionnellement de US-011 pour les taux au niveau profil.
