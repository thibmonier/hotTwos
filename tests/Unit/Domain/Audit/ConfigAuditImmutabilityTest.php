<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Audit;

use App\Domain\Audit\ConfigAuditEntry;
use App\Domain\Audit\ConfigAuditRecorder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * US-020 (INV-7) — le journal d'audit est append-only : le port n'expose que l'enregistrement et la
 * lecture (aucune modification/suppression), et l'entrée n'a aucun mutateur.
 */
final class ConfigAuditImmutabilityTest extends TestCase
{
    public function testRecorderPortExposesOnlyRecordAndRead(): void
    {
        $methods = array_map(
            static fn (ReflectionMethod $m): string => $m->getName(),
            new ReflectionClass(ConfigAuditRecorder::class)->getMethods(),
        );
        sort($methods);

        self::assertSame(['findForTenant', 'record'], $methods, 'INV-7 : le journal ne doit exposer ni update ni delete.');
    }

    public function testEntryHasNoMutator(): void
    {
        $mutators = array_filter(
            new ReflectionClass(ConfigAuditEntry::class)->getMethods(),
            static fn (ReflectionMethod $m): bool => $m->isPublic()
                && !$m->isConstructor()
                && (str_starts_with($m->getName(), 'set') || str_starts_with($m->getName(), 'update') || str_starts_with($m->getName(), 'delete')),
        );

        self::assertSame([], array_values($mutators), 'INV-7 : une entrée d\'audit est immuable (aucun setter).');
    }
}
