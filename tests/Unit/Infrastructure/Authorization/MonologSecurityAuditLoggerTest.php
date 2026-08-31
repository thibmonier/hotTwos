<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Authorization;

use App\Infrastructure\Authorization\MonologSecurityAuditLogger;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * US-003 / HAB-6 — l'audit de sécurité écrit sur le canal dédié un enregistrement
 * complet : qui, quoi, quand, dans quel tenant.
 */
final class MonologSecurityAuditLoggerTest extends TestCase
{
    public function testRecordsCompleteContextAtInfoLevel(): void
    {
        $handler = new TestHandler();
        $audit = new MonologSecurityAuditLogger(new Logger('security', [$handler]));

        $audit->record('sensitive_data_read', 'tenant-1', 'sophie@agence.test', ['resource' => 'collaborator:camille']);

        self::assertTrue($handler->hasRecords(Level::Info));
        $record = $handler->getRecords()[0];
        self::assertSame('sensitive_data_read', $record->message);
        self::assertSame('tenant-1', $record->context['tenant_id']);
        self::assertSame('sophie@agence.test', $record->context['actor']);
        self::assertSame('collaborator:camille', $record->context['resource']);
        self::assertArrayHasKey('at', $record->context);
    }

    public function testActorMayBeAnonymous(): void
    {
        $handler = new TestHandler();
        $audit = new MonologSecurityAuditLogger(new Logger('security', [$handler]));

        $audit->record('unauthorized_action_attempt', 'tenant-1', null, ['permission' => 'delete:project']);

        $record = $handler->getRecords()[0];
        self::assertNull($record->context['actor']);
        self::assertSame('delete:project', $record->context['permission']);
    }
}
