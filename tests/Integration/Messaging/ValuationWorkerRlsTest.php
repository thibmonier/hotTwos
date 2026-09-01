<?php

declare(strict_types=1);

namespace App\Tests\Integration\Messaging;

use App\Application\Messaging\TenantAwareMessage;
use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\TimeEntryValuation;
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
 * US-060 (T-060-08, sécurité — finding [Élevé]) — la RLS de `time_entry_valuation` est réellement
 * appliquée dans le **chemin worker**, sous rôle non-superutilisateur : une écriture n'est acceptée
 * que si le contexte de tenant a été posé sur la session (par {@see TenantContextMiddleware} à la
 * consommation). Sans ce contexte, la barrière `WITH CHECK` de la policy **rejette** l'écriture —
 * ce qui, sans le correctif, aurait cassé la valorisation en production (ou permis une écriture
 * cross-tenant sur une connexion résiduelle).
 */
final class ValuationWorkerRlsTest extends KernelTestCase
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

        $this->schema = [$this->em->getClassMetadata(TimeEntryValuation::class)];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        $this->connection->executeStatement('RESET app.current_tenant');
        $this->connection->executeStatement('ALTER TABLE time_entry_valuation ENABLE ROW LEVEL SECURITY');
        $this->connection->executeStatement('ALTER TABLE time_entry_valuation FORCE ROW LEVEL SECURITY');
        $this->connection->executeStatement('DROP POLICY IF EXISTS tenant_isolation ON time_entry_valuation');
        $this->connection->executeStatement(
            "CREATE POLICY tenant_isolation ON time_entry_valuation USING (tenant_id::text = current_setting('app.current_tenant', true))",
        );

        $this->connection->executeStatement('DROP ROLE IF EXISTS valuation_rls_probe');
        $this->connection->executeStatement('CREATE ROLE valuation_rls_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT, INSERT ON time_entry_valuation TO valuation_rls_probe');
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS valuation_rls_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testConsumingAMessageLetsTheWorkerWriteUnderRls(): void
    {
        $tenant = TenantId::generate();
        $middleware = new TenantContextMiddleware(new RequestTenantContext(), $this->connection);
        $inserter = new ValuationInserter($this->connection);

        $this->connection->executeStatement('SET ROLE valuation_rls_probe');
        // La consommation pose `app.current_tenant` autour du handler : l'INSERT passe la barrière RLS.
        $middleware->handle(
            new Envelope(new RlsFakeMessage($tenant), [new ReceivedStamp('async')]),
            new TerminalStack($inserter),
        );
        $this->connection->executeStatement('RESET ROLE');

        // Superutilisateur (bypass RLS) : la ligne du bon tenant a bien été écrite.
        self::assertSame(1, $this->countFor($tenant));
    }

    public function testWorkerWriteWithoutTenantContextIsRejectedByRls(): void
    {
        $tenant = TenantId::generate();
        $inserter = new ValuationInserter($this->connection);

        $this->connection->executeStatement('SET ROLE valuation_rls_probe');
        // Sans contexte de tenant (pas de middleware), la barrière WITH CHECK rejette l'écriture.
        $rejected = false;
        try {
            $inserter->insertFor($tenant);
        } catch (\Doctrine\DBAL\Exception) {
            $rejected = true;
        }
        $this->connection->executeStatement('RESET ROLE');

        self::assertTrue($rejected, 'Sans app.current_tenant, la RLS rejette l\'écriture du worker.');
        self::assertSame(0, $this->countFor($tenant));
    }

    private function countFor(TenantId $tenant): int
    {
        $value = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM time_entry_valuation WHERE tenant_id = ?',
            [$tenant->toString()],
        );

        return is_numeric($value) ? (int) $value : 0;
    }
}

/** Middleware terminal : écrit une valorisation pour le tenant du message. */
final readonly class ValuationInserter implements MiddlewareInterface
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
        $this->connection->insert('time_entry_valuation', [
            'id' => TenantId::generate()->toString(),
            'tenant_id' => $tenant->toString(),
            'time_entry_id' => TenantId::generate()->toString(),
            'status' => 'valued',
            'cost_cents' => 45000,
            'revenue_cents' => 78000,
            'valued_at' => new DateTimeImmutable('2026-08-20 10:00:00', new DateTimeZone('UTC')),
        ], ['valued_at' => 'datetime_immutable']);
    }
}

final readonly class RlsFakeMessage implements TenantAwareMessage
{
    public function __construct(private TenantId $tenantId)
    {
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }
}

final readonly class TerminalStack implements StackInterface
{
    public function __construct(private MiddlewareInterface $next)
    {
    }

    public function next(): MiddlewareInterface
    {
        return $this->next;
    }
}
