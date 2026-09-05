<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-018 (T-018-01) — seuil de dérive de marge paramétrable par tenant.
 *
 * Table `margin_drift_threshold` : un seuil (points) par tenant, override du défaut US-072. RLS dès la
 * création (double barrière, DBT-SEC-1), comparaison texte robuste.
 */
final class Version20260905110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-018 : seuil de dérive de marge par tenant (margin_drift_threshold) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE margin_drift_threshold (id UUID NOT NULL, tenant_id UUID NOT NULL, points SMALLINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_margin_drift_threshold_tenant ON margin_drift_threshold (tenant_id)');

        $this->addSql('ALTER TABLE margin_drift_threshold ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE margin_drift_threshold FORCE ROW LEVEL SECURITY');
        $this->addSql(
            'CREATE POLICY tenant_isolation ON margin_drift_threshold '
            ."USING (tenant_id::text = current_setting('app.current_tenant', true))"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE margin_drift_threshold');
    }
}
