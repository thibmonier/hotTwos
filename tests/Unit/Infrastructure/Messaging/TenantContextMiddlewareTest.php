<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Messaging;

use App\Application\Messaging\TenantAwareMessage;
use App\Application\Tenant\TenantSwitcher;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Messaging\TenantContextMiddleware;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * T-TECH-01 (Sprint 4) — isolation du tenant par message asynchrone (ARC-47, RSQ-15).
 */
final class TenantContextMiddlewareTest extends TestCase
{
    private const string TENANT = '018f9c4e-0000-7000-8000-000000000001';

    public function testSetsAndClearsTenantWhenConsumingTenantAwareMessage(): void
    {
        $switcher = new RecordingTenantSwitcher();
        $statements = [];
        $middleware = new TenantContextMiddleware($switcher, $this->recordingConnection($statements));
        $envelope = new Envelope(new FakeTenantMessage(TenantId::fromString(self::TENANT)), [new ReceivedStamp('async')]);

        $middleware->handle($envelope, new FakeStack(new PassThroughMiddleware()));

        self::assertSame([self::TENANT], $switcher->switched);
        self::assertSame(1, $switcher->cleared);
        // Barrière RLS armée côté connexion (paramètre lié) puis relâchée (parité HTTP).
        self::assertStringContainsString('set_config', $statements[0] ?? '');
        self::assertSame('RESET app.current_tenant', $statements[1] ?? null);
    }

    public function testClearsTenantEvenWhenHandlerThrows(): void
    {
        $switcher = new RecordingTenantSwitcher();
        $statements = [];
        $middleware = new TenantContextMiddleware($switcher, $this->recordingConnection($statements));
        $envelope = new Envelope(new FakeTenantMessage(TenantId::fromString(self::TENANT)), [new ReceivedStamp('async')]);

        try {
            $middleware->handle($envelope, new FakeStack(new ThrowingMiddleware()));
            self::fail('Expected the handler exception to propagate.');
        } catch (RuntimeException $exception) {
            self::assertSame('handler failed', $exception->getMessage());
        }

        // RSQ-15 : le tenant (mémoire + session DB) est effacé quoi qu'il arrive (bloc finally).
        self::assertSame([self::TENANT], $switcher->switched);
        self::assertSame(1, $switcher->cleared);
        self::assertSame('RESET app.current_tenant', $statements[1] ?? null);
    }

    public function testDoesNotTouchTenantWhenDispatching(): void
    {
        // Sans ReceivedStamp : dispatch initial (requête HTTP déjà scopée) — ne pas modifier le contexte.
        $switcher = new RecordingTenantSwitcher();
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('executeStatement');
        $middleware = new TenantContextMiddleware($switcher, $connection);
        $envelope = new Envelope(new FakeTenantMessage(TenantId::fromString(self::TENANT)));

        $middleware->handle($envelope, new FakeStack(new PassThroughMiddleware()));

        self::assertSame([], $switcher->switched);
        self::assertSame(0, $switcher->cleared);
    }

    public function testIgnoresNonTenantAwareMessageEvenWhenConsuming(): void
    {
        $switcher = new RecordingTenantSwitcher();
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('executeStatement');
        $middleware = new TenantContextMiddleware($switcher, $connection);
        $envelope = new Envelope(new PlainMessage(), [new ReceivedStamp('async')]);

        $middleware->handle($envelope, new FakeStack(new PassThroughMiddleware()));

        self::assertSame([], $switcher->switched);
        self::assertSame(0, $switcher->cleared);
    }

    /**
     * @param list<string> $statements capture, dans l'ordre, les SQL exécutés
     */
    private function recordingConnection(array &$statements): Connection
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('executeStatement')->willReturnCallback(
            static function (string $sql) use (&$statements): int {
                $statements[] = $sql;

                return 0;
            },
        );

        return $connection;
    }
}

final class RecordingTenantSwitcher implements TenantSwitcher
{
    /** @var list<string> */
    public array $switched = [];
    public int $cleared = 0;

    public function switchTo(TenantId $tenantId): void
    {
        $this->switched[] = $tenantId->toString();
    }

    public function clear(): void
    {
        ++$this->cleared;
    }
}

final readonly class FakeTenantMessage implements TenantAwareMessage
{
    public function __construct(private TenantId $tenantId)
    {
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }
}

final class PlainMessage
{
}

/** Stack de test : délègue toujours au middleware fourni. */
final readonly class FakeStack implements StackInterface
{
    public function __construct(private MiddlewareInterface $next)
    {
    }

    public function next(): MiddlewareInterface
    {
        return $this->next;
    }
}

/** Middleware terminal : renvoie l'enveloppe inchangée. */
final class PassThroughMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        return $envelope;
    }
}

/** Middleware simulant l'échec d'un handler. */
final class ThrowingMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        throw new RuntimeException('handler failed');
    }
}
