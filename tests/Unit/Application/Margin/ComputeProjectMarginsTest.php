<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Margin;

use App\Application\Margin\ComputeProjectMargins;
use App\Domain\Margin\ProjectMargin;
use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\ProjectValuationLine;
use App\Tests\Support\Margin\InMemoryProjectMarginRepository;
use App\Tests\Support\Valuation\InMemoryTimeEntryValuationRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-071 (T-071-04, CA-1/CA-2/CA-4) — figeage des marges par projet à la clôture : marge = CA − coût,
 * partiel si valorisation incomplète, non-rétroactivité (une autre période n'est jamais recalculée).
 */
final class ComputeProjectMarginsTest extends TestCase
{
    private const string PROJECT_A = '018f9c4e-0000-7000-8000-00000000aaaa';
    private const string PROJECT_B = '018f9c4e-0000-7000-8000-00000000bbbb';

    private TenantId $tenant;
    private InMemoryTimeEntryValuationRepository $valuations;
    private InMemoryProjectMarginRepository $margins;
    private ComputeProjectMargins $compute;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->valuations = new InMemoryTimeEntryValuationRepository();
        $this->margins = new InMemoryProjectMarginRepository();
        $this->compute = new ComputeProjectMargins(
            $this->valuations,
            $this->margins,
            new MockClock(new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC'))),
        );
    }

    public function testFreezesMarginPerProjectAtClosure(): void
    {
        $this->valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine(self::PROJECT_A, 'Site vitrine', 12, 10_000_00, 5_800_00),
            new ProjectValuationLine(self::PROJECT_B, 'Appli mobile', 8, 6_000_00, 6_500_00),
        ];

        $this->compute->forClosedPeriod($this->tenant, '2026-11');

        $frozen = $this->margins->findForPeriod($this->tenant, '2026-11');
        self::assertCount(2, $frozen);

        $a = $this->marginFor($frozen, self::PROJECT_A);
        self::assertSame(10_000_00, $a->revenueCents());
        self::assertSame(5_800_00, $a->costCents());
        self::assertSame(4_200_00, $a->marginCents());
        self::assertFalse($a->isPartial());
        self::assertEquals(new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC')), $a->frozenAt());

        // Marge négative conservée telle quelle (projet déficitaire).
        self::assertSame(-500_00, $this->marginFor($frozen, self::PROJECT_B)->marginCents());
    }

    public function testMarksMarginPartialWhenValuationIncomplete(): void
    {
        $this->valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine(self::PROJECT_A, 'Site vitrine', 12, 10_000_00, 5_800_00),
        ];
        $this->valuations->missingRateCountByProject = [self::PROJECT_A => 3];

        $this->compute->forClosedPeriod($this->tenant, '2026-11');

        $a = $this->marginFor($this->margins->findForPeriod($this->tenant, '2026-11'), self::PROJECT_A);
        self::assertTrue($a->isPartial());
        self::assertSame(3, $a->unvaluedCount());
        // La marge reste calculée sur les seules imputations valorisées (CA-4).
        self::assertSame(4_200_00, $a->marginCents());
    }

    public function testDoesNotRecomputePastPeriods(): void
    {
        // Marge d'octobre déjà figée.
        $this->valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine(self::PROJECT_A, 'Site vitrine', 10, 9_000_00, 4_000_00),
        ];
        $this->compute->forClosedPeriod($this->tenant, '2026-10');
        $october = $this->marginFor($this->margins->findForPeriod($this->tenant, '2026-10'), self::PROJECT_A);
        self::assertSame(5_000_00, $october->marginCents());

        // Clôture de novembre avec d'autres chiffres.
        $this->valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine(self::PROJECT_A, 'Site vitrine', 12, 10_000_00, 5_800_00),
        ];
        $this->compute->forClosedPeriod($this->tenant, '2026-11');

        // La marge d'octobre est inchangée (INV-2) — aucun recalcul rétroactif.
        self::assertSame(
            5_000_00,
            $this->marginFor($this->margins->findForPeriod($this->tenant, '2026-10'), self::PROJECT_A)->marginCents(),
        );
    }

    public function testReFreezingSamePeriodReplacesWithoutDuplicates(): void
    {
        $this->valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine(self::PROJECT_A, 'Site vitrine', 12, 10_000_00, 5_800_00),
        ];
        $this->compute->forClosedPeriod($this->tenant, '2026-11');

        // Réouverture puis re-clôture avec une valorisation corrigée.
        $this->valuations->projectBreakdownForPeriod = [
            new ProjectValuationLine(self::PROJECT_A, 'Site vitrine', 12, 10_000_00, 4_000_00),
        ];
        $this->compute->forClosedPeriod($this->tenant, '2026-11');

        $frozen = $this->margins->findForPeriod($this->tenant, '2026-11');
        self::assertCount(1, $frozen);
        self::assertSame(6_000_00, $frozen[0]->marginCents());
    }

    public function testEmptyBreakdownFreezesNothing(): void
    {
        $this->compute->forClosedPeriod($this->tenant, '2026-11');

        self::assertSame([], $this->margins->findForPeriod($this->tenant, '2026-11'));
    }

    /**
     * @param list<ProjectMargin> $margins
     */
    private function marginFor(array $margins, string $projectRef): ProjectMargin
    {
        foreach ($margins as $margin) {
            if ($margin->projectRef() === $projectRef) {
                return $margin;
            }
        }

        self::fail(sprintf('Aucune marge figée pour le projet %s.', $projectRef));
    }
}
