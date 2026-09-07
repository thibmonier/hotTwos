<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-022 : closure_period (fermetures entreprise) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE closure_period (id UUID NOT NULL, tenant_id UUID NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_closure_period_tenant ON closure_period (tenant_id, start_date)');
        $this->addSql('ALTER TABLE closure_period ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE closure_period FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON closure_period USING (tenant_id::text = current_setting('app.current_tenant', true))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE closure_period');
    }
}
