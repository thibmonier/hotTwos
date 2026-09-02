<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 6 (US-038, T-038-01) — clôture opérationnelle de projet : horodatage/auteur de clôture sur
 * `project` et réouvertures exceptionnelles 4-eyes (`project_reopening`, avec RLS).
 */
final class Version20260902140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-038 : clôture de projet + réouvertures (RLS)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD COLUMN closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD COLUMN closed_by UUID DEFAULT NULL');

        $this->addSql('CREATE TABLE project_reopening (id UUID NOT NULL, tenant_id UUID NOT NULL, project_id UUID NOT NULL, requested_by UUID NOT NULL, reason VARCHAR(500) NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, approved_by UUID DEFAULT NULL, approved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, open_until DATE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_reopening_tenant_project ON project_reopening (tenant_id, project_id)');

        $this->addSql('ALTER TABLE project_reopening ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE project_reopening FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON project_reopening USING (tenant_id::text = current_setting('app.current_tenant', true))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE project_reopening');
        $this->addSql('ALTER TABLE project DROP COLUMN closed_at');
        $this->addSql('ALTER TABLE project DROP COLUMN closed_by');
    }
}
