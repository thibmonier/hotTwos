<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Timesheet;

use App\Application\Authorization\Authorizer;
use App\Application\Timesheet\ValidateTimeEntries;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimesheetException;
use App\Domain\Timesheet\ValidationStatus;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

/**
 * US-055 — la validation par lot exige la permission (VALIDATE_TIME) et le périmètre
 * « ses projets » (responsable), refuse sans motif, et trace chaque décision (HAB-6).
 */
final class ValidateTimeEntriesTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private InMemoryTimeEntryRepository $entries;
    private RecordingSecurityAuditLogger $audit;
    private ValidateTimeEntries $validate;
    private User $chef;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::VALIDATE_TIME], DataScope::OWN_PROJECTS));
        $this->projects = new InMemoryProjectRepository();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->validate = new ValidateTimeEntries(
            new Authorizer($roles, $this->audit),
            $this->projects,
            $this->entries,
            $this->audit,
        );

        $this->chef = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
    }

    public function testValidatesEntriesOnOwnProject(): void
    {
        $project = new Project($this->tenant, 'PRJ-1', 'Refonte', true, $this->chef->id());
        $this->projects->save($project);
        $this->entries->save(new TimeEntry($this->tenant, 'camille', $project->id(), new DateTimeImmutable('2026-09-15'), 240));
        $entryId = $this->entries->entries[0]->id();

        $decided = $this->validate->validate($this->tenant, $this->chef, [$entryId]);

        self::assertSame(1, $decided);
        self::assertSame(ValidationStatus::VALIDATED, $this->entries->entries[0]->status());
        self::assertTrue($this->audit->has('time_entries_validated'));
    }

    public function testRejectsEntriesWithReason(): void
    {
        $project = new Project($this->tenant, 'PRJ-1', 'Refonte', true, $this->chef->id());
        $this->projects->save($project);
        $this->entries->save(new TimeEntry($this->tenant, 'camille', $project->id(), new DateTimeImmutable('2026-09-15'), 240));
        $entryId = $this->entries->entries[0]->id();

        $this->validate->reject($this->tenant, $this->chef, [$entryId], 'heures non justifiées');

        self::assertSame(ValidationStatus::REJECTED, $this->entries->entries[0]->status());
        self::assertTrue($this->audit->has('time_entries_rejected'));
    }

    public function testRejectWithoutReasonIsRefused(): void
    {
        $this->expectException(TimesheetException::class);

        $this->validate->reject($this->tenant, $this->chef, ['whatever'], '   ');
    }

    public function testCannotValidateOnAProjectNotUnderResponsibility(): void
    {
        $foreign = new Project($this->tenant, 'PRJ-2', 'Autre', true, 'un-autre-chef');
        $this->projects->save($foreign);
        $this->entries->save(new TimeEntry($this->tenant, 'camille', $foreign->id(), new DateTimeImmutable('2026-09-15'), 120));
        $entryId = $this->entries->entries[0]->id();

        try {
            $this->validate->validate($this->tenant, $this->chef, [$entryId]);
            self::fail('Une AccessDeniedException était attendue.');
        } catch (AccessDeniedException) {
        }

        self::assertSame(ValidationStatus::PENDING, $this->entries->entries[0]->status(), 'Aucune décision hors périmètre.');
        self::assertTrue($this->audit->has('out_of_scope_validation_attempt'));
    }

    public function testWithoutValidatePermissionIsRefused(): void
    {
        $collaborateur = new User($this->tenant, 'camille@agence.test', 'hash', ['Collaborateur']); // rôle sans VALIDATE_TIME

        $this->expectException(AccessDeniedException::class);
        $this->validate->validate($this->tenant, $collaborateur, ['x']);
    }
}
