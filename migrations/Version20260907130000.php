<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-012 : holiday (jours fériés tenant) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE holiday (id UUID NOT NULL, tenant_id UUID NOT NULL, date DATE NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_holiday_tenant_date ON holiday (tenant_id, date)');
        $this->addSql('COMMENT ON COLUMN holiday.date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE holiday ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE holiday FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON holiday USING (tenant_id::text = current_setting('app.current_tenant', true))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE holiday');
    }
}
