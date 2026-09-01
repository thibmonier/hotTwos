<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 4 (T-TECH-02) — Row-Level Security sur les tables métier `project` et `time_entry`.
 *
 * Créées au Sprint 3 (US-050) **sans** policy, elles ne reposaient que sur le filtre ORM
 * (première barrière). Cette migration arme la seconde barrière (DBT-SEC-1) : sous le rôle
 * applicatif non-superutilisateur, aucune ligne d'un autre tenant n'est visible ni écrivable.
 * Comparaison **texte** (robuste au contexte absent : `current_setting(..., true)` renvoie NULL
 * sans lever d'erreur, là où un cast `::uuid` échouerait).
 */
final class Version20260901190000 extends AbstractMigration
{
    private const array TABLES = ['project', 'time_entry'];

    public function getDescription(): string
    {
        return 'T-TECH-02 : RLS sur project et time_entry';
    }

    public function up(Schema $schema): void
    {
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
        foreach (self::TABLES as $table) {
            $this->addSql(sprintf('DROP POLICY IF EXISTS tenant_isolation ON %s', $table));
            $this->addSql(sprintf('ALTER TABLE %s NO FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE %s DISABLE ROW LEVEL SECURITY', $table));
        }
    }
}
