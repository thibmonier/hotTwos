<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 2 (TECH-1) — complète le schéma multi-tenant : utilisateurs (US-002),
 * rôles RBAC (US-003), flux d'événements et schéma en étoile analytique (US-005).
 *
 * Le durcissement analytique (RLS + trigger anti-écriture) est appliqué par la
 * migration suivante ({@see Version20260831161807}).
 */
final class Version20260831161806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma US-002/US-003/US-005 : app_user, auth_role, event_stream, dim_period, fact_project_revenue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (id UUID NOT NULL, tenant_id UUID NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_tenant_email ON app_user (tenant_id, email)');
        $this->addSql('CREATE TABLE auth_role (id UUID NOT NULL, tenant_id UUID NOT NULL, name VARCHAR(100) NOT NULL, permissions JSON NOT NULL, data_scope VARCHAR(20) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_role_tenant_name ON auth_role (tenant_id, name)');
        $this->addSql('CREATE TABLE dim_period (id UUID NOT NULL, tenant_id UUID NOT NULL, period VARCHAR(7) NOT NULL, year INT NOT NULL, month INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_dim_period_tenant ON dim_period (tenant_id, period)');
        $this->addSql('CREATE TABLE event_stream (id UUID NOT NULL, tenant_id UUID NOT NULL, name VARCHAR(100) NOT NULL, payload JSON NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, sequence INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_event_stream_tenant_seq ON event_stream (tenant_id, sequence)');
        $this->addSql('CREATE TABLE fact_project_revenue (id UUID NOT NULL, tenant_id UUID NOT NULL, period VARCHAR(7) NOT NULL, project_ref VARCHAR(100) NOT NULL, amount_cents INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_fact_revenue_grain ON fact_project_revenue (tenant_id, period, project_ref)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE auth_role');
        $this->addSql('DROP TABLE dim_period');
        $this->addSql('DROP TABLE event_stream');
        $this->addSql('DROP TABLE fact_project_revenue');
    }
}
