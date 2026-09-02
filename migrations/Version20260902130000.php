<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 6 (US-034, T-034-01) — engagements externes rattachés à un projet (sous-traitance, achats,
 * licences). RLS dès la création (double barrière, DBT-SEC-1), comparaison texte.
 */
final class Version20260902130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-034 : engagements externes de projet + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project_external_commitment (id UUID NOT NULL, tenant_id UUID NOT NULL, project_id UUID NOT NULL, type VARCHAR(20) NOT NULL, label VARCHAR(200) NOT NULL, amount_cents INT NOT NULL, supplier VARCHAR(200) NOT NULL, status VARCHAR(20) NOT NULL, lot_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_commitment_tenant_project ON project_external_commitment (tenant_id, project_id)');

        $this->addSql('ALTER TABLE project_external_commitment ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE project_external_commitment FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON project_external_commitment USING (tenant_id::text = current_setting('app.current_tenant', true))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE project_external_commitment');
    }
}
