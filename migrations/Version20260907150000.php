<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-020 : config_audit_entry (journal d\'audit append-only) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE config_audit_entry (id UUID NOT NULL, tenant_id UUID NOT NULL, actor_user_id UUID NOT NULL, action VARCHAR(20) NOT NULL, object_type VARCHAR(100) NOT NULL, object_label VARCHAR(255) NOT NULL, field VARCHAR(100) DEFAULT NULL, value_before VARCHAR(255) DEFAULT NULL, value_after VARCHAR(255) DEFAULT NULL, recorded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_config_audit_tenant_recorded ON config_audit_entry (tenant_id, recorded_at)');
        $this->addSql('ALTER TABLE config_audit_entry ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE config_audit_entry FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON config_audit_entry USING (tenant_id::text = current_setting('app.current_tenant', true))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE config_audit_entry');
    }
}
