<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 3 (US-050) — référentiel Projet minimal et imputations de temps.
 * Grain d'imputation : (tenant, collaborateur, projet, jour). Durée en minutes (INV-2).
 */
final class Version20260831175946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Saisie de temps (US-050) : tables project et time_entry';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project (id UUID NOT NULL, tenant_id UUID NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(255) NOT NULL, active BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_project_tenant ON project (tenant_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_project_tenant_code ON project (tenant_id, code)');
        $this->addSql('CREATE TABLE time_entry (id UUID NOT NULL, tenant_id UUID NOT NULL, user_id UUID NOT NULL, project_id UUID NOT NULL, work_date DATE NOT NULL, minutes INT NOT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_time_entry_tenant_user_date ON time_entry (tenant_id, user_id, work_date)');
        $this->addSql('CREATE UNIQUE INDEX uniq_time_entry_grain ON time_entry (tenant_id, user_id, project_id, work_date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE time_entry');
    }
}
