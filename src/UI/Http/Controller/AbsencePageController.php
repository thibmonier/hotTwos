<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Absence\AbsenceBalance;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Absence\AbsenceType;
use App\Domain\Absence\AbsenceTypeRepository;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-054 (T-054-06) — module « Mes absences » du collaborateur (adaptateur web).
 *
 * Affiche le widget compteurs (acquis/pris/attente/solde/projeté), la liste des demandes avec
 * badges de statut, et le formulaire de déclaration. Les actions passent par l'API via Stimulus.
 * Périmètre « soi-même » : chaque collaborateur ne voit que ses propres absences.
 */
final class AbsencePageController extends AbstractController
{
    public function __construct(
        private readonly AbsenceTypeRepository $types,
        private readonly AbsenceRequestRepository $requests,
        private readonly AbsenceBalance $balance,
    ) {
    }

    #[Route('/absences', name: 'absence_page', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $tenant = $user->tenantId();
        $counters = $this->balance->for($tenant, $user->id());

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
        ]);
    }
}
