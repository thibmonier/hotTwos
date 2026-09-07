<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-079b : charge_drift_threshold (seuil de dérive de charge par type + escalade) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE charge_drift_threshold (id UUID NOT NULL, tenant_id UUID NOT NULL, contract_type VARCHAR(20) NOT NULL, alert_percent DOUBLE PRECISION NOT NULL, escalation_percent DOUBLE PRECISION NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_charge_drift_threshold ON charge_drift_threshold (tenant_id, contract_type)');
        $this->addSql('ALTER TABLE charge_drift_threshold ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE charge_drift_threshold FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON charge_drift_threshold USING (tenant_id::text = current_setting('app.current_tenant', true))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE charge_drift_threshold');
    }
}
