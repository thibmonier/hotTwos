<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project;

use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * US-050 — un projet minimal porte une identité (code, nom) et un état actif.
 */
final class ProjectTest extends TestCase
{
    public function testExposesIdentityAndActiveState(): void
    {
        $project = new Project(TenantId::generate(), 'PRJ-1', 'Refonte SI');

        self::assertSame('PRJ-1', $project->code());
        self::assertSame('Refonte SI', $project->name());
        self::assertTrue($project->isActive());
    }

    public function testCanBeInactive(): void
    {
        $project = new Project(TenantId::generate(), 'PRJ-2', 'Archivé', false);

        self::assertFalse($project->isActive());
    }

    public function testRejectsEmptyCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Project(TenantId::generate(), '', 'Sans code');
    }
}
