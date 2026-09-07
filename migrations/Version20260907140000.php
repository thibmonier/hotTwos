<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-013 : skill / skill_level_scale / skill_assignment + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE skill (id UUID NOT NULL, tenant_id UUID NOT NULL, category VARCHAR(20) NOT NULL, label VARCHAR(255) NOT NULL, normalized_label VARCHAR(255) NOT NULL, active BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_skill_tenant_category_label ON skill (tenant_id, category, normalized_label)');
        $this->addSql('ALTER TABLE skill ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE skill FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON skill USING (tenant_id::text = current_setting('app.current_tenant', true))");

        $this->addSql('CREATE TABLE skill_level_scale (id UUID NOT NULL, tenant_id UUID NOT NULL, levels JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_skill_level_scale_tenant ON skill_level_scale (tenant_id)');
        $this->addSql('ALTER TABLE skill_level_scale ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE skill_level_scale FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON skill_level_scale USING (tenant_id::text = current_setting('app.current_tenant', true))");

        $this->addSql('CREATE TABLE skill_assignment (id UUID NOT NULL, tenant_id UUID NOT NULL, user_id UUID NOT NULL, skill_id UUID NOT NULL, level SMALLINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_skill_assignment ON skill_assignment (tenant_id, user_id, skill_id)');
        $this->addSql('ALTER TABLE skill_assignment ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE skill_assignment FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON skill_assignment USING (tenant_id::text = current_setting('app.current_tenant', true))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE skill_assignment');
        $this->addSql('DROP TABLE skill_level_scale');
        $this->addSql('DROP TABLE skill');
    }
}
