<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\Application\Onboarding\InitializeTenantDefaults;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * US-019 (EF-REF-29) — provisionne les valeurs par défaut d'un tenant (rôles, devise EUR, échelle de
 * compétences, profil et client par défaut, jours fériés de l'année). **Idempotent**.
 *
 * Adaptateur CLI (ARC-17) : pose le contexte de tenant PostgreSQL avant les écritures cloisonnées (RLS).
 */
#[AsCommand(name: 'tenant:init', description: 'Provisionne les valeurs par défaut d\'un tenant (US-019, idempotent)')]
final class InitializeTenantCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly InitializeTenantDefaults $defaults,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('tenantId', InputArgument::REQUIRED, 'Identifiant (UUID) du tenant à initialiser');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rawArgument = $input->getArgument('tenantId');
        $raw = is_string($rawArgument) ? $rawArgument : '';

        try {
            $tenantId = TenantId::fromString($raw);
        } catch (Throwable) {
            $io->error(sprintf('Identifiant de tenant invalide : « %s ».', $raw));

            return Command::INVALID;
        }

        if (!$this->em->find(Tenant::class, $tenantId->toString()) instanceof Tenant) {
            $io->error(sprintf('Tenant introuvable : %s.', $tenantId->toString()));

            return Command::FAILURE;
        }

        // Contexte de tenant posé pour franchir la RLS sur les écritures cloisonnées (parité HTTP/worker).
        $this->em->getConnection()->executeStatement("SELECT set_config('app.current_tenant', ?, false)", [$tenantId->toString()]);

        $this->defaults->forTenant($tenantId);

        $io->success(sprintf('Valeurs par défaut provisionnées pour le tenant %s.', $tenantId->toString()));

        return Command::SUCCESS;
    }
}
