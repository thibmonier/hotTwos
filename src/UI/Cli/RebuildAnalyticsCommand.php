<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\Application\Analytics\RebuildAnalytics;
use App\Domain\Tenant\TenantId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use InvalidArgumentException;

/**
 * US-005 (ARC-114) — reconstruit complètement le modèle analytique d'un tenant en
 * rejouant son flux d'événements. Idempotente et bornée au tenant.
 */
#[AsCommand(
    name: 'app:analytics:rebuild',
    description: 'Reconstruit le modèle analytique en étoile d\'un tenant (rejeu des événements)',
)]
final class RebuildAnalyticsCommand extends Command
{
    public function __construct(private readonly RebuildAnalytics $rebuildAnalytics)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('tenant', InputArgument::REQUIRED, 'Identifiant (UUID) du tenant à reconstruire');
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

        $this->rebuildAnalytics->forTenant($tenant);

        $io->success(sprintf('Modèle analytique reconstruit pour le tenant %s.', $tenant->toString()));

        return Command::SUCCESS;
    }
}
