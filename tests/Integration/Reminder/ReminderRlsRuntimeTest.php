<?php

declare(strict_types=1);

namespace App\Tests\Integration\Reminder;

use App\Domain\Reminder\ReminderChannel;
use App\Domain\Reminder\ReminderLog;
use App\Domain\Reminder\ReminderPreference;
use App\Domain\Reminder\ReminderRule;
use App\Domain\Tenant\TenantId;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-056 (T-056-01 / DBT-SEC-1) — la RLS isole les tables de relances au runtime, sous le rôle
 * applicatif non-superutilisateur : sans contexte de tenant, aucune ligne n'est visible.
 */
final class ReminderRlsRuntimeTest extends KernelTestCase
{
    private const array RLS_TABLES = ['reminder_rule', 'reminder_preference', 'reminder_log'];
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';

    private EntityManagerInterface $em;
    private Connection $connection;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();

        $this->schema = [
            $this->em->getClassMetadata(ReminderRule::class),
            $this->em->getClassMetadata(ReminderPreference::class),
            $this->em->getClassMetadata(ReminderLog::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        foreach (self::RLS_TABLES as $table) {
            $this->connection->executeStatement(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
            $this->connection->executeStatement(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
            $this->connection->executeStatement(sprintf('DROP POLICY IF EXISTS tenant_isolation ON %s', $table));
            $this->connection->executeStatement(sprintf(
                "CREATE POLICY tenant_isolation ON %s USING (tenant_id::text = current_setting('app.current_tenant', true))",
                $table,
            ));
        }
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS reminder_rls_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testRlsIsolatesReminderTablesAtRuntime(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        foreach ([$tenantA, $tenantB] as $tenant) {
            $this->em->persist(ReminderRule::default($tenant));
            $this->em->persist(new ReminderPreference($tenant, self::USER, true, $this->at('2026-08-01 09:00:00')));
            $this->em->persist(new ReminderLog($tenant, self::USER, $this->day('2026-08-24'), ReminderChannel::IN_APP, 1, false, $this->at('2026-08-31 08:00:00')));
        }
        $this->em->flush();
        $this->em->clear();

        $this->connection->executeStatement('DROP ROLE IF EXISTS reminder_rls_probe');
        $this->connection->executeStatement('CREATE ROLE reminder_rls_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT ON reminder_rule, reminder_preference, reminder_log TO reminder_rls_probe');

        $this->connection->executeStatement(sprintf("SET app.current_tenant = '%s'", $tenantA->toString()));
        $this->connection->executeStatement('SET ROLE reminder_rls_probe');
        $rulesForA = $this->countRows('reminder_rule');
        $prefsForA = $this->countRows('reminder_preference');
        $logsForA = $this->countRows('reminder_log');

        $this->connection->executeStatement('RESET app.current_tenant');
        $rulesWithoutContext = $this->countRows('reminder_rule');
        $prefsWithoutContext = $this->countRows('reminder_preference');
        $logsWithoutContext = $this->countRows('reminder_log');

        $this->connection->executeStatement('RESET ROLE');

        self::assertSame(1, $rulesForA, 'Seule la règle du tenant A est visible.');
        self::assertSame(1, $prefsForA, 'Seule la préférence du tenant A est visible.');
        self::assertSame(1, $logsForA, 'Seule la relance du tenant A est visible.');
        self::assertSame(0, $rulesWithoutContext, 'Sans contexte, la RLS masque les règles.');
        self::assertSame(0, $prefsWithoutContext, 'Sans contexte, la RLS masque les préférences.');
        self::assertSame(0, $logsWithoutContext, 'Sans contexte, la RLS masque le journal.');
    }

    private function countRows(string $table): int
    {
        $value = $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));

        return is_numeric($value) ? (int) $value : 0;
    }

    private function day(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }

    private function at(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
