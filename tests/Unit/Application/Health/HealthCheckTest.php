<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Health;

use App\Application\Health\HealthCheck;
use PHPUnit\Framework\TestCase;

/**
 * Le cas d'usage est invocable et testable sans HTTP ni conteneur (ARC-17).
 */
final class HealthCheckTest extends TestCase
{
    public function testStatusReportsOkWithApplicationName(): void
    {
        $healthCheck = new HealthCheck('HotOnes');

        self::assertSame(
            ['status' => 'ok', 'app' => 'HotOnes'],
            $healthCheck->status(),
        );
    }
}
