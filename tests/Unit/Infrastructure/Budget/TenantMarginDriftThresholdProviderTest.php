<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Budget;

use App\Domain\Budget\MarginDriftThreshold;
use App\Domain\Tenant\TenantId;
use App\Infrastructure\Budget\DefaultMarginDriftThresholdProvider;
use App\Infrastructure\Budget\TenantMarginDriftThresholdProvider;
use App\Tests\Support\Budget\InMemoryMarginDriftThresholdRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-018 (T-018-02/04) — seuil de dérive paramétrable par tenant : override si configuré, repli sur
 * le défaut (OBJ-6) sinon ; bornes de l'entité.
 */
final class TenantMarginDriftThresholdProviderTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryMarginDriftThresholdRepository $repository;
    private TenantMarginDriftThresholdProvider $provider;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->repository = new InMemoryMarginDriftThresholdRepository();
        $this->provider = new TenantMarginDriftThresholdProvider($this->repository);
    }

    public function testFallsBackToDefaultWhenNotConfigured(): void
    {
        self::assertSame(DefaultMarginDriftThresholdProvider::DEFAULT_POINTS, $this->provider->pointsFor($this->tenant));
    }

    public function testUsesConfiguredThresholdWhenPresent(): void
    {
        $this->repository->save(new MarginDriftThreshold($this->tenant, 8));

        self::assertSame(8.0, $this->provider->pointsFor($this->tenant));
    }

    public function testAnotherTenantStillGetsDefault(): void
    {
        $this->repository->save(new MarginDriftThreshold($this->tenant, 8));

        self::assertSame(DefaultMarginDriftThresholdProvider::DEFAULT_POINTS, $this->provider->pointsFor(TenantId::generate()));
    }

    public function testThresholdRejectsOutOfBoundsValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MarginDriftThreshold($this->tenant, 101);
    }

    public function testReconfigureUpdatesPoints(): void
    {
        $threshold = new MarginDriftThreshold($this->tenant, 5);
        $threshold->reconfigure(10);

        self::assertSame(10, $threshold->points());
    }
}
