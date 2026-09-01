<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 5 (US-056, T-056-01) — relances automatiques de retard de saisie.
 *
 * `reminder_rule` : configuration par tenant (une règle : délai initial, fréquence, canal, escalade,
 * activation globale). `reminder_preference` : opt-out individuel du collaborateur (CA-2, RGPD).
 * `reminder_log` : journal des relances émises (historique + mémoire du moteur : rang d'escalade et
 * plancher anti-spam). RLS dès la création (double barrière, DBT-SEC-1), comparaison texte.
 */
final class Version20260901260000 extends AbstractMigration
{
    private const array TABLES = ['reminder_rule', 'reminder_preference', 'reminder_log'];

    public function getDescription(): string
    {
        return 'US-056 : règles, préférences et journal de relances + RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reminder_rule (id UUID NOT NULL, tenant_id UUID NOT NULL, initial_delay_days SMALLINT NOT NULL, frequency_days SMALLINT NOT NULL, channel VARCHAR(20) NOT NULL, escalation_enabled BOOLEAN NOT NULL, active BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_reminder_rule_tenant ON reminder_rule (tenant_id)');

        $this->addSql('CREATE TABLE reminder_preference (id UUID NOT NULL, tenant_id UUID NOT NULL, user_id UUID NOT NULL, opted_out BOOLEAN NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_reminder_pref_tenant_user ON reminder_preference (tenant_id, user_id)');

        $this->addSql('CREATE TABLE reminder_log (id UUID NOT NULL, tenant_id UUID NOT NULL, user_id UUID NOT NULL, week_start DATE NOT NULL, channel VARCHAR(20) NOT NULL, sequence_no SMALLINT NOT NULL, escalated BOOLEAN NOT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, reason VARCHAR(100) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_reminder_log_tenant_user_week ON reminder_log (tenant_id, user_id, week_start)');
        $this->addSql('CREATE INDEX idx_reminder_log_tenant_sent ON reminder_log (tenant_id, sent_at)');

        foreach (self::TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf(
                "CREATE POLICY tenant_isolation ON %s USING (tenant_id::text = current_setting('app.current_tenant', true))",
                $table,
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reminder_log');
        $this->addSql('DROP TABLE reminder_preference');
        $this->addSql('DROP TABLE reminder_rule');
    }
}
