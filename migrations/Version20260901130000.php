<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-010 (T-010-02) — structure organisationnelle paramétrable et historisée.
 *
 * Deux tables portées par tenant :
 *  - org_level_config : niveaux hiérarchiques nommés (1..N, sans développement) ;
 *  - org_unit         : nœuds de la hiérarchie (parent auto-référencé, désactivables).
 *
 * Row-Level Security dès la création (double barrière avec le filtre ORM — ADR-0006 / ARC-34,
 * amorce l'action rétro DBT-SEC-1). Le tenant courant est posé par requête via
 *   SET LOCAL app.current_tenant = '<uuid>'
 * Sans ce contexte, la policy ne laisse rien passer. Relecture manuelle de la RLS (ARC-106).
 */
final class Version20260901130000 extends AbstractMigration
{
    private const array TENANT_TABLES = ['org_level_config', 'org_unit'];

    public function getDescription(): string
    {
        return 'US-010 : structure organisationnelle (org_level_config, org_unit) + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE org_level_config (id UUID NOT NULL, tenant_id UUID NOT NULL, position INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_org_level_tenant_position ON org_level_config (tenant_id, position)');

        $this->addSql('CREATE TABLE org_unit (id UUID NOT NULL, tenant_id UUID NOT NULL, parent_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, active BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_org_unit_tenant ON org_unit (tenant_id)');
        $this->addSql('CREATE INDEX idx_org_unit_tenant_parent ON org_unit (tenant_id, parent_id)');

        foreach (self::TENANT_TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
            // Comparaison en texte (et non ::uuid) : robuste quand le contexte tenant est absent
            // ou vide — sans contexte, la policy ne laisse rien passer sans lever d'erreur de cast.
            $this->addSql(sprintf(
                "CREATE POLICY tenant_isolation ON %s "
                . "USING (tenant_id::text = current_setting('app.current_tenant', true))",
                $table,
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE org_unit');
        $this->addSql('DROP TABLE org_level_config');
    }
}
