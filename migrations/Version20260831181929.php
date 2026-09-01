<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 3 (US-055) — validation des temps : responsable de projet et cycle de validation
 * des imputations (soumise/validée/refusée + motif + validateur + horodatage).
 */
final class Version20260831181929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Validation des temps (US-055) : project.responsible_user_id et statut de validation sur time_entry';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD responsible_user_id UUID DEFAULT NULL');
        // Ajout avec valeur par défaut pour backfiller d'éventuelles lignes, puis retrait
        // du défaut (le mapping ORM n'en porte pas — cohérence schema:validate).
        $this->addSql("ALTER TABLE time_entry ADD status VARCHAR(20) DEFAULT 'pending' NOT NULL");
        $this->addSql('ALTER TABLE time_entry ALTER COLUMN status DROP DEFAULT');
        $this->addSql('ALTER TABLE time_entry ADD rejection_reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE time_entry ADD validated_by UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE time_entry ADD decided_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP responsible_user_id');
        $this->addSql('ALTER TABLE time_entry DROP status');
        $this->addSql('ALTER TABLE time_entry DROP rejection_reason');
        $this->addSql('ALTER TABLE time_entry DROP validated_by');
        $this->addSql('ALTER TABLE time_entry DROP decided_at');
    }
}
