<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Period\ClosePeriod;
use App\Domain\Authorization\Permission;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Period\AccountingPeriodRepository;
use App\Domain\Period\PeriodException;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-057 (T-057-07) — écran d'administration des périodes comptables (adaptateur web).
 *
 * Réservé à `MANAGE_PERIODS` (ARC-106). Liste les périodes avec leur statut (code couleur) et
 * permet la clôture, protégée par une **saisie de confirmation** (le code de la période) pour
 * éviter les clics accidentels (CA-1). Le déclenchement des calculs aval et le verrouillage sont
 * portés par {@see ClosePeriod}.
 */
final class PeriodAdminController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly AccountingPeriodRepository $periods,
        private readonly ClosePeriod $closePeriod,
    ) {
    }

    #[Route('/administration/periodes', name: 'period_admin', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_PERIODS);

        return $this->render('period/index.html.twig', [
            'periods' => array_map(
                static fn (AccountingPeriod $p): array => [
                    'period' => $p->period(),
                    'status' => $p->status()->value,
                    'closedAt' => $p->closedAt()?->format('Y-m-d H:i'),
                ],
                $this->periods->findAllByTenant($user->tenantId()),
            ),
        ]);
    }

    #[Route('/administration/periodes/cloturer', name: 'period_close', methods: ['POST'])]
    public function close(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_PERIODS);

        if (!$this->isCsrfTokenValid('close_period', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('period_admin');
        }

        $period = trim((string) $request->request->get('period'));
        $confirmation = trim((string) $request->request->get('confirmation'));
        $force = '1' === $request->request->get('force');

        if ($period !== $confirmation) {
            $this->addFlash('error', 'Confirmation invalide : saisissez le code exact de la période.');

            return $this->redirectToRoute('period_admin');
        }

        try {
            $excluded = $this->closePeriod->close($user->tenantId(), $user, $period, $force);
            $this->addFlash('success', 0 === $excluded
                ? sprintf('Période %s clôturée.', $period)
                : sprintf('Période %s clôturée (%d imputation(s) non finalisée(s) exclue(s)).', $period, $excluded));
        } catch (PeriodException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('period_admin');
    }
}
