<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-071 (T-071-02) — marges réelles figées par projet à la clôture (INV-2).
 *
 * Table `project_margin` : CA reconnu et coût valorisé en centimes, marge dérivée, statut de
 * complétude (partiel si valorisation incomplète, CA-4) — figés à la clôture, jamais recalculés
 * après une révision tarifaire. Unicité (tenant, période, projet). RLS dès la création (double
 * barrière, DBT-SEC-1), comparaison texte robuste.
 */
final class Version20260904221500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-071 : marges figées par projet (project_margin) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project_margin (id UUID NOT NULL, tenant_id UUID NOT NULL, period VARCHAR(7) NOT NULL, project_ref VARCHAR(100) NOT NULL, project_name VARCHAR(255) NOT NULL, revenue_cents INT NOT NULL, cost_cents INT NOT NULL, valued_count INT NOT NULL, unvalued_count INT NOT NULL, partial BOOLEAN NOT NULL, frozen_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_margin_tenant_period_project ON project_margin (tenant_id, period, project_ref)');
        $this->addSql('CREATE INDEX idx_margin_tenant_period ON project_margin (tenant_id, period)');

        $this->addSql('ALTER TABLE project_margin ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE project_margin FORCE ROW LEVEL SECURITY');
        $this->addSql(
            'CREATE POLICY tenant_isolation ON project_margin '
            ."USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE project_margin');
    }
}
