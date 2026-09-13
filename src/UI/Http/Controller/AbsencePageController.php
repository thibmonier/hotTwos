<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Absence\AbsenceBalance;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Absence\AbsenceStatus;
use App\Domain\Absence\AbsenceType;
use App\Domain\Absence\AbsenceTypeRepository;
use App\Domain\Calendar\ClosurePeriodRepository;
use App\Domain\Calendar\HolidayRepository;
use App\Domain\Shared\CalendarMonth;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-054 (T-054-06) — module « Mes absences » du collaborateur (adaptateur web).
 *
 * Affiche le widget compteurs (acquis/pris/attente/solde/projeté), la liste des demandes avec
 * badges de statut, et le formulaire de déclaration. Les actions passent par l'API via Stimulus.
 * Périmètre « soi-même » : chaque collaborateur ne voit que ses propres absences.
 *
 * US-091b (CA-1) — ajoute un calendrier du mois affiché (`?month=YYYY-MM`, défaut mois courant) qui
 * distingue fériés, fermetures d'entreprise, absences déjà posées et week-ends, chaque marquage
 * portant une alternative textuelle (aria-label + légende), jamais la couleur seule (WCAG 1.4.1).
 */
final class AbsencePageController extends AbstractController
{
    /** @var array<int, string> libellés de mois (fr), indexés 1..12. */
    private const array MONTHS = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
        7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    public function __construct(
        private readonly AbsenceTypeRepository $types,
        private readonly AbsenceRequestRepository $requests,
        private readonly AbsenceBalance $balance,
        private readonly HolidayRepository $holidays,
        private readonly ClosurePeriodRepository $closures,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/absences', name: 'absence_page', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, Request $request): Response
    {
        $tenant = $user->tenantId();
        $counters = $this->balance->for($tenant, $user->id());

        $month = $request->query->getString('month');
        if (!CalendarMonth::isValid($month)) {
            $month = $this->clock->now()->format('Y-m');
        }

        return $this->render('absence/index.html.twig', [
            'types' => array_map(
                static fn (AbsenceType $t): array => ['id' => $t->id(), 'label' => $t->label()],
                $this->types->findAllByTenant($tenant),
            ),
            'requests' => array_map(
                static fn (AbsenceRequest $r): array => [
                    'from' => $r->startDate()->format('Y-m-d'),
                    'to' => $r->endDate()->format('Y-m-d'),
                    'status' => $r->status()->value,
                    'reason' => $r->rejectionReason(),
                ],
                $this->requests->findForUser($tenant, $user->id()),
            ),
            'counters' => [
                'acquired' => $counters->acquired,
                'taken' => $counters->taken,
                'pending' => $counters->pending,
                'balance' => $counters->balance(),
                'projected' => $counters->projectedBalance(),
            ],
            'calendar' => $this->buildCalendar($tenant, $user->id(), $month),
        ]);
    }

    /**
     * @return array{monthLabel: string, days: list<array{type: string, number: int|null, label: string|null}>}
     */
    private function buildCalendar(TenantId $tenant, string $userId, string $month): array
    {
        [$from, $toExclusive] = CalendarMonth::bounds($month);

        $holidayLabels = [];
        foreach ($this->holidays->findForTenant($tenant) as $holiday) {
            $holidayLabels[$holiday->date()->format('Y-m-d')] = $holiday->label();
        }

        $closureLabels = [];
        foreach ($this->closures->findForTenant($tenant) as $closure) {
            for ($day = $closure->startDate(); $day <= $closure->endDate() && $day < $toExclusive; $day = $day->modify('+1 day')) {
                $closureLabels[$day->format('Y-m-d')] = $closure->label();
            }
        }

        $absenceDays = [];
        foreach ($this->requests->findForUser($tenant, $userId) as $request) {
            if (AbsenceStatus::REJECTED === $request->status()) {
                continue;
            }
            for ($day = $request->startDate(); $day <= $request->endDate() && $day < $toExclusive; $day = $day->modify('+1 day')) {
                $absenceDays[$day->format('Y-m-d')] = true;
            }
        }

        $days = [];
        // Alignement lundi-first : cellules vides avant le 1er du mois.
        $offset = (int) $from->format('N') - 1;
        for ($i = 0; $i < $offset; ++$i) {
            $days[] = ['type' => 'blank', 'number' => null, 'label' => null];
        }
        for ($day = $from; $day < $toExclusive; $day = $day->modify('+1 day')) {
            $days[] = $this->classifyDay($day, $holidayLabels, $closureLabels, $absenceDays);
        }

        return ['monthLabel' => self::MONTHS[(int) $from->format('n')].' '.$from->format('Y'), 'days' => $days];
    }

    /**
     * @param array<string, string> $holidayLabels
     * @param array<string, string> $closureLabels
     * @param array<string, true>   $absenceDays
     *
     * @return array{type: string, number: int|null, label: string|null}
     */
    private function classifyDay(DateTimeImmutable $day, array $holidayLabels, array $closureLabels, array $absenceDays): array
    {
        $key = $day->format('Y-m-d');
        $number = (int) $day->format('j');

        // Priorité des marquages (un jour peut cumuler plusieurs états) : absence posée > férié >
        // fermeture > week-end > jour ouvré normal.
        if (isset($absenceDays[$key])) {
            return ['type' => 'request', 'number' => $number, 'label' => 'mon absence déjà posée'];
        }
        if (isset($holidayLabels[$key])) {
            return ['type' => 'closure', 'number' => $number, 'label' => 'férié : '.$holidayLabels[$key]];
        }
        if (isset($closureLabels[$key])) {
            return ['type' => 'closure', 'number' => $number, 'label' => 'fermeture : '.$closureLabels[$key]];
        }
        if ((int) $day->format('N') >= 6) {
            return ['type' => 'weekend', 'number' => $number, 'label' => 'week-end'];
        }

        return ['type' => 'normal', 'number' => $number, 'label' => null];
    }
}
