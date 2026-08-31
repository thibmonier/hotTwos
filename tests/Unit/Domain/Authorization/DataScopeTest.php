<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Authorization;

use App\Domain\Authorization\DataScope;
use PHPUnit\Framework\TestCase;

/**
 * US-003 — le périmètre de données est totalement ordonné ; cet ordre fonde
 * l'anti-élévation de privilège (CA-6).
 */
final class DataScopeTest extends TestCase
{
    public function testWiderScopeCoversNarrowerOne(): void
    {
        self::assertTrue(DataScope::TENANT->covers(DataScope::OWN));
        self::assertTrue(DataScope::POOL->covers(DataScope::OWN_PROJECTS));
    }

    public function testEachScopeCoversItself(): void
    {
        foreach (DataScope::cases() as $scope) {
            self::assertTrue($scope->covers($scope));
        }
    }

    public function testNarrowerScopeDoesNotCoverWiderOne(): void
    {
        self::assertFalse(DataScope::OWN->covers(DataScope::TENANT));
        self::assertFalse(DataScope::OWN_PROJECTS->covers(DataScope::POOL));
    }

    public function testRankIsStrictlyIncreasing(): void
    {
        self::assertLessThan(DataScope::OWN_PROJECTS->rank(), DataScope::OWN->rank());
        self::assertLessThan(DataScope::POOL->rank(), DataScope::OWN_PROJECTS->rank());
        self::assertLessThan(DataScope::TENANT->rank(), DataScope::POOL->rank());
    }
}
