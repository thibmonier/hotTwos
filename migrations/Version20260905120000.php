<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-014 (T-014-02) — comptes clients (tranche minimale) + rattachement projet.
 *
 * Table `client` (tenant, nom, SIREN optionnel), nom unique par tenant, RLS dès la création (double
 * barrière, DBT-SEC-1). Ajoute `project.client_id` (nullable) pour rattacher un projet à un client —
 * coexiste avec `client_name` (migration ultérieure des ventilations finance).
 */
final class Version20260905120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-014 : comptes clients (client) + RLS + project.client_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE client (id UUID NOT NULL, tenant_id UUID NOT NULL, name VARCHAR(255) NOT NULL, siren VARCHAR(9) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_client_tenant_name ON client (tenant_id, name)');
        $this->addSql('CREATE INDEX idx_client_tenant ON client (tenant_id)');

        $this->addSql('ALTER TABLE client ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE client FORCE ROW LEVEL SECURITY');
        $this->addSql(
            'CREATE POLICY tenant_isolation ON client '
            ."USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );

        $this->addSql('ALTER TABLE project ADD client_id UUID DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP client_id');
        $this->addSql('DROP TABLE client');
    }
}
