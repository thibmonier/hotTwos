<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-017 : absence_validation_circuit + RLS ; absence_request.current_step';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE absence_validation_circuit (id UUID NOT NULL, tenant_id UUID NOT NULL, steps JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_absence_validation_circuit_tenant ON absence_validation_circuit (tenant_id)');
        $this->addSql('ALTER TABLE absence_validation_circuit ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE absence_validation_circuit FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON absence_validation_circuit USING (tenant_id::text = current_setting('app.current_tenant', true))");

        // Migration additive : étape courante du circuit (défaut 1 pour les demandes existantes).
        $this->addSql('ALTER TABLE absence_request ADD current_step SMALLINT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE absence_request ALTER COLUMN current_step DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE absence_request DROP current_step');
        $this->addSql('DROP TABLE absence_validation_circuit');
    }
}
