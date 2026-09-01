<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Absence;

use App\Application\Absence\DeclareAbsence;
use App\Application\Absence\DecideAbsence;
use App\Application\Absence\Message\AbsenceDeclared;
use App\Application\Absence\Message\AbsenceDecided;
use App\Application\Authorization\Authorizer;
use App\Domain\Absence\AbsenceException;
use App\Domain\Absence\AbsenceStatus;
use App\Domain\Absence\AbsenceType;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Absence\InMemoryAbsenceRequestRepository;
use App\Tests\Support\Absence\InMemoryAbsenceTypeRepository;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Messaging\RecordingMessageBus;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-054 (T-054-02, CA-1/CA-5) — un collaborateur déclare une absence (notifiée), un manager habilité
 * la valide ou la refuse (notifié) ; un non-habilité ne peut pas décider.
 */
final class AbsenceWorkflowTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryAbsenceTypeRepository $types;
    private InMemoryAbsenceRequestRepository $requests;
    private RecordingSecurityAuditLogger $audit;
    private RecordingMessageBus $bus;
    private MockClock $clock;
    private Authorizer $authorizer;
    private User $camille;
    private User $marc;
    private string $typeId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::VALIDATE_ABSENCE], DataScope::OWN_PROJECTS));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->types = new InMemoryAbsenceTypeRepository();
        $this->requests = new InMemoryAbsenceRequestRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->bus = new RecordingMessageBus();
        $this->clock = new MockClock(new DateTimeImmutable('2026-08-20 09:00:00', new DateTimeZone('UTC')));
        $this->authorizer = new Authorizer($roles, $this->audit);

        $type = new AbsenceType($this->tenant, 'Congés payés');
        $this->typeId = $type->id();
        $this->types->save($type);

        $this->camille = new User($this->tenant, 'camille@agence.test', 'hash', ['Collaborateur']);
        $this->marc = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
    }

    public function testDeclareCreatesPendingRequestAndNotifies(): void
    {
        $id = $this->declareUseCase()->declare($this->tenant, $this->camille, $this->typeId, $this->date('2026-09-01'), $this->date('2026-09-05'));

        $request = $this->requests->findById($this->tenant, $id);
        self::assertNotNull($request);
        self::assertSame(AbsenceStatus::PENDING, $request->status());
        self::assertTrue($this->audit->has('absence_declaree'));
        self::assertCount(1, $this->bus->dispatched);
        self::assertInstanceOf(AbsenceDeclared::class, $this->bus->dispatched[0]);
    }

    public function testDeclareWithUnknownTypeIsRejected(): void
    {
        $this->expectException(AbsenceException::class);

        $this->declareUseCase()->declare($this->tenant, $this->camille, TenantId::generate()->toString(), $this->date('2026-09-01'), $this->date('2026-09-05'));
    }

    public function testManagerApprovesAndNotifiesRequester(): void
    {
        $id = $this->declareUseCase()->declare($this->tenant, $this->camille, $this->typeId, $this->date('2026-09-01'), $this->date('2026-09-05'));
        $this->bus->dispatched = [];

        $this->decideUseCase()->approve($this->tenant, $this->marc, $id);

        self::assertSame(AbsenceStatus::VALIDATED, $this->requests->findById($this->tenant, $id)?->status());
        self::assertTrue($this->audit->has('absence_validee'));
        $message = $this->bus->dispatched[0];
        self::assertInstanceOf(AbsenceDecided::class, $message);
        self::assertTrue($message->isApproved());
    }

    public function testManagerRejectsWithReason(): void
    {
        $id = $this->declareUseCase()->declare($this->tenant, $this->camille, $this->typeId, $this->date('2026-09-01'), $this->date('2026-09-05'));

        $this->decideUseCase()->reject($this->tenant, $this->marc, $id, 'Chevauchement livraison critique');

        $request = $this->requests->findById($this->tenant, $id);
        self::assertNotNull($request);
        self::assertSame(AbsenceStatus::REJECTED, $request->status());
        self::assertSame('Chevauchement livraison critique', $request->rejectionReason());
    }

    public function testCollaboratorCannotDecide(): void
    {
        $id = $this->declareUseCase()->declare($this->tenant, $this->camille, $this->typeId, $this->date('2026-09-01'), $this->date('2026-09-05'));

        $this->expectException(AccessDeniedException::class);
        $this->decideUseCase()->approve($this->tenant, $this->camille, $id);
    }

    public function testCannotDecideTwice(): void
    {
        $id = $this->declareUseCase()->declare($this->tenant, $this->camille, $this->typeId, $this->date('2026-09-01'), $this->date('2026-09-05'));
        $this->decideUseCase()->approve($this->tenant, $this->marc, $id);

        $this->expectException(AbsenceException::class);
        $this->decideUseCase()->reject($this->tenant, $this->marc, $id, 'Trop tard');
    }

    public function testManagerCannotDecideOwnAbsence(): void
    {
        // Marc (chef de projet, habilité VALIDATE_ABSENCE) déclare pour lui-même…
        $id = $this->declareUseCase()->declare($this->tenant, $this->marc, $this->typeId, $this->date('2026-09-01'), $this->date('2026-09-05'));

        // …et ne peut pas approuver sa propre demande (séparation des tâches).
        $this->expectException(AbsenceException::class);
        $this->decideUseCase()->approve($this->tenant, $this->marc, $id);
    }

    private function declareUseCase(): DeclareAbsence
    {
        return new DeclareAbsence($this->types, $this->requests, $this->audit, $this->bus, $this->clock);
    }

    private function decideUseCase(): DecideAbsence
    {
        return new DecideAbsence($this->authorizer, $this->requests, $this->audit, $this->bus, $this->clock);
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
