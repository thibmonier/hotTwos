<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Period;

use App\Application\Authorization\Authorizer;
use App\Application\Period\ApproveReopening;
use App\Application\Period\PeriodModificationGuard;
use App\Application\Period\RequestReopening;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Period\PeriodException;
use App\Domain\Period\PeriodLockedException;
use App\Domain\Period\ReopeningStatus;
use App\Domain\Tenant\TenantId;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Period\InMemoryAccountingPeriodRepository;
use App\Tests\Support\Period\InMemoryReopeningRequestRepository;
use App\Domain\User\User;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-057 (T-057-05, CA-2/CA-5) — réouverture formelle : demande habilitée (403 sinon), approbation
 * ouvrant une fenêtre bornée, verrou de modification levé pendant la fenêtre puis re-verrouillé.
 */
final class ReopeningWorkflowTest extends TestCase
{
    private const string WORKDATE = '2026-08-15';

    private TenantId $tenant;
    private InMemoryAccountingPeriodRepository $periods;
    private InMemoryReopeningRequestRepository $reopenings;
    private RecordingSecurityAuditLogger $audit;
    private Authorizer $authorizer;
    private MockClock $clock;
    private User $admin;
    private User $marc;
    private User $collaborator;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Administrateur', [Permission::MANAGE_PERIODS, Permission::REQUEST_PERIOD_REOPENING], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Chef de projet', [Permission::REQUEST_PERIOD_REOPENING], DataScope::OWN_PROJECTS));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->periods = new InMemoryAccountingPeriodRepository();
        $this->reopenings = new InMemoryReopeningRequestRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->authorizer = new Authorizer($roles, $this->audit);
        $this->clock = new MockClock(new DateTimeImmutable('2026-09-01 10:00:00', new DateTimeZone('UTC')));

        $this->admin = new User($this->tenant, 'admin@agence.test', 'hash', ['Administrateur']);
        $this->marc = new User($this->tenant, 'marc@agence.test', 'hash', ['Chef de projet']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);

        // Période août clôturée.
        $closed = new AccountingPeriod($this->tenant, '2026-08');
        $closed->close($this->admin->id(), $this->clock->now());
        $this->periods->save($closed);
    }

    public function testCollaboratorCannotRequestReopening(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->requestUseCase()->request($this->tenant, $this->collaborator, '2026-08', 'Erreur de projet');
    }

    public function testReopeningLiftsThenRestoresTheLock(): void
    {
        // Avant réouverture : le mois est verrouillé.
        $this->assertLocked();

        // Marc demande, l'admin approuve (fenêtre 48 h).
        $requestId = $this->requestUseCase()->request($this->tenant, $this->marc, '2026-08', 'Erreur de projet : P-Alpha → P-Beta');
        $this->approveUseCase()->approve($this->tenant, $this->admin, $requestId);

        $request = $this->reopenings->findById($this->tenant, $requestId);
        self::assertSame(ReopeningStatus::APPROVED, $request?->status());
        self::assertTrue($this->audit->has('reouverture_approuvee'));

        // Pendant la fenêtre : la modification est autorisée (aucune exception).
        $this->guard()->ensureModifiable($this->tenant, $this->marc->id(), $this->date());
        $this->addToAssertionCount(1);

        // Après expiration (49 h plus tard) : de nouveau verrouillé (reclôture passive).
        $this->clock->modify('+49 hours');
        $this->assertLocked();
    }

    public function testCannotRequestReopeningOnOpenPeriod(): void
    {
        $this->expectException(PeriodException::class);

        $this->requestUseCase()->request($this->tenant, $this->marc, '2026-09', 'Pas clôturée');
    }

    private function assertLocked(): void
    {
        try {
            $this->guard()->ensureModifiable($this->tenant, $this->marc->id(), $this->date());
            self::fail('Le mois clôturé doit être verrouillé.');
        } catch (PeriodLockedException) {
            $this->addToAssertionCount(1);
        }
    }

    private function requestUseCase(): RequestReopening
    {
        return new RequestReopening($this->authorizer, $this->periods, $this->reopenings, $this->audit, $this->clock);
    }

    private function approveUseCase(): ApproveReopening
    {
        return new ApproveReopening($this->authorizer, $this->reopenings, $this->audit, $this->clock);
    }

    private function guard(): PeriodModificationGuard
    {
        return new PeriodModificationGuard($this->periods, $this->reopenings, $this->audit, $this->clock);
    }

    private function date(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::WORKDATE.' 00:00:00', new DateTimeZone('UTC'));
    }
}
