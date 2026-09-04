<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Analytics;

use App\Application\Analytics\Message\AnalyticsRebuildRequested;
use App\Application\Analytics\ProjectAnalyticsHandler;
use App\Application\Analytics\RebuildAnalytics;
use App\Domain\Analytics\AnalyticsProjector;
use App\Domain\Tenant\TenantId;
use PHPUnit\Framework\TestCase;

/**
 * US-060 (T-060-06) — à réception d'{@see AnalyticsRebuildRequested}, la fact table du tenant est
 * rematérialisée par le projecteur (rejeu), et pour ce tenant uniquement.
 */
final class ProjectAnalyticsHandlerTest extends TestCase
{
    public function testRematerialisesTenantAnalytics(): void
    {
        $tenant = TenantId::generate();
        $projector = new class () implements AnalyticsProjector {
            /** @var list<string> */
            public array $rebuilt = [];

            public function rebuild(TenantId $tenant): void
            {
                $this->rebuilt[] = $tenant->toString();
            }
        };

        $handler = new ProjectAnalyticsHandler(new RebuildAnalytics($projector));
        $handler(new AnalyticsRebuildRequested($tenant->toString()));

        self::assertSame([$tenant->toString()], $projector->rebuilt);
    }
}
