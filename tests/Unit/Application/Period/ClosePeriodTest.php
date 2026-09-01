<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Period;

use App\Application\Authorization\Authorizer;
use App\Application\Period\ClosePeriod;
use App\Application\Period\Message\PeriodClosed;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Period\PeriodException;
use App\Domain\Period\PeriodStatus;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Messaging\RecordingMessageBus;
use App\Tests\Support\Period\InMemoryAccountingPeriodRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-057 (T-057-02, CA-1/CA-3) — clôture d'une période : habilité seulement (403), verrouille la
 * période et publie les calculs aval, refuse si des imputations ne sont pas finalisées (sauf force).
 */
final class ClosePeriodTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';
    private const string PROJECT = '018f9c4e-0000-7000-8000-0000000000bb';

    private TenantId $tenant;
    private InMemoryAccountingPeriodRepository $periods;
    private InMemoryTimeEntryRepository $entries;
    private RecordingSecurityAuditLogger $audit;
    private RecordingMessageBus $bus;
    private ClosePeriod $close;
    private User $admin;
    private User $collaborator;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Administrateur', [Permission::MANAGE_PERIODS], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->periods = new InMemoryAccountingPeriodRepository();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->bus = new RecordingMessageBus();
        $this->close = new ClosePeriod(
            new Authorizer($roles, $this->audit),
            $this->periods,
            $this->entries,
            $this->audit,
            $this->bus,
            new MockClock(new DateTimeImmutable('2026-09-01 10:00:00', new DateTimeZone('UTC'))),
        );

        $this->admin = new User($this->tenant, 'admin@agence.test', 'hash', ['Administrateur']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);
    }

    public function testUnauthorizedActorIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->close->close($this->tenant, $this->collaborator, '2026-08');
    }

    public function testClosesACleanPeriodAndTriggersDownstream(): void
    {
        $count = $this->close->close($this->tenant, $this->admin, '2026-08');

        self::assertSame(0, $count);
        $period = $this->periods->findByPeriod($this->tenant, '2026-08');
        self::assertNotNull($period);
        self::assertSame(PeriodStatus::CLOSED, $period->status());
        self::assertTrue($this->audit->has('periode_cloturee'));
        self::assertCount(1, $this->bus->dispatched);
        self::assertInstanceOf(PeriodClosed::class, $this->bus->dispatched[0]);
    }

    public function testRefusesWhenUnvalidatedEntriesWithoutForce(): void
    {
        $this->entries->save(new TimeEntry($this->tenant, self::USER, self::PROJECT, $this->date('2026-08-15'), 420)); // PENDING

        try {
            $this->close->close($this->tenant, $this->admin, '2026-08');
            self::fail('La clôture doit être refusée en présence d\'imputations non finalisées.');
        } catch (PeriodException $exception) {
            self::assertStringContainsString('non finalisée', $exception->getMessage());
        }

        self::assertNull($this->periods->findByPeriod($this->tenant, '2026-08'));
        self::assertSame([], $this->bus->dispatched);
    }

    public function testForceClosesDespiteUnvalidatedEntries(): void
    {
        $this->entries->save(new TimeEntry($this->tenant, self::USER, self::PROJECT, $this->date('2026-08-15'), 420));

        $count = $this->close->close($this->tenant, $this->admin, '2026-08', true);

        self::assertSame(1, $count);
        self::assertTrue($this->periods->findByPeriod($this->tenant, '2026-08')?->isClosed());
        self::assertCount(1, $this->bus->dispatched);
    }

    public function testAlreadyClosedPeriodIsRejected(): void
    {
        $this->close->close($this->tenant, $this->admin, '2026-08');

        $this->expectException(PeriodException::class);
        $this->close->close($this->tenant, $this->admin, '2026-08');
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
