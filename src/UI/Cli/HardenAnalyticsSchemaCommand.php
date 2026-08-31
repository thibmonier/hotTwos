<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\Application\Analytics\HardenAnalyticsSchema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * US-005 (CA-4/CA-6) — applique le durcissement PostgreSQL des tables analytiques :
 * isolation RLS et protection anti-écriture directe. Idempotente.
 *
 * En production, ce DDL relèvera d'une migration Doctrine ; cette commande fournit le
 * mécanisme au Walking Skeleton.
 */
#[AsCommand(
    name: 'app:analytics:harden-schema',
    description: 'Applique RLS et la protection anti-écriture directe sur les tables analytiques',
)]
final class HardenAnalyticsSchemaCommand extends Command
{
    public function __construct(private readonly HardenAnalyticsSchema $hardenAnalyticsSchema)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->hardenAnalyticsSchema->apply();
        $io->success('Tables analytiques durcies : RLS activée (FORCE) et écriture directe protégée.');

        return Command::SUCCESS;
    }
}
