<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-033 (T-033-02) — avenants budgétaires (EF-PRJ-8).
 *
 * Table `budget_amendment` : delta coût/montant daté, motif et auteur, par projet. Le budget courant se
 * dérive du budget initial (`project.budget_cents`/`revenue_budget_cents`) + Σ avenants. RLS dès la
 * création (isolation tenant).
 */
final class Version20260906100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-033 : avenants budgétaires (budget_amendment) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE budget_amendment (id UUID NOT NULL, tenant_id UUID NOT NULL, project_id UUID NOT NULL, delta_cost_cents INT NOT NULL, delta_revenue_cents INT NOT NULL, reason VARCHAR(255) NOT NULL, author_id UUID NOT NULL, recorded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_budget_amendment_tenant_project ON budget_amendment (tenant_id, project_id)');

        $this->addSql('ALTER TABLE budget_amendment ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE budget_amendment FORCE ROW LEVEL SECURITY');
        $this->addSql(
            'CREATE POLICY tenant_isolation ON budget_amendment '
            ."USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE budget_amendment');
    }
}
