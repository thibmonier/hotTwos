<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project;

use App\Domain\Project\ContractType;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectException;
use App\Domain\Project\ProjectStatus;
use App\Domain\Tenant\TenantId;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-030 — l'agrégat projet applique RG-PRJ-1 (client, responsable, budget obligatoires), démarre
 * « En préparation », conditionne l'imputation au statut « En cours » (CA-2), et n'autorise que les
 * transitions valides (EF-PRJ-4).
 */
final class ProjectTest extends TestCase
{
    public function testMinimalProjectDefaultsToInProgressAndImputable(): void
    {
        // Usage rétro-compatible (US-050) : projet système/technique imputable par défaut.
        $project = new Project(TenantId::generate(), 'ABSENCE', 'Absence');

        self::assertSame(ProjectStatus::EN_COURS, $project->status());
        self::assertTrue($project->allowsImputation());
    }

    public function testBusinessProjectStartsInPreparationAndBlocksImputation(): void
    {
        $project = $this->business();

        self::assertSame(ProjectStatus::EN_PREPARATION, $project->status());
        self::assertFalse($project->allowsImputation());
        self::assertSame('Acme Corp', $project->clientName());
        self::assertSame(12_000_000, $project->budgetCents());
    }

    public function testImputationOpensWhenStatusBecomesInProgress(): void
    {
        $project = $this->business();
        $project->changeStatus(ProjectStatus::EN_COURS);

        self::assertTrue($project->allowsImputation());
    }

    public function testInvalidTransitionIsRejected(): void
    {
        $project = $this->business(); // En préparation
        $this->expectException(ProjectException::class);

        $project->changeStatus(ProjectStatus::RECEPTIONNE);
    }

    public function testClientIsMandatory(): void
    {
        $this->expectException(ProjectException::class);
        Project::createBusiness(TenantId::generate(), 'PRJ-0001', 'Refonte', '  ', 'marc', 12_000_000, ContractType::FORFAIT, null, null);
    }

    public function testBudgetIsMandatory(): void
    {
        $this->expectException(ProjectException::class);
        Project::createBusiness(TenantId::generate(), 'PRJ-0001', 'Refonte', 'Acme Corp', 'marc', 0, ContractType::FORFAIT, null, null);
    }

    private function business(): Project
    {
        return Project::createBusiness(
            TenantId::generate(),
            'PRJ-0042',
            'Refonte SI',
            'Acme Corp',
            'marc',
            12_000_000,
            ContractType::FORFAIT,
            new DateTimeImmutable('2026-09-01', new DateTimeZone('UTC')),
            new DateTimeImmutable('2027-03-31', new DateTimeZone('UTC')),
        );
    }
}
