<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-011 (T-011-01) — profils de tarification (EF-REF-4/20).
 *
 * Table `profile` portée par tenant (nom, mode de calcul du coût). RLS dès la création
 * (double barrière ADR-0006 / ARC-34, DBT-SEC-1) : comparaison en texte robuste au contexte
 * absent. Le tenant courant est posé par requête via SET LOCAL app.current_tenant.
 */
final class Version20260901150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-011 : profils de tarification (profile) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE profile (id UUID NOT NULL, tenant_id UUID NOT NULL, name VARCHAR(255) NOT NULL, calculation_mode VARCHAR(20) NOT NULL, active BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_profile_tenant ON profile (tenant_id)');

        $this->addSql('ALTER TABLE profile ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE profile FORCE ROW LEVEL SECURITY');
        $this->addSql(
            "CREATE POLICY tenant_isolation ON profile "
            . "USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE profile');
    }
}
