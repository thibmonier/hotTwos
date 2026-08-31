<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\Application\Authorization\DefaultRoleMatrix;
use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Tenant\TenantId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use InvalidArgumentException;

/**
 * US-003 (CA-4) — initialise la matrice de rôles standard d'un tenant.
 *
 * Adaptateur CLI (ARC-17) : déléguée au cas d'usage {@see InitializeDefaultRoles},
 * idempotente (relancer converge vers la matrice sans créer de doublon).
 */
#[AsCommand(
    name: 'app:tenant:init-roles',
    description: 'Initialise (ou réaligne) la matrice de rôles standard d\'un tenant',
)]
final class InitializeDefaultRolesCommand extends Command
{
    public function __construct(private readonly InitializeDefaultRoles $initializeDefaultRoles)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('tenant', InputArgument::REQUIRED, 'Identifiant (UUID) du tenant à initialiser');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $raw */
        $raw = $input->getArgument('tenant');

        try {
            $tenant = TenantId::fromString($raw);
        } catch (InvalidArgumentException $exception) {
            $io->error(sprintf('Identifiant de tenant invalide : %s', $exception->getMessage()));

            return Command::INVALID;
        }

        $this->initializeDefaultRoles->forTenant($tenant);

        $io->success(sprintf(
            'Matrice de rôles appliquée au tenant %s (%d rôles de référence).',
            $tenant->toString(),
            count(DefaultRoleMatrix::definitions()),
        ));

        return Command::SUCCESS;
    }
}
