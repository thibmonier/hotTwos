<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics;

use App\Domain\Analytics\AnalyticsSchemaGuard;
use Doctrine\DBAL\Connection;

/**
 * Durcissement PostgreSQL des tables analytiques (US-005, CA-4/CA-6) : double barrière
 * d'isolation (RLS) et protection anti-écriture directe (trigger).
 *
 * Idempotent (rejouable). Au Walking Skeleton, invoqué par une commande d'ops et par les
 * tests ; en production, ce DDL relèvera d'une migration Doctrine (ARC-34).
 */
final readonly class AnalyticsSchemaHardener implements AnalyticsSchemaGuard
{
    /** Tables de faits et de dimensions à cloisonner par RLS. */
    private const array ANALYTICAL_TABLES = ['fact_project_revenue', 'dim_period'];

    /** Tables de faits protégées contre l'écriture directe (seul le projecteur écrit). */
    private const array FACT_TABLES = ['fact_project_revenue'];

    public function __construct(private Connection $connection)
    {
    }

    public function harden(): void
    {
        $this->installDirectWriteGuard();

        foreach (self::ANALYTICAL_TABLES as $table) {
            $this->enableRowLevelSecurity($table);
        }

        foreach (self::FACT_TABLES as $table) {
            $this->attachDirectWriteGuard($table);
        }
    }

    private function installDirectWriteGuard(): void
    {
        // La fonction rejette toute écriture hors contexte projecteur (ARC-111, CA-6).
        $this->connection->executeStatement(<<<'SQL'
            CREATE OR REPLACE FUNCTION analytics_guard_direct_write() RETURNS trigger AS $$
            BEGIN
                IF current_setting('app.projector_active', true) IS DISTINCT FROM 'on' THEN
                    RAISE EXCEPTION 'Écriture directe interdite dans les tables analytiques : utiliser le canal événementiel';
                END IF;
                IF TG_OP = 'DELETE' THEN
                    RETURN OLD;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            SQL);
    }

    private function attachDirectWriteGuard(string $table): void
    {
        $this->connection->executeStatement(sprintf('DROP TRIGGER IF EXISTS guard_direct_write ON %s', $table));
        $this->connection->executeStatement(sprintf(
            'CREATE TRIGGER guard_direct_write BEFORE INSERT OR UPDATE OR DELETE ON %s'
            .' FOR EACH ROW EXECUTE FUNCTION analytics_guard_direct_write()',
            $table,
        ));
    }

    private function enableRowLevelSecurity(string $table): void
    {
        // FORCE : la politique s'applique même au propriétaire de la table (le rôle
        // applicatif) ; seule reste exemptée l'identité superutilisateur.
        $this->connection->executeStatement(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
        $this->connection->executeStatement(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
        $this->connection->executeStatement(sprintf('DROP POLICY IF EXISTS tenant_isolation ON %s', $table));
        // Comparaison en `text` des deux côtés : agnostique au type de la colonne
        // (uuid natif ou char(36)) et sûr quand le réglage de session est absent (NULL).
        $this->connection->executeStatement(sprintf(
            'CREATE POLICY tenant_isolation ON %s'
            ." USING (tenant_id::text = current_setting('app.current_tenant', true))",
            $table,
        ));
    }
}
