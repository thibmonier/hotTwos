<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-072 (T-072-01) — CA cible prévisionnel par projet (EF-FIN).
 *
 * Ajoute `revenue_budget_cents` (nullable, centimes) à `project` : enveloppe de revenu cible, en
 * complément du budget de charge existant (`budget_cents`). Permet le rapprochement budget vs réalisé
 * sur le CA et la marge (marge cible = CA cible − coût cible), et la détection de dérive du taux de
 * marge. Nullable : les projets internes/non budgétés en revenu restent valides (CA-4).
 */
final class Version20260904223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-072 : CA cible prévisionnel par projet (project.revenue_budget_cents)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD revenue_budget_cents INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP revenue_budget_cents');
    }
}
