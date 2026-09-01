<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-060 (T-060-03) — valorisations figées des imputations (INV-2/INV-3).
 *
 * Table `time_entry_valuation` : coût et revenu en centimes, avec le taux appliqué copié
 * (snapshot) — jamais recalculé après une révision tarifaire. Unicité (tenant, imputation).
 * RLS dès la création (double barrière, DBT-SEC-1), comparaison texte robuste.
 */
final class Version20260901170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-060 : valorisations figées (time_entry_valuation) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE time_entry_valuation (id UUID NOT NULL, tenant_id UUID NOT NULL, time_entry_id UUID NOT NULL, status VARCHAR(20) NOT NULL, cost_cents INT NOT NULL, revenue_cents INT NOT NULL, snapshot_cost_rate_cents INT DEFAULT NULL, snapshot_selling_rate_cents INT DEFAULT NULL, snapshot_rate_date DATE DEFAULT NULL, valued_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_valuation_tenant_entry ON time_entry_valuation (tenant_id, time_entry_id)');
        $this->addSql('CREATE INDEX idx_valuation_tenant_status ON time_entry_valuation (tenant_id, status)');

        $this->addSql('ALTER TABLE time_entry_valuation ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE time_entry_valuation FORCE ROW LEVEL SECURITY');
        $this->addSql(
            "CREATE POLICY tenant_isolation ON time_entry_valuation "
            . "USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE time_entry_valuation');
    }
}
