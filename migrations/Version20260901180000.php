<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-060 (T-060-02) — rattachements collaborateur → profil, historisés (pivot de la valorisation).
 *
 * Table `profile_assignment` portée par tenant : période de validité à date d'effet. RLS dès la
 * création (double barrière, DBT-SEC-1), comparaison texte robuste.
 */
final class Version20260901180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-060 : rattachements collaborateur → profil (profile_assignment) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE profile_assignment (id UUID NOT NULL, tenant_id UUID NOT NULL, effective_from DATE NOT NULL, effective_to DATE DEFAULT NULL, user_id UUID NOT NULL, profile_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_profile_assignment_tenant_user ON profile_assignment (tenant_id, user_id)');

        $this->addSql('ALTER TABLE profile_assignment ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE profile_assignment FORCE ROW LEVEL SECURITY');
        $this->addSql(
            "CREATE POLICY tenant_isolation ON profile_assignment "
            . "USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE profile_assignment');
    }
}
