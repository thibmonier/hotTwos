<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-079c (suivi) : charge_landing_snapshot.is_escalated (franchissement 2e seuil sur la courbe)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE charge_landing_snapshot ADD is_escalated BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE charge_landing_snapshot ALTER COLUMN is_escalated DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE charge_landing_snapshot DROP is_escalated');
    }
}
