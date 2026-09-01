<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sprint 3 (TECH-3) — active la connexion applicative sous RLS.
 *
 * Donne le droit LOGIN au rôle `hotones_app` (créé au Sprint 2). Le **mot de passe** est
 * posé hors dépôt (console d'administration de la base — ARC-88), et l'application bascule
 * son `DATABASE_URL` sur ce rôle : dès lors, non-superutilisateur, elle est réellement
 * soumise aux politiques RLS (ARC-34). Les migrations continuent via un rôle privilégié
 * (voir `MIGRATION_DATABASE_URL` dans docker/start.sh).
 *
 * Idempotent et tolérant au privilège (n'échoue pas sur un hôte sans droit d'altérer les rôles).
 */
final class Version20260831183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Active LOGIN sur le rôle applicatif hotones_app (bascule RLS runtime en production)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DO $$
            BEGIN
                IF EXISTS (SELECT FROM pg_roles WHERE rolname = 'hotones_app') THEN
                    ALTER ROLE hotones_app WITH LOGIN;
                END IF;
            EXCEPTION WHEN insufficient_privilege THEN
                RAISE NOTICE 'LOGIN non activé sur hotones_app (privilège insuffisant) — bascule RLS à faire manuellement.';
            END
            $$;
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DO $$
            BEGIN
                IF EXISTS (SELECT FROM pg_roles WHERE rolname = 'hotones_app') THEN
                    ALTER ROLE hotones_app WITH NOLOGIN;
                END IF;
            EXCEPTION WHEN insufficient_privilege THEN
                RAISE NOTICE 'NOLOGIN non appliqué sur hotones_app (privilège insuffisant).';
            END
            $$;
            SQL);
    }
}
