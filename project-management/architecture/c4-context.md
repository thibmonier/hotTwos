# C4 — Niveau 1 : Contexte système HotOnes

**Projet :** HotOnes — ERP SaaS agence digitale / ESN
**Date :** 2026-08-31
**Réf. tech-spec :** §2.1

---

## Diagramme de contexte

```mermaid
C4Context
    title Contexte système — HotOnes ERP SaaS

    %% ─── Utilisateurs internes (personas P1-P6) ───
    Person(p1, "P1 · Camille", "Collaboratrice\n(développeuse)\n80 % des utilisateurs")
    Person(p2, "P2 · Marc", "Chef de projet")
    Person(p3, "P3 · Sophie", "Resource Manager\n/ Dir. production")
    Person(p4, "P4 · Yann", "Commercial\n/ Dir. de clientèle")
    Person(p5, "P5 · Nadia", "Responsable RH")
    Person(p6, "P6 · Élodie", "Dirigeante\n/ Associée")

    %% ─── Acteurs secondaires ───
    Person(admin, "Admin Tenant", "Paramètre l'instance\n(rôles, taux, calendriers)")
    Person(editeur, "Éditeur HotOnes", "Supervision multi-tenant\nAccès exceptionnel, tracé\n(ENF-SEC-8)")

    %% ─── Système central ───
    System(hotones, "HotOnes", "ERP SaaS agence / ESN\nMonolithe modulaire Symfony 8.1\nFrankenPHP worker · PostgreSQL\nMulti-tenant · API Platform\nSymfony AI")

    %% ─── Systèmes externes lot 1 ───
    System_Ext(ai_provider, "Fournisseur d'inférence IA (UE)", "OpenAI / Anthropic / Mistral\nou Ollama local\nClé API fournie par le tenant\n(ADR-10 · ARC-73 · CTR-5)")
    System_Ext(sso, "Fournisseur SSO", "OIDC / SAML\n(Google Workspace, Azure AD…)")

    %% ─── Systèmes externes lots 2-3 ───
    System_Ext(calendar, "Calendrier", "Google Calendar\nMicrosoft 365")
    System_Ext(jira, "Gestion de tâches / tickets", "Jira, Linear\n(intégration lecture seule)")
    System_Ext(compta, "Logiciel comptable", "Export EF-FIN-22\n(achats, factures)")

    %% ─── Systèmes externes lots 3-4 ───
    System_Ext(paie, "Logiciel de paie", "Export éléments variables\nEF-RH-18")
    System_Ext(signature, "Signature électronique", "DocuSign, Yousign…\nEF-CRM-19")
    System_Ext(crm_ext, "CRM externe", "Synchronisation optionnelle\n(bidirectionnel borné)")
    System_Ext(jobboard, "Job boards / ATS", "Indeed, LinkedIn, Welcome\nEF-REC-7")

    %% ─── Relations — utilisateurs → HotOnes ───
    Rel(p1, hotones, "Saisie de temps\n≤ 2 min/semaine (ENF-UX-1)\nMobile + desktop", "HTTPS")
    Rel(p2, hotones, "Suivi projet\nDétection de dérive\nAvancement / RAF", "HTTPS")
    Rel(p3, hotones, "Plan de charge\nAffectation\nCapacité nette", "HTTPS")
    Rel(p4, hotones, "Pipeline commercial\nDevis\nCapacité prévisionnelle", "HTTPS")
    Rel(p5, hotones, "RH — entretiens\nCompétences\nRecrutement", "HTTPS")
    Rel(p6, hotones, "Pilotage\nMarge · Taux d'occupation\nVue explicable", "HTTPS")
    Rel(admin, hotones, "Administration tenant\nRôles, taux, calendriers\nOnboarding < 15 min (ENF-SAAS-2)", "HTTPS")
    Rel(editeur, hotones, "Supervision multi-tenant\nSupport technique\nAccès exceptionnel tracé + notifié", "HTTPS")

    %% ─── Relations — HotOnes → systèmes externes ───
    Rel(hotones, ai_provider, "Inférence IA\nContexte filtré (ARC-9/HAB-5)\nClé tenant · UE uniquement", "HTTPS/REST")
    Rel(hotones, sso, "Authentification déléguée\nOIDC / SAML (lot 1 Should)", "OIDC/SAML")
    Rel(hotones, calendar, "Import planning\nRéunions → signaux pré-remplissage\n(lot 1-2)", "HTTPS/CalDAV")
    Rel(hotones, jira, "Import statut tickets\nLecture seule (lot 2)", "HTTPS/REST")
    Rel(hotones, compta, "Export factures\nEF-FIN-22 (lot 2)", "CSV/API")
    Rel(hotones, paie, "Export éléments variables\nEF-RH-18 (lot 4)", "CSV/API")
    Rel(hotones, signature, "Envoi contrats\nStatut retour (lot 3)", "HTTPS/Webhook")
    Rel(hotones, crm_ext, "Synchro opportunités\nBidirectionnel borné (lot 3)", "HTTPS/REST")
    Rel(hotones, jobboard, "Publication offres\nImport candidatures (lot 4)", "HTTPS/REST")
```

