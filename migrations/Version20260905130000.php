<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-075 (T-075-02) — factures émises manuellement (ADR-0022).
 *
 * Table `invoice` (tenant, projet, période, montant centimes, client optionnel, émetteur, date,
 * statut). Figée à l'émission (INV-2). RLS dès la création (double barrière, DBT-SEC-1).
 */
final class Version20260905130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-075 : factures (invoice) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE invoice (id UUID NOT NULL, tenant_id UUID NOT NULL, project_ref VARCHAR(100) NOT NULL, period VARCHAR(7) NOT NULL, amount_cents INT NOT NULL, client_id UUID DEFAULT NULL, issued_by UUID DEFAULT NULL, issued_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(20) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_invoice_tenant_project_period ON invoice (tenant_id, project_ref, period)');

        $this->addSql('ALTER TABLE invoice ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE invoice FORCE ROW LEVEL SECURITY');
        $this->addSql(
            'CREATE POLICY tenant_isolation ON invoice '
            ."USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE invoice');
    }
}
