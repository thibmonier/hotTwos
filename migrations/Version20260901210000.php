<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 5 (US-057, T-057-04) — verrou base de données des imputations d'une période clôturée.
 *
 * Défense en profondeur (INV-7, RG-TMP-6) : au-delà du garde applicatif (PeriodModificationGuard →
 * 423), un trigger PostgreSQL refuse tout UPDATE/DELETE sur une imputation dont le mois est clôturé
 * (`accounting_period.status = 'closed'` pour le même tenant). La lecture de `accounting_period`
 * reste soumise à la RLS (même tenant), cohérente avec le contexte de la requête.
 */
final class Version20260901210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-057 : trigger anti-modification des imputations en période clôturée';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION reject_locked_time_entry_modification() RETURNS trigger AS $$
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM accounting_period ap
                    WHERE ap.tenant_id = OLD.tenant_id
                      AND ap.period = to_char(OLD.work_date, 'YYYY-MM')
                      AND ap.status = 'closed'
                ) THEN
                    RAISE EXCEPTION 'Imputation % en période clôturée : modification interdite (RG-TMP-6/INV-7)', OLD.id
                        USING ERRCODE = 'check_violation';
                END IF;
                IF TG_OP = 'DELETE' THEN
                    RETURN OLD;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            SQL);
        $this->addSql('CREATE TRIGGER trg_time_entry_period_lock BEFORE UPDATE OR DELETE ON time_entry FOR EACH ROW EXECUTE FUNCTION reject_locked_time_entry_modification()');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER IF EXISTS trg_time_entry_period_lock ON time_entry');
        $this->addSql('DROP FUNCTION IF EXISTS reject_locked_time_entry_modification()');
    }
}
