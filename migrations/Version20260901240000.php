<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 5 (US-054, T-054-01) — référentiel de types d'absence et demandes d'absence.
 *
 * `absence_type` : libellés normalisés par tenant. `absence_request` : type, dates, maille
 * demi-journée (`starts_morning`/`ends_afternoon`), statut, commentaire optionnel — **jamais** de
 * donnée de santé (HAB-3). RLS dès la création (double barrière, DBT-SEC-1), comparaison texte.
 */
final class Version20260901240000 extends AbstractMigration
{
    private const array TABLES = ['absence_type', 'absence_request'];

    public function getDescription(): string
    {
        return 'US-054 : types et demandes d\'absence + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE absence_type (id UUID NOT NULL, tenant_id UUID NOT NULL, label VARCHAR(100) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_absence_type_tenant_label ON absence_type (tenant_id, label)');

        $this->addSql('CREATE TABLE absence_request (id UUID NOT NULL, tenant_id UUID NOT NULL, user_id UUID NOT NULL, type_id UUID NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, starts_morning BOOLEAN NOT NULL, ends_afternoon BOOLEAN NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, comment TEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, decided_by UUID DEFAULT NULL, decided_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, rejection_reason TEXT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_absence_tenant_user ON absence_request (tenant_id, user_id)');

        foreach (self::TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf(
                "CREATE POLICY tenant_isolation ON %s USING (tenant_id::text = current_setting('app.current_tenant', true))",
                $table,
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE absence_request');
        $this->addSql('DROP TABLE absence_type');
    }
}
