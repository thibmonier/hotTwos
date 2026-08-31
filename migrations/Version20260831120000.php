<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-001 — socle multi-tenant : tenant, sonde d'isolation, pgvector, et
 * Row-Level Security (ARC-34, seconde barrière d'isolation après le filtre ORM ARC-33).
 */
final class Version20260831120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-001 : socle multi-tenant (tenant, protected_record, pgvector, RLS)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS vector');

        $this->addSql('CREATE TABLE tenant (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');

        $this->addSql('CREATE TABLE protected_record (id UUID NOT NULL, tenant_id UUID NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_protected_record_tenant ON protected_record (tenant_id)');

        // Seconde barrière d'isolation (ARC-34) : la RLS s'applique au rôle applicatif
        // (non-superuser). Le tenant courant est posé par requête via
        //   SET LOCAL app.current_tenant = '<uuid>'
        // Tant que la variable n'est pas positionnée, la policy ne laisse rien passer.
        $this->addSql('ALTER TABLE protected_record ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE protected_record FORCE ROW LEVEL SECURITY');
        $this->addSql(
            "CREATE POLICY tenant_isolation ON protected_record "
            . "USING (tenant_id = current_setting('app.current_tenant', true)::uuid)"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE protected_record');
        $this->addSql('DROP TABLE tenant');
    }
}
