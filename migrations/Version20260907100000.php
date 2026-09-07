<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-079c : charge_landing_snapshot (historisation atterrissage) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE charge_landing_snapshot (id UUID NOT NULL, tenant_id UUID NOT NULL, project_id UUID NOT NULL, period VARCHAR(7) NOT NULL, project_name VARCHAR(255) NOT NULL, landing_cost_cents INT DEFAULT NULL, cost_budget_cents INT DEFAULT NULL, overrun_percent DOUBLE PRECISION DEFAULT NULL, consumption_percent DOUBLE PRECISION DEFAULT NULL, physical_progress_percent SMALLINT DEFAULT NULL, is_early_drift BOOLEAN NOT NULL, captured_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_charge_landing_snapshot ON charge_landing_snapshot (tenant_id, project_id, period)');
        $this->addSql('ALTER TABLE charge_landing_snapshot ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE charge_landing_snapshot FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON charge_landing_snapshot USING (tenant_id::text = current_setting('app.current_tenant', true))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE charge_landing_snapshot');
    }
}
