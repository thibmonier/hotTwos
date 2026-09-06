<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project;

use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectLot;
use App\Domain\Tenant\TenantId;
use PHPUnit\Framework\TestCase;

/**
 * US-035 (CA-1/CA-2/CA-4/CA-5) — avancement physique & RAF sur un lot : invariants de bornes et
 * indépendance des deux données (INV-4). L'avancement/RAF ne modifient pas le budget du lot.
 */
final class ProjectLotTest extends TestCase
{
    private const string PROJECT = '018f9c4e-0000-7000-8000-00000000aaaa';

    public function testRecordProgressStoresPercentAndRaf(): void
    {
        $lot = $this->lot();

        $lot->recordProgress(40, 6);

        self::assertSame(40, $lot->physicalProgressPercent());
        self::assertSame(6, $lot->remainingWorkDays());
        // INV-4 : le budget (charge/montant) n'est pas altéré par l'avancement.
        self::assertSame(120, $lot->budgetDays());
        self::assertSame(9_600_000, $lot->budgetCents());
    }

    public function testProgressAndRafAreIndependent(): void
    {
        $lot = $this->lot();

        $lot->recordProgress(20, null);
        self::assertSame(20, $lot->physicalProgressPercent());
        self::assertNull($lot->remainingWorkDays());

        $lot->recordProgress(null, 3);
        self::assertNull($lot->physicalProgressPercent());
        self::assertSame(3, $lot->remainingWorkDays());
    }

    public function testProgressAbove100IsRejected(): void
    {
        $this->expectException(ProjectException::class);
        $this->lot()->recordProgress(120, null);
    }

    public function testNegativeProgressIsRejected(): void
    {
        $this->expectException(ProjectException::class);
        $this->lot()->recordProgress(-10, null);
    }

    public function testNegativeRafIsRejected(): void
    {
        $this->expectException(ProjectException::class);
        $this->lot()->recordProgress(null, -3);
    }

    private function lot(): ProjectLot
    {
        return new ProjectLot(TenantId::generate(), self::PROJECT, 'Développement', 120, 9_600_000);
    }
}
