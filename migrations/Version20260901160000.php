<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-011 (T-011-02) — entrées tarifaires historisées (EF-REF-5, INV-2).
 *
 * Table `profile_rate` : coût de revient et taux de vente en centimes entiers, sur une période
 * à date d'effet. RLS dès la création (double barrière, DBT-SEC-1), comparaison texte robuste.
 */
final class Version20260901160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-011 : entrées tarifaires historisées (profile_rate) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE profile_rate (id UUID NOT NULL, tenant_id UUID NOT NULL, profile_id UUID NOT NULL, effective_from DATE NOT NULL, effective_to DATE DEFAULT NULL, cost_price_cents INT NOT NULL, selling_price_cents INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_profile_rate_tenant_profile ON profile_rate (tenant_id, profile_id)');

        $this->addSql('ALTER TABLE profile_rate ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE profile_rate FORCE ROW LEVEL SECURITY');
        $this->addSql(
            "CREATE POLICY tenant_isolation ON profile_rate "
            . "USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE profile_rate');
    }
}
