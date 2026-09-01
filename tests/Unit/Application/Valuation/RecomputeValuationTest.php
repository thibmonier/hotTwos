<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Valuation;

use App\Application\Authorization\Authorizer;
use App\Application\Timesheet\Message\TimeEntriesValidated;
use App\Application\Valuation\RecomputeValuation;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\DataScope;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\Role;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use App\Domain\Valuation\PeriodClosedException;
use App\Domain\Valuation\ValuationException;
use App\Infrastructure\Valuation\ConfiguredPeriodClosure;
use App\Tests\Support\Authorization\InMemoryRoleRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Messaging\RecordingMessageBus;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-060 (T-060-05, CA-5) — recalcul manuel de valorisation : habilité seulement (403), verrouillé
 * sur période clôturée (423), re-valorise les imputations validées du mois et trace l'opération.
 */
final class RecomputeValuationTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';
    private const string PROJECT = '018f9c4e-0000-7000-8000-0000000000bb';

    private TenantId $tenant;
    private InMemoryTimeEntryRepository $entries;
    private RecordingSecurityAuditLogger $audit;
    private RecordingMessageBus $bus;
    private Authorizer $authorizer;
    private User $admin;
    private User $collaborator;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $roles = new InMemoryRoleRepository();
        $roles->add(new Role($this->tenant, 'Administrateur', [Permission::RECOMPUTE_VALUATION], DataScope::TENANT));
        $roles->add(new Role($this->tenant, 'Collaborateur', [Permission::VIEW_PROJECT], DataScope::OWN));

        $this->entries = new InMemoryTimeEntryRepository();
        $this->audit = new RecordingSecurityAuditLogger();
        $this->bus = new RecordingMessageBus();
        $this->authorizer = new Authorizer($roles, $this->audit);

        $this->admin = new User($this->tenant, 'admin@agence.test', 'hash', ['Administrateur']);
        $this->collaborator = new User($this->tenant, 'collab@agence.test', 'hash', ['Collaborateur']);
    }

    public function testUnauthorizedActorIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->recompute([])->forPeriod($this->tenant, $this->collaborator, '2026-08');
    }

    public function testClosedPeriodIsLocked(): void
    {
        $recompute = $this->recompute(['2026-08']);

        try {
            $recompute->forPeriod($this->tenant, $this->admin, '2026-08');
            self::fail('Une période clôturée doit verrouiller le recalcul.');
        } catch (PeriodClosedException $exception) {
            self::assertStringContainsString('Août 2026', $exception->getMessage());
        }

        self::assertSame([], $this->bus->dispatched, 'Aucun recalcul n\'est dispatché sur période clôturée.');
        self::assertTrue($this->audit->has('valuation_recompute_blocked_closed_period'));
    }

    public function testInvalidPeriodIsRejected(): void
    {
        $this->expectException(ValuationException::class);

        $this->recompute([])->forPeriod($this->tenant, $this->admin, '2026-13');
    }

    public function testOpenPeriodRecomputesValidatedEntriesOfTheMonth(): void
    {
        $inAugust1 = $this->validatedEntry($this->date('2026-08-05'));
        $inAugust2 = $this->validatedEntry($this->date('2026-08-28'));
        $this->validatedEntry($this->date('2026-07-31')); // hors période
        $this->pendingEntry($this->date('2026-08-15'));    // non validée → hors recalcul

        $count = $this->recompute([])->forPeriod($this->tenant, $this->admin, '2026-08');

        self::assertSame(2, $count);
        self::assertCount(1, $this->bus->dispatched);
        $message = $this->bus->dispatched[0];
        self::assertInstanceOf(TimeEntriesValidated::class, $message);
        self::assertSame([$inAugust1, $inAugust2], $message->timeEntryIds());
        self::assertTrue($this->audit->has('valuation_recomputed'));
    }

    public function testOpenPeriodWithoutEntriesDispatchesNothingButTraces(): void
    {
        $count = $this->recompute([])->forPeriod($this->tenant, $this->admin, '2026-08');

        self::assertSame(0, $count);
        self::assertSame([], $this->bus->dispatched);
        self::assertTrue($this->audit->has('valuation_recomputed'));
    }

    /**
     * @param list<string> $closedPeriods
     */
    private function recompute(array $closedPeriods): RecomputeValuation
    {
        return new RecomputeValuation(
            $this->authorizer,
            new ConfiguredPeriodClosure($closedPeriods),
            $this->entries,
            $this->audit,
            $this->bus,
            new MockClock(new DateTimeImmutable('2026-09-01 10:00:00', new DateTimeZone('UTC'))),
        );
    }

    private function validatedEntry(DateTimeImmutable $workDate): string
    {
        $entry = new TimeEntry($this->tenant, self::USER, self::PROJECT, $workDate, 420);
        $entry->validate(self::USER, new DateTimeImmutable('2026-08-31 18:00:00', new DateTimeZone('UTC')));
        $this->entries->save($entry);

        return $entry->id();
    }

    private function pendingEntry(DateTimeImmutable $workDate): void
    {
        $this->entries->save(new TimeEntry($this->tenant, self::USER, self::PROJECT, $workDate, 210));
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
