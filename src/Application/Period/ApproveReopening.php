<?php

declare(strict_types=1);

namespace App\Application\Period;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Period\PeriodException;
use App\Domain\Period\ReopeningRequest;
use App\Domain\Period\ReopeningRequestRepository;
use App\Domain\Period\ReopeningStatus;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;

/**
 * Approbation d'une demande de réouverture (US-057, CA-2).
 *
 * Réservé à `MANAGE_PERIODS` (administrateur → 403). Ouvre une fenêtre de modification bornée
 * (`VALIDITY_HOURS`) au-delà de laquelle la période est de nouveau verrouillée (reclôture
 * automatique passive). Tracée (demandeur d'origine, approbateur, validité — HAB-6).
 */
final readonly class ApproveReopening
{
    /** Durée de validité par défaut d'une réouverture (heures ; « ouvrées » = raffinement ultérieur). */
    private const int VALIDITY_HOURS = 48;

    public function __construct(
        private Authorizer $authorizer,
        private ReopeningRequestRepository $reopenings,
        private SecurityAuditLogger $audit,
        private ClockInterface $clock,
    ) {
    }

    public function approve(TenantId $tenant, User $approver, string $requestId): void
    {
        $this->authorizer->ensureCan($approver, Permission::MANAGE_PERIODS);

        $request = $this->reopenings->findById($tenant, $requestId);
        if (!$request instanceof ReopeningRequest) {
            throw new PeriodException('Demande de réouverture introuvable.');
        }
        if (ReopeningStatus::REQUESTED !== $request->status()) {
            throw new PeriodException('Cette demande de réouverture a déjà été traitée.');
        }

        // Séparation des tâches (4-eyes, INV-7) : l'approbateur ne peut pas être le demandeur.
        if ($request->requestedBy() === $approver->id()) {
            $this->audit->record('reouverture_auto_approbation_refusee', $tenant->toString(), $approver->getUserIdentifier(), [
                'request' => $request->id(),
            ]);

            throw new PeriodException('Une réouverture ne peut pas être approuvée par son demandeur (contrôle à deux personnes).');
        }

        $validUntil = $this->clock->now()->modify(sprintf('+%d hours', self::VALIDITY_HOURS));
        $request->approve($approver->id(), $validUntil);
        $this->reopenings->save($request);

        $this->audit->record('reouverture_approuvee', $tenant->toString(), $approver->getUserIdentifier(), [
            'request' => $request->id(),
            'period' => $request->period(),
            'valid_until' => $validUntil->format(DATE_ATOM),
        ]);
    }
}
