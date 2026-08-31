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
        $this->addSql(sprintf(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '%1$s') THEN
                    CREATE ROLE %1$s NOSUPERUSER NOBYPASSRLS NOCREATEDB NOCREATEROLE NOLOGIN;
                END IF;
            END
            $$;
            SQL, self::APP_ROLE));

        // Privilèges minimaux : DML sur les tables applicatives, USAGE du schéma, et
        // héritage automatique pour les tables/séquences futures.
        $this->addSql(sprintf('GRANT USAGE ON SCHEMA public TO %s', self::APP_ROLE));
        $this->addSql(sprintf('GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO %s', self::APP_ROLE));
        $this->addSql(sprintf('GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO %s', self::APP_ROLE));
        $this->addSql(sprintf('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO %s', self::APP_ROLE));
        $this->addSql(sprintf('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO %s', self::APP_ROLE));
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sprintf('ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE SELECT, INSERT, UPDATE, DELETE ON TABLES FROM %s', self::APP_ROLE));
        $this->addSql(sprintf('ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE USAGE, SELECT ON SEQUENCES FROM %s', self::APP_ROLE));
        $this->addSql(sprintf('REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM %s', self::APP_ROLE));
        $this->addSql(sprintf('REVOKE ALL ON ALL TABLES IN SCHEMA public FROM %s', self::APP_ROLE));
        $this->addSql(sprintf('REVOKE USAGE ON SCHEMA public FROM %s', self::APP_ROLE));
        $this->addSql(sprintf('DROP ROLE IF EXISTS %s', self::APP_ROLE));
    }
}
