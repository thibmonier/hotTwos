<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-035 (T-035-02) — avancement physique & RAF par lot.
 *
 * Ajoute `project_lot.physical_progress_percent` (0-100 %) et `project_lot.remaining_work_days` (RAF en
 * jours), tous deux nullable. Données distinctes de la consommation valorisée (INV-4). Aucune RLS à
 * ajouter : la table `project_lot` porte déjà sa policy d'isolation tenant.
 */
final class Version20260906090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-035 : avancement physique (%) + RAF (jours) sur project_lot';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_lot ADD physical_progress_percent INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_lot ADD remaining_work_days INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_lot DROP physical_progress_percent');
        $this->addSql('ALTER TABLE project_lot DROP remaining_work_days');
    }
}
