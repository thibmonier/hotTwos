<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 5 (US-057, T-057-05) — demandes de réouverture formelle de période.
 *
 * Table `period_reopening_request` : demandeur, motif, statut, approbateur, fenêtre de validité
 * (CA-2). RLS dès la création (double barrière, DBT-SEC-1), comparaison texte.
 */
final class Version20260901220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-057 : demandes de réouverture (period_reopening_request) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE period_reopening_request (id UUID NOT NULL, tenant_id UUID NOT NULL, period VARCHAR(7) NOT NULL, requested_by UUID NOT NULL, reason TEXT NOT NULL, status VARCHAR(20) NOT NULL, approved_by UUID DEFAULT NULL, valid_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_reopening_tenant_period ON period_reopening_request (tenant_id, period)');

        $this->addSql('ALTER TABLE period_reopening_request ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE period_reopening_request FORCE ROW LEVEL SECURITY');
        $this->addSql(
            "CREATE POLICY tenant_isolation ON period_reopening_request "
            . "USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE period_reopening_request');
    }
}
