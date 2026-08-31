-- US-007 / ADR-6 — active pgvector dès la création de la base.
-- Exécuté une seule fois par PostgreSQL au premier démarrage du volume.
CREATE EXTENSION IF NOT EXISTS vector;
