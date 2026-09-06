<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-032 (T-032-01) — projets internes non facturables (EF-PRJ-5).
 *
 * Ajoute `project.internal` (bool, défaut false) : un projet interne est exclu du calcul de marge et de
 * l'occupation facturable, mais reste dans la capacité consommée (RG-PRJ-6).
 */
final class Version20260906120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-032 : project.internal (projets internes non facturables)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD internal BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP internal');
    }
}
