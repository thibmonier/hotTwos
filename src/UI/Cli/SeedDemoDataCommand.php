<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Application\Timesheet\EnsureAbsenceProject;
use App\Application\Timesheet\Message\TimeEntriesValidated;
use App\Application\Valuation\ValueValidatedTimeHandler;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceType;
use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileAssignment;
use App\Domain\Pricing\ProfileRate;
use App\Domain\Project\Project;
use App\Domain\Reminder\ReminderRule;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use DateTimeImmutable;

/**
 * T-TECH-04 (EPIC-003) — sème un tenant de **démonstration** cohérent qui exerce tous les modules du
 * cycle temps : saisie (US-050/052), complétude (US-058), absences (US-054), relances (US-056) et
 * synthèse d'activité (US-059). Données **synthétiques** régénérables, jamais en production (ADR-13).
 *
 * Adaptateur CLI (ARC-17). Le contexte de tenant PostgreSQL (`app.current_tenant`) est posé avant les
 * écritures cloisonnées pour franchir la RLS, comme le fait le chemin HTTP/worker.
 */
#[AsCommand(
    name: 'app:demo:seed',
    description: 'Sème un tenant de démonstration EPIC-003 (saisie, complétude, absences, relances, synthèse)',
)]
final class SeedDemoDataCommand extends Command
{
    private const string PASSWORD = 'demo-1234-solide';
    private const int FULL_DAY = 420;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PasswordHasherFactoryInterface $hasherFactory,
        private readonly InitializeDefaultRoles $initializeDefaultRoles,
        private readonly EnsureAbsenceProject $ensureAbsenceProject,
        private readonly ValueValidatedTimeHandler $valuationHandler,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Requis en production (interdit par défaut — ADR-13)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ('prod' === $this->environment && true !== $input->getOption('force')) {
            $io->error('Refus : seed de démo interdit en production sans --force (ADR-13 — aucune donnée synthétique en prod).');

            return Command::FAILURE;
        }

        $tenant = TenantId::generate();
        $this->em->persist(new Tenant($tenant, 'Démo Atoll Tourisme'));
        $this->em->flush();

        // Contexte de tenant posé pour franchir la RLS sur les écritures cloisonnées (parité HTTP/worker).
        $this->em->getConnection()->executeStatement("SELECT set_config('app.current_tenant', ?, false)", [$tenant->toString()]);

        $this->initializeDefaultRoles->forTenant($tenant);
        $users = $this->seedUsers($tenant);
        $projects = $this->seedProjects($tenant, $users['marc']);
        $this->seedAbsence($tenant, $users['camille'], $users['marc']);
        $validatedIds = $this->seedTimeEntries($tenant, $users['camille'], $users['marc'], $projects);
        $this->em->persist(ReminderRule::default($tenant));
        $this->em->flush();

        // Pricing + valorisation : profil, tarif et affectation de Camille, puis valorisation des
        // imputations validées (US-060). Sans cela, /valorisation reste vide (finding F2).
        $this->seedPricingAndValuation($tenant, $users['camille'], $validatedIds);

        $io->success('Tenant de démonstration créé.');
        $io->table(['Élément', 'Valeur'], [
            ['Tenant', $tenant->toString()],
            ['Connexions', 'camille@demo.test (Collaborateur) · marc@demo.test (Chef de projet) · admin@demo.test (Administrateur)'],
            ['Mot de passe', self::PASSWORD],
            ['Écrans', '/saisie · /saisie/jour · /completude · /absences · /relances · /administration/periodes'],
        ]);

