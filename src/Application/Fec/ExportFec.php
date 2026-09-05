<?php

declare(strict_types=1);

namespace App\Application\Fec;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Fec\FecConfiguration;
use App\Domain\Fec\FecConfigurationRepository;
use App\Domain\Fec\FecExportException;
use App\Domain\Fec\FecGenerator;
use App\Domain\Margin\ProjectMarginRepository;
use App\Domain\Shared\CalendarMonth;
use App\Domain\User\User;
use App\Domain\Valuation\PeriodClosureStatus;

/**
 * Export comptable FEC d'une période clôturée (US-074, CA-1/CA-3/CA-4).
 *
 * Réservé aux rôles finance/direction : accès {@see Permission::VIEW_PROJECT_FINANCIALS} ; le fichier
 * contenant des coûts, il exige aussi {@see Permission::VIEW_COLLABORATOR_COST} (HAB-1) et trace la
 * lecture sensible (HAB-6). N'exporte que des **périodes clôturées** (données figées, opposables) et
 * requiert une **configuration comptable** (SIREN + comptes). Le contenu provient du moteur unique
 * {@see FecGenerator} — aucun recalcul de marge.
 */
final readonly class ExportFec
{
    public function __construct(
        private Authorizer $authorizer,
        private PeriodClosureStatus $closureStatus,
        private FecConfigurationRepository $configurations,
        private ProjectMarginRepository $margins,
        private FecGenerator $generator,
    ) {
    }

    public function forPeriod(User $user, string $period): FecExport
    {
        $this->authorizer->ensureCan($user, Permission::VIEW_PROJECT_FINANCIALS);
        $this->authorizer->authorizeSensitiveRead($user, Permission::VIEW_COLLABORATOR_COST, 'fec:export:'.$period);

        if (!CalendarMonth::isValid($period)) {
            throw new FecExportException(sprintf('Période invalide « %s » (attendu YYYY-MM).', $period));
        }

        $tenant = $user->tenantId();
        if (!$this->closureStatus->isClosed($tenant, $period)) {
            throw new FecExportException('Période non clôturée — export FEC non opposable. Clôturez la période (US-057).');
        }

        $config = $this->configurations->findForTenant($tenant);
        if (!$config instanceof FecConfiguration) {
            throw new FecExportException('Configuration comptable requise (SIREN + comptes) pour générer le FEC.');
        }

        $lines = $this->generator->lines($config, $period, $this->margins->findForPeriod($tenant, $period));

        return new FecExport(
            $this->generator->fileName($config, $period),
            $this->generator->render($lines),
        );
    }
}
