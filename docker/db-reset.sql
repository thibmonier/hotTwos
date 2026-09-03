-- Purge toutes les tables applicatives (hors migrations Doctrine) avant un re-seed propre.
-- Utilisé par `make db-reset` (action rétro S7 : éviter l'accumulation de tenants de démo).
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT tablename FROM pg_tables
        WHERE schemaname = 'public' AND tablename <> 'doctrine_migration_versions'
    LOOP
        EXECUTE 'TRUNCATE TABLE ' || quote_ident(r.tablename) || ' CASCADE';
    END LOOP;
END $$;
