<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Fec\ExportFec;
use App\Application\Finance\ConsolidatedFinanceReport;
use App\Domain\Fec\FecExportException;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-073 (T-073-03) — tableau de bord finance consolidé (adaptateur web).
 *
 * Réservé aux rôles finance/direction : l'accès exige `VIEW_PROJECT_FINANCIALS` (deny-by-default,
 * 403 habillée via l'ExceptionListener). Le gating fin (coût/marge réservés à `VIEW_COLLABORATOR_COST`,
 * HAB-1) et la traçabilité (HAB-6) sont portés par le read service. Sélection de période (mois figé)
 * et filtre client appliqués côté backend.
 */
final class FinanceDashboardController extends AbstractController
{
    public function __construct(
        private readonly ConsolidatedFinanceReport $report,
        private readonly ExportFec $exportFec,
    ) {
    }

    #[Route('/finance', name: 'finance_dashboard', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, Request $request): Response
    {
        $period = $this->cleanParam($request->query->get('period'));
        $client = $this->cleanParam($request->query->get('client'));

        return $this->render('finance/index.html.twig', [
            'dashboard' => $this->report->forPeriod($user, $period, $client),
        ]);
    }

    #[Route('/finance/export/fec', name: 'finance_export_fec', methods: ['GET'])]
    public function exportFec(#[CurrentUser] User $user, Request $request): Response
    {
        $period = $this->cleanParam($request->query->get('period')) ?? '';

        try {
            $export = $this->exportFec->forPeriod($user, $period);
        } catch (FecExportException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return new RedirectResponse($this->generateUrl('finance_dashboard', ['period' => $period]));
        }

        $response = new Response($export->content);
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition('attachment', $export->fileName));

        return $response;
    }

    private function cleanParam(mixed $raw): ?string
    {
        return is_string($raw) && '' !== trim($raw) ? trim($raw) : null;
    }
}
