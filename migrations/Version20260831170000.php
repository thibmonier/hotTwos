<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 2 (TECH-2) — rôle applicatif non-superutilisateur pour la RLS au runtime.
 *
 * En production, l'application se connecte via ce rôle (`hotones_app`) : n'étant ni
 * superutilisateur ni propriétaire des tables, il est **réellement soumis aux politiques
 * RLS** (ARC-34). Les migrations, elles, continuent via un rôle privilégié.
 *
 * Idempotent. Le positionnement du tenant courant par requête est assuré côté application
 * ({@see \App\Infrastructure\Tenant\TenantSessionConfigurator}).
 */
final class Version20260831170000 extends AbstractMigration
{
    private const string APP_ROLE = 'hotones_app';

    public function getDescription(): string
    {
        return 'Rôle applicatif non-superutilisateur (hotones_app) soumis à la RLS';
    }

    public function up(Schema $schema): void
    {
        // Résilient au niveau de privilège de l'hôte : sur un PostgreSQL managé sans droit
        // de créer des rôles, on n'échoue pas — la RLS runtime reste alors inactive et
        // l'isolation repose sur le filtre ORM (ARC-33). Idempotent.
        $this->addSql(sprintf(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '%1$s') THEN
                    CREATE ROLE %1$s NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOLOGIN;
                END IF;
                GRANT USAGE ON SCHEMA public TO %1$s;
                GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO %1$s;
                GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO %1$s;
                ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO %1$s;
                ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO %1$s;
            EXCEPTION WHEN insufficient_privilege THEN
                RAISE NOTICE 'Rôle applicatif %1$s non configuré (privilège insuffisant sur cet hôte) — RLS runtime inactive, isolation portée par le filtre ORM.';
            END
            $$;
            SQL, self::APP_ROLE));
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sprintf(<<<'SQL'
            DO $$
            BEGIN
                IF EXISTS (SELECT FROM pg_roles WHERE rolname = '%1$s') THEN
                    ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE SELECT, INSERT, UPDATE, DELETE ON TABLES FROM %1$s;
                    ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE USAGE, SELECT ON SEQUENCES FROM %1$s;
                    REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM %1$s;
                    REVOKE ALL ON ALL TABLES IN SCHEMA public FROM %1$s;
                    REVOKE USAGE ON SCHEMA public FROM %1$s;
                    DROP ROLE %1$s;
                END IF;
            EXCEPTION WHEN insufficient_privilege THEN
                RAISE NOTICE 'Rôle applicatif %1$s non supprimé (privilège insuffisant).';
            END
            $$;
            SQL, self::APP_ROLE));
    }
}
