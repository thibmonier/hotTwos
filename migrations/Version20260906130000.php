<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-015 (T-015-02) — taux de vente multi-niveaux (EF-REF-19).
 *
 * Table `selling_rate` : surcharges de taux de vente d'un profil au niveau client ou projet, historisées
 * à date d'effet. Le niveau profil (défaut) reste dans `profile_rate`. RLS dès la création.
 */
final class Version20260906130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-015 : surcharges de taux de vente (selling_rate) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE selling_rate (id UUID NOT NULL, tenant_id UUID NOT NULL, profile_id UUID NOT NULL, scope VARCHAR(10) NOT NULL, scope_ref_id UUID NOT NULL, effective_from DATE NOT NULL, effective_to DATE DEFAULT NULL, selling_price_cents INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_selling_rate_lookup ON selling_rate (tenant_id, profile_id, scope, scope_ref_id)');

        $this->addSql('ALTER TABLE selling_rate ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE selling_rate FORCE ROW LEVEL SECURITY');
        $this->addSql(
            'CREATE POLICY tenant_isolation ON selling_rate '
            ."USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE selling_rate');
    }
}
