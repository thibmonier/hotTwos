<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 5 (US-054, T-054-08 — optimisation post-revue) — index composite pour le blocage RG-TMP-3.
 *
 * `findValidatedCovering` (interrogée à **chaque** saisie de production) filtre par
 * (tenant, collaborateur, statut) : un index composite évite un scan à fort volume d'absences.
 */
final class Version20260901250000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-054 : index (tenant_id, user_id, status) sur absence_request';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_absence_tenant_user_status ON absence_request (tenant_id, user_id, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_absence_tenant_user_status');
    }
}
