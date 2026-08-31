<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\Domain\Tenant\TenantSize;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * US-007 (T-007-04) — génération des jeux de données de test représentatifs
 * des 3 tailles de tenant (ARC-87), régénérables par commande.
 *
 * Adaptateur CLI (ARC-17). La génération effective des entités sera branchée
 * avec le modèle de données du Walking Skeleton (US-001) ; cette commande pose
 * l'ossature et expose les volumétries cibles.
 */
#[AsCommand(
    name: 'app:fixtures:load',
    description: 'Charge les jeux de données de test des 3 tailles de tenant',
)]
final class LoadFixturesCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Jeux de données de test — volumétries cibles (ARC-87)');

        $rows = array_map(
            static fn (TenantSize $size): array => [
                $size->value,
                $size->collaborators(),
                $size->activeProjects(),
                number_format($size->timeEntriesPerYear(), 0, '.', ' '),
            ],
            TenantSize::cases(),
        );

        $io->table(
            ['Taille', 'Collaborateurs', 'Projets actifs', 'Lignes de temps / an'],
            $rows,
        );

        $io->note('Génération effective des entités : à brancher avec le modèle de données (US-001).');

        return Command::SUCCESS;
    }
}
