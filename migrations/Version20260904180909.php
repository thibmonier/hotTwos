<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260904180909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-060 (T-060-09) : index idx_time_entry_project sur time_entry(project_id) — accélère les jointures valorisation↔projet (ventilation, occupation).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_time_entry_project ON time_entry (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_time_entry_project');
    }
}
