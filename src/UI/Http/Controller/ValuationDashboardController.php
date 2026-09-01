<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\User\User;
use App\Domain\Valuation\TimeEntryValuation;
use App\Domain\Valuation\TimeEntryValuationRepository;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-060 (T-060-06) — tableau de bord financier de la valorisation (adaptateur web).
 *
 * Réservé aux rôles habilités (`VIEW_PROJECT_FINANCIALS`, ARC-106) : chef de projet, resource
 * manager, dirigeant. Affiche la fraîcheur (« mise à jour il y a X min »), l'avancement
 * (valorisées/total), le CA cumulé et un bandeau d'alerte en cas de valorisation incomplète
 * (imputations sans tarif — CA-4). Le **coût, la marge et l'audit trail du taux appliqué** sont
 * des données sensibles (HAB-1) : affichés uniquement aux porteurs de `VIEW_COLLABORATOR_COST`,
 * dont la lecture est tracée (HAB-6).
 */
final class ValuationDashboardController extends AbstractController
{
    /** Nombre de lignes valorisées détaillées dans l'audit trail. */
    private const int AUDIT_TRAIL_LIMIT = 50;

    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly TimeEntryValuationRepository $valuations,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/valorisation', name: 'valuation_dashboard', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_PROJECT_FINANCIALS);

        $tenant = $user->tenantId();
        $summary = $this->valuations->summaryFor($tenant);

        $canViewCost = $this->authorizer->can($user, Permission::VIEW_COLLABORATOR_COST);
        $auditTrail = [];
        if ($canViewCost) {
            // Lecture sensible (coût/taux) : autorisée puis tracée (HAB-6).
            $this->authorizer->authorizeSensitiveRead($user, Permission::VIEW_COLLABORATOR_COST, 'valuation:dashboard');
            $auditTrail = array_map(
                static fn (TimeEntryValuation $v): array => [
                    'timeEntryId' => $v->timeEntryId(),
                    'revenueCents' => $v->revenueCents(),
                    'costCents' => $v->costCents(),
                    'sellingRateCents' => $v->snapshotSellingRateCents(),
                    'costRateCents' => $v->snapshotCostRateCents(),
                    'rateDate' => $v->snapshotRateDate()?->format('Y-m-d'),
                    'valuedAt' => $v->valuedAt()->format('Y-m-d H:i'),
                ],
                $this->valuations->findValued($tenant, self::AUDIT_TRAIL_LIMIT),
            );
        }

        return $this->render('valuation/index.html.twig', [
            'summary' => $summary,
            'freshnessMinutes' => $this->freshnessMinutes($summary->lastValuedAt),
            'canViewCost' => $canViewCost,
            'auditTrail' => $auditTrail,
        ]);
    }

    private function freshnessMinutes(?DateTimeImmutable $lastValuedAt): ?int
    {
        if (!$lastValuedAt instanceof DateTimeImmutable) {
            return null;
        }

        return intdiv(max(0, $this->clock->now()->getTimestamp() - $lastValuedAt->getTimestamp()), 60);
    }
}
