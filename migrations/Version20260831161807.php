<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 2 (TECH-1) — durcissement analytique versionné (US-005, CA-4/CA-6).
 *
 * Porte le DDL jusqu'ici appliqué par `AnalyticsSchemaHardener` : isolation RLS (FORCE)
 * sur les tables du schéma en étoile et protection anti-écriture directe des faits
 * (seul le projecteur, en transaction marquée `app.projector_active`, peut écrire).
 * Idempotent (rejouable).
 */
final class Version20260831161807 extends AbstractMigration
{
    private const array ANALYTICAL_TABLES = ['fact_project_revenue', 'dim_period'];
    private const array FACT_TABLES = ['fact_project_revenue'];

    public function getDescription(): string
    {
        return 'Durcissement analytique : RLS (FORCE) + trigger anti-écriture directe des faits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
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

        foreach (self::ANALYTICAL_TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('DROP POLICY IF EXISTS tenant_isolation ON %s', $table));
            $this->addSql(sprintf(
                "CREATE POLICY tenant_isolation ON %s USING (tenant_id::text = current_setting('app.current_tenant', true))",
                $table,
            ));
        }

        foreach (self::FACT_TABLES as $table) {
            $this->addSql(sprintf('DROP TRIGGER IF EXISTS guard_direct_write ON %s', $table));
            $this->addSql(sprintf(
                'CREATE TRIGGER guard_direct_write BEFORE INSERT OR UPDATE OR DELETE ON %s'
                . ' FOR EACH ROW EXECUTE FUNCTION analytics_guard_direct_write()',
                $table,
            ));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::FACT_TABLES as $table) {
            $this->addSql(sprintf('DROP TRIGGER IF EXISTS guard_direct_write ON %s', $table));
        }

        foreach (self::ANALYTICAL_TABLES as $table) {
            $this->addSql(sprintf('DROP POLICY IF EXISTS tenant_isolation ON %s', $table));
            $this->addSql(sprintf('ALTER TABLE %s NO FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE %s DISABLE ROW LEVEL SECURITY', $table));
        }

        $this->addSql('DROP FUNCTION IF EXISTS analytics_guard_direct_write()');
    }
}
