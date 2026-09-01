<?php

declare(strict_types=1);

namespace App\Tests\Integration\Messaging;

use App\Application\Messaging\TenantAwareMessage;
use App\Domain\Reminder\ReminderLog;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Messaging\TenantContextMiddleware;
use App\Infrastructure\Tenant\RequestTenantContext;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-056 (T-056-03, sécurité — action rétro S4) — l'écriture du journal de relances par le worker
 * n'est acceptée que si le contexte de tenant a été posé à la consommation ({@see TenantContextMiddleware}).
 * Sous rôle non-superutilisateur, la barrière `WITH CHECK` de la RLS rejette toute écriture hors
 * contexte — parité avec le chemin HTTP, comme pour la valorisation US-060.
 */
final class ReminderWorkerRlsTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Connection $connection;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();

        $this->schema = [$this->em->getClassMetadata(ReminderLog::class)];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->connection->executeStatement('RESET app.current_tenant');
        $this->connection->executeStatement('ALTER TABLE reminder_log ENABLE ROW LEVEL SECURITY');
        $this->connection->executeStatement('ALTER TABLE reminder_log FORCE ROW LEVEL SECURITY');
        $this->connection->executeStatement('DROP POLICY IF EXISTS tenant_isolation ON reminder_log');
        $this->connection->executeStatement(
            "CREATE POLICY tenant_isolation ON reminder_log USING (tenant_id::text = current_setting('app.current_tenant', true))",
        );

        $this->connection->executeStatement('DROP ROLE IF EXISTS reminder_worker_probe');
        $this->connection->executeStatement('CREATE ROLE reminder_worker_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT, INSERT ON reminder_log TO reminder_worker_probe');
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS reminder_worker_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testConsumingLetsTheWorkerJournalizeUnderRls(): void
    {
        $tenant = TenantId::generate();
        $middleware = new TenantContextMiddleware(new RequestTenantContext(), $this->connection);
        $inserter = new ReminderLogInserter($this->connection);

        $this->connection->executeStatement('SET ROLE reminder_worker_probe');
        $middleware->handle(
            new Envelope(new ReminderFakeMessage($tenant), [new ReceivedStamp('async')]),
            new ReminderTerminalStack($inserter),
        );
        $this->connection->executeStatement('RESET ROLE');

        self::assertSame(1, $this->countFor($tenant));
    }

    public function testWorkerJournalWithoutTenantContextIsRejectedByRls(): void
    {
        $tenant = TenantId::generate();
        $inserter = new ReminderLogInserter($this->connection);

        $this->connection->executeStatement('SET ROLE reminder_worker_probe');
        $rejected = false;
        try {
            $inserter->insertFor($tenant);
        } catch (\Doctrine\DBAL\Exception) {
            $rejected = true;
        }
        $this->connection->executeStatement('RESET ROLE');

        self::assertTrue($rejected, 'Sans app.current_tenant, la RLS rejette l\'écriture du journal.');
        self::assertSame(0, $this->countFor($tenant));
    }

    private function countFor(TenantId $tenant): int
    {
        $value = $this->connection->fetchOne('SELECT COUNT(*) FROM reminder_log WHERE tenant_id = ?', [$tenant->toString()]);

        return is_numeric($value) ? (int) $value : 0;
    }
}

/** Middleware terminal : journalise une relance pour le tenant du message. */
final readonly class ReminderLogInserter implements MiddlewareInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        if ($message instanceof TenantAwareMessage) {
            $this->insertFor($message->tenantId());
        }

        return $envelope;
    }

    public function insertFor(TenantId $tenant): void
    {
        $this->connection->insert('reminder_log', [
            'id' => TenantId::generate()->toString(),
            'tenant_id' => $tenant->toString(),
            'user_id' => '018f9c4e-0000-7000-8000-0000000000aa',
            'week_start' => new DateTimeImmutable('2026-08-24 00:00:00', new DateTimeZone('UTC')),
            'channel' => 'in_app',
            'sequence_no' => 1,
            'escalated' => false,
            'sent_at' => new DateTimeImmutable('2026-09-02 08:00:00', new DateTimeZone('UTC')),
            'reason' => null,
        ], [
            'week_start' => 'date_immutable',
            'sent_at' => 'datetime_immutable',
            'escalated' => 'boolean',
        ]);
    }
}

final readonly class ReminderFakeMessage implements TenantAwareMessage
{
    public function __construct(private TenantId $tenantId)
    {
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }
}

final readonly class ReminderTerminalStack implements StackInterface
{
    public function __construct(private MiddlewareInterface $next)
    {
    }

    public function next(): MiddlewareInterface
    {
        return $this->next;
    }
}
