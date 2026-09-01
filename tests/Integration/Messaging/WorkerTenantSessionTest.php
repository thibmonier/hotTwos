<?php

declare(strict_types=1);

namespace App\Tests\Integration\Messaging;

use App\Application\Messaging\TenantAwareMessage;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Messaging\TenantContextMiddleware;
use App\Infrastructure\Tenant\RequestTenantContext;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * US-060 (T-060-08, sécurité) — parité RLS HTTP/worker : à la consommation d'un message
 * tenant-aware, la variable de session PostgreSQL `app.current_tenant` (pivot de la RLS) est
 * bien positionnée **pendant** le handler puis relâchée après. Sans cela, les écritures du worker
 * (valorisation US-060) s'exécuteraient sans contexte RLS.
 */
final class WorkerTenantSessionTest extends KernelTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(Connection::class);
        $this->connection->executeStatement('RESET app.current_tenant');
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET app.current_tenant');
        parent::tearDown();
    }

    public function testTenantIsBoundToTheDatabaseSessionDuringConsumption(): void
    {
        $tenant = TenantId::generate();
        $middleware = new TenantContextMiddleware(new RequestTenantContext(), $this->connection);
        $probe = new CurrentTenantProbe($this->connection);

        $middleware->handle(
            new Envelope(new WorkerFakeMessage($tenant), [new ReceivedStamp('async')]),
            new SingleMiddlewareStack($probe),
        );

        // Le tenant du message était actif côté connexion pendant l'exécution du handler…
        self::assertSame($tenant->toString(), $probe->observed);
        // …et la session est relâchée ensuite (aucune fuite vers le message suivant — RSQ-15).
        self::assertEmpty($this->connection->fetchOne("SELECT current_setting('app.current_tenant', true)"));
    }

    public function testDispatchWithoutReceivedStampDoesNotBindSession(): void
    {
        $tenant = TenantId::generate();
        $middleware = new TenantContextMiddleware(new RequestTenantContext(), $this->connection);
        $probe = new CurrentTenantProbe($this->connection);

        // Sans ReceivedStamp (dispatch hors worker) : la session n'est pas touchée.
        $middleware->handle(new Envelope(new WorkerFakeMessage($tenant)), new SingleMiddlewareStack($probe));

        self::assertEmpty($probe->observed);
    }
}

final readonly class WorkerFakeMessage implements TenantAwareMessage
{
    public function __construct(private TenantId $tenantId)
    {
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }
}

/** Middleware terminal : lit le tenant actif sur la session au moment du handler. */
final class CurrentTenantProbe implements MiddlewareInterface
{
    public ?string $observed = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $value = $this->connection->fetchOne("SELECT current_setting('app.current_tenant', true)");
        $this->observed = is_string($value) ? $value : null;

        return $envelope;
    }
}

final readonly class SingleMiddlewareStack implements StackInterface
{
    public function __construct(private MiddlewareInterface $next)
    {
    }

    public function next(): MiddlewareInterface
    {
        return $this->next;
    }
}
