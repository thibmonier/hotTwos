<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Margin;

use App\Application\Margin\ComputeProjectMargins;
use App\Application\Margin\FreezeProjectMarginsOnPeriodClosed;
use App\Domain\Budget\ChargeLandingSnapshotRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * US-079c (décision rétro S14) — garde-fou : l'historisation de l'atterrissage passe par un handler
 * **séparé**. Ce test prouve que le figeage de marge n'a pas été recouplé à la capture d'atterrissage :
 * ni {@see ComputeProjectMargins} ni {@see FreezeProjectMarginsOnPeriodClosed} ne connaissent la
 * couche d'historisation.
 */
final class ComputeProjectMarginsIsolationTest extends TestCase
{
    public function testComputeProjectMarginsKeepsItsFourDependencies(): void
    {
        $constructor = new ReflectionClass(ComputeProjectMargins::class)->getConstructor();
        self::assertNotNull($constructor);

        // TimeEntryValuationRepository, ProjectMarginRepository, RevenueSource, ClockInterface — pas plus.
        self::assertSame(4, $constructor->getNumberOfParameters());
    }

    public function testMarginFreezingDoesNotDependOnChargeLandingSnapshots(): void
    {
        foreach ([ComputeProjectMargins::class, FreezeProjectMarginsOnPeriodClosed::class] as $class) {
            $constructor = new ReflectionClass($class)->getConstructor();
            self::assertNotNull($constructor);

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();
                self::assertNotSame(
                    ChargeLandingSnapshotRepository::class,
                    $type instanceof ReflectionNamedType ? $type->getName() : null,
                    sprintf('%s ne doit pas dépendre de l\'historisation d\'atterrissage (handler séparé).', $class),
                );
            }
        }
    }
}
