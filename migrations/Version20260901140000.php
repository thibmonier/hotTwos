<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-010 (T-010-03) — rattachements historisés des collaborateurs (EF-REF-2).
 *
 * Table org_membership portée par tenant : période de validité à date d'effet
 * (effective_from / effective_to nullable = « en cours »). RLS dès la création (double barrière
 * ADR-0006 / ARC-34, DBT-SEC-1). Tenant courant posé par requête via SET LOCAL app.current_tenant.
 */
final class Version20260901140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-010 : rattachements historisés (org_membership) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE org_membership (id UUID NOT NULL, tenant_id UUID NOT NULL, user_id UUID NOT NULL, org_unit_id UUID NOT NULL, effective_from DATE NOT NULL, effective_to DATE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_org_membership_tenant_user ON org_membership (tenant_id, user_id)');
        $this->addSql('CREATE INDEX idx_org_membership_tenant_unit ON org_membership (tenant_id, org_unit_id)');

        $this->addSql('ALTER TABLE org_membership ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE org_membership FORCE ROW LEVEL SECURITY');
        // Comparaison en texte (et non ::uuid) : robuste quand le contexte tenant est absent/vide.
        $this->addSql(
            "CREATE POLICY tenant_isolation ON org_membership "
            . "USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE org_membership');
    }
}
