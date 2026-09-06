<?php

declare(strict_types=1);

namespace App\Application\Invoice;

use App\Application\Authorization\Authorizer;
use App\Application\Margin\ComputeProjectMargins;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Invoice\Invoice;
use App\Domain\Invoice\InvoiceException;
use App\Domain\Invoice\InvoiceRepository;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Shared\CalendarMonth;
use App\Domain\User\User;
use App\Domain\Valuation\PeriodClosureStatus;
use Psr\Clock\ClockInterface;

/**
 * Émission manuelle d'une facture pour un projet et une période clôturée (US-075, ADR-0022).
 *
 * Réservé aux rôles finance/direction ({@see Permission::VIEW_PROJECT_FINANCIALS}) ; l'émission est
 * tracée (HAB-6). N'émet que sur une **période clôturée** (données figées) et un **montant > 0**. Le
 * client est copié du projet à l'émission. La facture est figée (INV-2).
 */
final readonly class IssueInvoice
{
    public function __construct(
        private Authorizer $authorizer,
        private PeriodClosureStatus $closureStatus,
        private ProjectRepository $projects,
        private InvoiceRepository $invoices,
        private ComputeProjectMargins $computeMargins,
        private SecurityAuditLogger $audit,
        private ClockInterface $clock,
    ) {
    }

    public function issue(User $user, string $projectId, string $period, int $amountCents): Invoice
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_PROJECT_FINANCIALS);

        if (!CalendarMonth::isValid($period)) {
            throw new InvoiceException(sprintf('Période invalide « %s » (attendu YYYY-MM).', $period));
        }
        if ($amountCents <= 0) {
            throw new InvoiceException('Le montant de la facture doit être strictement positif.');
        }

        $tenant = $user->tenantId();
        if (!$this->closureStatus->isClosed($tenant, $period)) {
            throw new InvoiceException('Facturation impossible : la période n\'est pas clôturée.');
        }

        $project = $this->projects->find($tenant, $projectId);
        if (!$project instanceof Project) {
            throw new InvoiceException('Projet introuvable.');
        }

        $invoice = Invoice::issue($tenant, $projectId, $period, $amountCents, $project->clientId(), $user->id(), $this->clock->now());
        $this->invoices->save($invoice);

        // Re-figeage de la marge de la période avec le facturé réel (ADR-0022, US-076) : la marge, le
        // dashboard et le FEC reflètent désormais le facturé réel. Acte explicite et tracé (HAB-6).
        $this->computeMargins->forClosedPeriod($tenant, $period);

        $this->audit->record('invoice_issued', $tenant->toString(), $user->getUserIdentifier(), [
            'project' => $projectId,
            'period' => $period,
            'amount_cents' => (string) $amountCents,
        ]);

        return $invoice;
    }
}
