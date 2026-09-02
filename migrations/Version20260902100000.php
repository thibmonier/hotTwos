<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 6 (US-030, T-030-01) — l'agrégat projet devient métier : cycle de vie + client, budget,
 * contractualisation, dates. La table `project` porte déjà la RLS (Sprint 4, T-TECH-02) : on n'ajoute
 * que des colonnes. `status` NOT NULL avec défaut « en_cours » pour rétro-compatibilité des lignes
 * existantes (projet système « Absence », jeux de démo) qui restent imputables.
 */
final class Version20260902100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-030 : cycle de vie + champs métier sur project (client, budget, contractualisation, dates)';
    }

    public function up(Schema $schema): void
    {
        // Défaut « en_cours » uniquement pour backfiller les lignes existantes (projet système
        // « Absence », jeux de démo), puis on le retire : l'application fixe toujours le statut à
        // l'insertion et le mapping Doctrine ne déclare pas de défaut (sinon schema:validate diverge).
        $this->addSql("ALTER TABLE project ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'en_cours'");
        $this->addSql('ALTER TABLE project ALTER COLUMN status DROP DEFAULT');
        $this->addSql('ALTER TABLE project ADD COLUMN client_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD COLUMN budget_cents INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD COLUMN contract_type VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD COLUMN start_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD COLUMN end_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP COLUMN status');
        $this->addSql('ALTER TABLE project DROP COLUMN client_name');
        $this->addSql('ALTER TABLE project DROP COLUMN budget_cents');
        $this->addSql('ALTER TABLE project DROP COLUMN contract_type');
        $this->addSql('ALTER TABLE project DROP COLUMN start_date');
        $this->addSql('ALTER TABLE project DROP COLUMN end_date');
    }
}
