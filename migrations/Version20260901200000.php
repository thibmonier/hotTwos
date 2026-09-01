<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 5 (US-057, T-057-01) — périodes comptables et leur statut de clôture.
 *
 * Table `accounting_period` : un mois `YYYY-MM` par tenant, statut open/closing/closed, auteur et
 * horodatage de clôture (CA-1). RLS dès la création (double barrière, DBT-SEC-1), comparaison texte.
 */
final class Version20260901200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-057 : périodes comptables (accounting_period) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE accounting_period (id UUID NOT NULL, tenant_id UUID NOT NULL, period VARCHAR(7) NOT NULL, status VARCHAR(20) NOT NULL, closed_by UUID DEFAULT NULL, closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_period_tenant_month ON accounting_period (tenant_id, period)');

        $this->addSql('ALTER TABLE accounting_period ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE accounting_period FORCE ROW LEVEL SECURITY');
        $this->addSql(
            "CREATE POLICY tenant_isolation ON accounting_period "
            . "USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE accounting_period');
    }
}