        return Command::SUCCESS;
    }

    /**
     * @return array{camille: User, marc: User, admin: User}
     */
    private function seedUsers(TenantId $tenant): array
    {
        $hash = $this->hasherFactory->getPasswordHasher(User::class)->hash(self::PASSWORD);

        $users = [
            'camille' => new User($tenant, 'camille@demo.test', $hash, ['Collaborateur']),
            'marc' => new User($tenant, 'marc@demo.test', $hash, ['Chef de projet']),
            'admin' => new User($tenant, 'admin@demo.test', $hash, ['Administrateur']),
        ];
        foreach ($users as $user) {
            $this->em->persist($user);
        }
        $this->em->flush();

        return $users;
    }

    /**
     * @return array{alpha: string, beta: string}
     */
    private function seedProjects(TenantId $tenant, User $marc): array
    {
        $this->ensureAbsenceProject->forTenant($tenant);
        // Marc (chef de projet) est responsable des deux projets : ses imputations en attente
        // alimentent l'écran /validation (parcours peuplé, cf. US-069 T-069-03).
        $alpha = new Project($tenant, 'ALPHA', 'Refonte site Alpha', true, $marc->id());
        $beta = new Project($tenant, 'BETA', 'Application mobile Beta', true, $marc->id());
        $this->em->persist($alpha);
        $this->em->persist($beta);
        $this->em->flush();

        return ['alpha' => $alpha->id(), 'beta' => $beta->id()];
    }

    private function seedAbsence(TenantId $tenant, User $camille, User $marc): void
    {
        $type = new AbsenceType($tenant, 'Congés payés');
        $this->em->persist($type);

        $wed = $this->monday(2)->modify('+2 days');
        $absence = new AbsenceRequest($tenant, $camille->id(), $type->id(), $wed, $wed->modify('+1 day'), true, true, $this->monday(3));
        $absence->validate($marc->id(), $this->monday(2));
        $this->em->persist($absence);
        $this->em->flush();
    }

    /**
     * @param array{alpha: string, beta: string} $projects
     */
    /**
     * @param array{alpha: string, beta: string} $projects
     *
     * @return list<string> identifiants des imputations validées (à valoriser)
     */
    private function seedTimeEntries(TenantId $tenant, User $camille, User $marc, array $projects): array
    {
        $validatedIds = [];
        // Semaine -3 : complète et validée (complétude « soumise »).
        for ($d = 0; $d < 5; ++$d) {
            $entry = new TimeEntry($tenant, $camille->id(), $projects['alpha'], $this->monday(3)->modify(sprintf('+%d days', $d)), self::FULL_DAY);
            $entry->validate($marc->id(), $this->monday(2));
            $this->em->persist($entry);
            $validatedIds[] = $entry->id();
        }
        // Semaine -2 : partielle, soumise (lun/mar) ; mer/jeu = absence validée.
        $this->em->persist(new TimeEntry($tenant, $camille->id(), $projects['beta'], $this->monday(2), self::FULL_DAY));
        $this->em->persist(new TimeEntry($tenant, $camille->id(), $projects['beta'], $this->monday(2)->modify('+1 day'), self::FULL_DAY));
        // Semaine -1 : vide (retard → candidate à la relance).
        // Semaine courante : une journée saisie (en cours).
        $this->em->persist(new TimeEntry($tenant, $camille->id(), $projects['alpha'], $this->monday(0), self::FULL_DAY));

        $this->em->flush();

        return $validatedIds;
    }

    /**
     * Profil + tarif + affectation de Camille, puis valorisation synchrone des imputations validées.
     *
     * @param list<string> $validatedIds
     */
    private function seedPricingAndValuation(TenantId $tenant, User $camille, array $validatedIds): void
    {
        // Période large couvrant les imputations passées (résolution profil + taux à la date de travail).
        $period = EffectivePeriod::since($this->monday(6));

        $profile = new Profile($tenant, 'Consultant confirmé', CalculationMode::LOADED);
        $this->em->persist($profile);
        // Coût chargé 480 €/j, taux de vente 720 €/j (en centimes, base journalière FULL_DAY).
        $this->em->persist(new ProfileRate($tenant, $profile->id(), $period, 48_000, 72_000));
        $this->em->persist(new ProfileAssignment($tenant, $camille->id(), $profile->id(), $period));
        $this->em->flush();

        if ([] === $validatedIds) {
            return;
        }

        // Valorisation synchrone (pas de dépendance à un worker Messenger pour la démo).
        ($this->valuationHandler)(new TimeEntriesValidated($tenant->toString(), $validatedIds, new DateTimeImmutable('now')));
        $this->em->flush();
    }

    /** Lundi de la semaine située `$weeksAgo` semaines avant la semaine courante. */
    private function monday(int $weeksAgo): DateTimeImmutable
    {
        return new DateTimeImmutable('today')->modify('monday this week')->modify(sprintf('-%d weeks', $weeksAgo));
    }
}
