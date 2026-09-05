<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-074 (T-074-03) — configuration comptable FEC par tenant.
 *
 * Table `fec_configuration` : SIREN + journal + mapping des 4 comptes (produit, tiers, charge,
 * contrepartie) pour dériver les écritures FEC. Une config par tenant. RLS dès la création (double
 * barrière, DBT-SEC-1), comparaison texte robuste.
 */
final class Version20260905100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-074 : configuration comptable FEC (fec_configuration) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE fec_configuration (id UUID NOT NULL, tenant_id UUID NOT NULL, siren VARCHAR(9) NOT NULL, journal_code VARCHAR(10) NOT NULL, journal_lib VARCHAR(100) NOT NULL, revenue_account_num VARCHAR(20) NOT NULL, revenue_account_lib VARCHAR(100) NOT NULL, receivable_account_num VARCHAR(20) NOT NULL, receivable_account_lib VARCHAR(100) NOT NULL, cost_account_num VARCHAR(20) NOT NULL, cost_account_lib VARCHAR(100) NOT NULL, cost_counterpart_account_num VARCHAR(20) NOT NULL, cost_counterpart_account_lib VARCHAR(100) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_fec_config_tenant ON fec_configuration (tenant_id)');

        $this->addSql('ALTER TABLE fec_configuration ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE fec_configuration FORCE ROW LEVEL SECURITY');
        $this->addSql(
            'CREATE POLICY tenant_isolation ON fec_configuration '
            ."USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE fec_configuration');
    }
}
