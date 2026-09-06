<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-016 (T-016-01) — devises & devise de référence (EF-REF-22).
 *
 * Tables `reference_currency` (une devise de référence par tenant, défaut EUR applicatif) et
 * `exchange_rate` (taux datés devise→référence, en millièmes). RLS dès la création.
 */
final class Version20260906140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-016 : reference_currency + exchange_rate + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reference_currency (id UUID NOT NULL, tenant_id UUID NOT NULL, code VARCHAR(3) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_reference_currency_tenant ON reference_currency (tenant_id)');
        $this->addSql('ALTER TABLE reference_currency ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE reference_currency FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON reference_currency USING (tenant_id::text = current_setting('app.current_tenant', true))");

        $this->addSql('CREATE TABLE exchange_rate (id UUID NOT NULL, tenant_id UUID NOT NULL, code VARCHAR(3) NOT NULL, effective_from DATE NOT NULL, effective_to DATE DEFAULT NULL, rate_to_reference_millis INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_exchange_rate_tenant_code ON exchange_rate (tenant_id, code)');
        $this->addSql('ALTER TABLE exchange_rate ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE exchange_rate FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation ON exchange_rate USING (tenant_id::text = current_setting('app.current_tenant', true))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE exchange_rate');
        $this->addSql('DROP TABLE reference_currency');
    }
}
