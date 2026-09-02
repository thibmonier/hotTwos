<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 6 (US-031, T-031-01) — structure de projet : lots (budget bidimensionnel charge+montant,
 * arborescence 2 niveaux) et jalons (date, statut, déclencheur de facturation). RLS dès la création
 * (double barrière, DBT-SEC-1), comparaison texte.
 */
final class Version20260902110000 extends AbstractMigration
{
    private const array TABLES = ['project_lot', 'project_milestone'];

    public function getDescription(): string
    {
        return 'US-031 : lots et jalons de projet + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project_lot (id UUID NOT NULL, tenant_id UUID NOT NULL, project_id UUID NOT NULL, name VARCHAR(150) NOT NULL, budget_days INT NOT NULL, budget_cents INT NOT NULL, parent_lot_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_lot_tenant_project ON project_lot (tenant_id, project_id)');

        $this->addSql('CREATE TABLE project_milestone (id UUID NOT NULL, tenant_id UUID NOT NULL, project_id UUID NOT NULL, name VARCHAR(150) NOT NULL, due_date DATE NOT NULL, reached_date DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, billing_trigger_cents INT DEFAULT NULL, billing_triggered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_milestone_tenant_project ON project_milestone (tenant_id, project_id)');

        foreach (self::TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf(
                "CREATE POLICY tenant_isolation ON %s USING (tenant_id::text = current_setting('app.current_tenant', true))",
                $table,
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE project_milestone');
        $this->addSql('DROP TABLE project_lot');
    }
}
