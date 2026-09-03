<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * US-067 (T-067-01) — nom et prénom du profil utilisateur sur `app_user` (nullable, rétrocompatible :
 * les comptes existants restent valides et retombent sur l'e-mail comme libellé).
 */
final class Version20260903193803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-067 : ajoute first_name / last_name (nullable) sur app_user.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD first_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD last_name VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP first_name');
        $this->addSql('ALTER TABLE app_user DROP last_name');
    }
}
