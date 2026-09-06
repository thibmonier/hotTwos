<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-078 (T-078-01) — budget de charge par profil (EF-PRJ-9).
 *
 * Table `lot_profile_budget` : jours budgétés par profil sur un lot. L'équivalent € (vente/coût) est
 * dérivé des taux historisés (profile_rate) à la lecture. RLS dès la création.
 */
final class Version20260906110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-078 : budget de charge par profil (lot_profile_budget) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE lot_profile_budget (id UUID NOT NULL, tenant_id UUID NOT NULL, lot_id UUID NOT NULL, profile_id UUID NOT NULL, days INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_lot_profile_budget_tenant_lot ON lot_profile_budget (tenant_id, lot_id)');

        $this->addSql('ALTER TABLE lot_profile_budget ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE lot_profile_budget FORCE ROW LEVEL SECURITY');
        $this->addSql(
            'CREATE POLICY tenant_isolation ON lot_profile_budget '
            ."USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE lot_profile_budget');
    }
}
