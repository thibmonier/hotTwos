<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 5 (US-057, T-057-09 — durcissement post-revue) — le trigger de verrou couvre le « move-in ».
 *
 * La version initiale ne testait que `OLD.work_date` : un UPDATE **déplaçant** une imputation d'un
 * mois ouvert vers un mois clôturé passait la barrière DB. Le trigger teste désormais `OLD` **et**
 * `NEW.work_date` (bloque si l'un des deux mois est clôturé), fermant le contournement d'INV-7.
 */
final class Version20260901230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'US-057 : trigger de verrou — couvrir le déplacement vers une période clôturée';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION reject_locked_time_entry_modification() RETURNS trigger AS $$
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM accounting_period ap
                    WHERE ap.tenant_id = OLD.tenant_id
                      AND ap.status = 'closed'
                      AND ap.period IN (
                          to_char(OLD.work_date, 'YYYY-MM'),
                          CASE WHEN TG_OP = 'UPDATE' THEN to_char(NEW.work_date, 'YYYY-MM') END
                      )
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
    }

    public function down(Schema $schema): void
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
    }
}
