<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\Application\Reminder\Message\SendDueReminders;
use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantRegistry;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use InvalidArgumentException;

/**
 * US-056 (T-056-03) — déclencheur périodique (cron) des relances de retard de saisie.
 *
 * Ne calcule rien lui-même : publie un ordre **asynchrone** {@see SendDueReminders} par tenant, figé
 * à l'instant courant. Le calcul déterministe et l'émission ont lieu dans le worker, sous contexte de
 * tenant (RLS). Sans argument, itère tous les tenants ; avec un argument, cible un tenant précis.
 */
#[AsCommand(
    name: 'app:reminders:run',
    description: 'Déclenche les relances de retard de saisie (dispatch asynchrone par tenant)',
)]
final class RunRemindersCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly TenantRegistry $tenants,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('tenant', InputArgument::OPTIONAL, 'Identifiant (UUID) d\'un tenant à relancer ; par défaut, tous');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = $this->clock->now();

        try {
            $targets = $this->targets($input);
        } catch (InvalidArgumentException $exception) {
            $io->error(sprintf('Identifiant de tenant invalide : %s', $exception->getMessage()));

            return Command::INVALID;
        }

        foreach ($targets as $tenant) {
            $this->bus->dispatch(new SendDueReminders($tenant->toString(), $now));
        }

        $io->success(sprintf('%d tenant(s) programmé(s) pour relance.', count($targets)));

        return Command::SUCCESS;
    }

    /**
     * @return list<TenantId>
     */
    private function targets(InputInterface $input): array
    {
        $raw = $input->getArgument('tenant');
        if (is_string($raw) && '' !== $raw) {
            return [TenantId::fromString($raw)];
        }

        return $this->tenants->allIds();
    }
}
