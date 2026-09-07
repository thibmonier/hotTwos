<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-021 : work_schedule (régimes de travail différenciés) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE work_schedule (id UUID NOT NULL, tenant_id UUID NOT NULL, user_id UUID NOT NULL, working_weekdays JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_work_schedule_tenant_user ON work_schedule (tenant_id, user_id)');
        $this->addSql('ALTER TABLE work_schedule ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE work_schedule FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON work_schedule USING (tenant_id::text = current_setting('app.current_tenant', true))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE work_schedule');
    }
}
