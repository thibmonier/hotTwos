# Tâches Techniques Transverses — Sprint 9

Issues de la rétrospective S8 (dette reconduite) + fiabilisation.

| ID | Type | Tâche | Estimation | Statut |
|----|------|-------|------------|--------|
| T-TECH-01 | [OPS] | **Recette navigateur sur données peuplées** des écrans valorisation enrichie (par-projet, occupation) + auth (mot de passe oublié), tracée dans `.recette/` — **action rétro reconduite (S7→S8→S9), priorité haute** | 3h | 🔲 |
| T-TECH-02 | [OPS] | `MAILER_DSN` staging (mailpit/SMTP) + test e2e du parcours « mot de passe oublié » | 2h | 🔲 |
| T-TECH-03 | [OPS] | Fiabiliser l'outillage : warmup du cache dev après `cache:clear` (piège récurrent `make analyse`) — note ou cible make dédiée | 1h | 🔲 |

**Total transverse : 6h**

## Notes
- T-TECH-01 est la priorité qualité du sprint (jamais rejouée depuis S7). À planifier tôt.
- T-TECH-02 : `make up` provisionne déjà un conteneur **mailpit** en local — capitaliser dessus pour la recette du reset.