---

## Légende et notes

### Utilisateurs

| Persona | Fréquence | Volume | Exigence clé |
|---------|-----------|--------|-------------|
| P1 Camille — Collaborateur | Quotidien, 2-5 min | 80 % des comptes | `ENF-UX-1` : saisie ≤ 2 min/semaine — bloquant lot 1 |
| P2 Marc — Chef de projet | Quotidien-hebdo, 20-40 min | 10-15 % | Détection de dérive avant 50 % budget (`OBJ-2`) |
| P3 Sophie — Resource Manager | Hebdo, 1-2 h | 1-5 % | Plan de charge 4-12 semaines sans tableur |
| P4 Yann — Commercial | Quotidien, 15-30 min | 3-8 % | Capacité réelle avant engagement de date |
| P5 Nadia — RH | Hebdo, 1-3 h | 1-3 % | Données entretien cloisonnées (`HAB-2`) |
| P6 Élodie — Dirigeante | Hebdo-mensuel, 30 min | 1 % | Indicateurs explicables, réconciliés comptabilité |
| Admin Tenant | Occasionnel | 1 par tenant | Onboarding < 15 min sans intervention infra (`ENF-SAAS-2`) |
| Éditeur HotOnes | Exceptionnel | Équipe produit | Accès tracé et notifié au tenant (`ENF-SEC-8`, `ARC-37`) |

### Systèmes externes — priorités par lot

| Priorité | Système | Lot | Nature |
|----------|---------|-----|--------|
| Must lot 1 | Fournisseur IA (UE) | 1 | Sortant — clé par tenant, filtrage à la source (`ARC-9`) |
| Should lot 1 | SSO (OIDC/SAML) | 1 | Entrant — fallback authentification locale |
| Should lot 2 | Calendrier | 2 | Entrant — signaux pré-remplissage (consentement `INT-5`) |
| Should lot 2 | Jira/Linear | 2 | Entrant — lecture statut tickets (`EF-PRJ-25`) |
| Should lot 2 | Comptabilité | 2 | Sortant — export factures (`EF-FIN-22`) |
| Should lot 3 | Signature électronique | 3 | Sortant + retour statut (`EF-CRM-19`) |
| Could lot 3 | CRM externe | 3 | Bidirectionnel borné |
| Should lot 4 | Logiciel de paie | 4 | Sortant — éléments variables (`EF-RH-18`) |
| Could lot 4 | Job boards / ATS | 4 | Sortant + entrant (`EF-REC-7`) |

### Contraintes de souveraineté

`CTR-5` / `ENF-RGPD-7` : tout hébergement et tout traitement IA est dans l'Union européenne. Le fournisseur d'inférence est choisi par le tenant parmi les fournisseurs supportés disposant de régions UE (ou un point d'accès Ollama auto-hébergé). HotOnes ne transit pas de données tenant vers un hébergement hors UE.

### Points ouverts liés au contexte

- `ARB-3` / `CTR-5` : choix du fournisseur d'inférence IA UE par l'éditeur (pour l'offre avec inférence incluse, `ARB-24`) — à trancher au lot 0.
- `ARB-25` : hébergement de production UE — à instruire au lot 2.

---

**Document suivant :** `c4-container.md` (niveau 2 — conteneurs)
