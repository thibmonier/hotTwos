<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 6 (US-037, T-037-01) — affectations de projet et ouvertures exceptionnelles d'imputation.
 * RLS dès la création (double barrière, DBT-SEC-1), comparaison texte.
 */
final class Version20260902120000 extends AbstractMigration
{
    private const array TABLES = ['project_assignment', 'exceptional_imputation_opening'];

    public function getDescription(): string
    {
        return 'US-037 : affectations et ouvertures exceptionnelles + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project_assignment (id UUID NOT NULL, tenant_id UUID NOT NULL, project_id UUID NOT NULL, user_id UUID NOT NULL, role VARCHAR(100) NOT NULL, planned_days INT NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_assignment_tenant_project ON project_assignment (tenant_id, project_id)');
        $this->addSql('CREATE INDEX idx_assignment_tenant_user ON project_assignment (tenant_id, user_id)');

        $this->addSql('CREATE TABLE exceptional_imputation_opening (id UUID NOT NULL, tenant_id UUID NOT NULL, project_id UUID NOT NULL, user_id UUID NOT NULL, week_start DATE NOT NULL, reason VARCHAR(500) NOT NULL, granted_by UUID NOT NULL, granted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_opening_tenant_project_user ON exceptional_imputation_opening (tenant_id, project_id, user_id)');

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
        $this->addSql('DROP TABLE exceptional_imputation_opening');
        $this->addSql('DROP TABLE project_assignment');
    }
}
