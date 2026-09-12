<?php

declare(strict_types=1);

namespace App\Tests\Functional\A11y;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceType;
use App\Domain\Authorization\Role;
use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\Holiday;
use App\Domain\Calendar\WorkSchedule;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Project\Project;
use App\Domain\Reminder\ReminderPreference;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use DateTimeImmutable;

/**
 * US-093 — Export du HTML rendu (serveur) des écrans du parcours P1 pour le scan d'accessibilité
 * WCAG 2.2 AA en CI (approche B : scan de fichiers statiques par @axe-core/cli, sans serveur HTTP).
 *
 * Le test rend chaque route via le client in-process (loginUser, aucune requête HTTP réelle → ni CSRF
 * ni rate limit) et écrit le HTML dans var/a11y/*.html, ensuite scanné par le job CI « a11y ».
 * Il vaut aussi test de fumée : chaque route du parcours doit répondre 200.
 */
final class A11yHtmlExportTest extends WebTestCase
{
    /** @var array<string, string> route => slug de fichier */
    private const array ROUTES = [
        '/' => 'dashboard',
        '/saisie' => 'saisie-semaine',
        '/saisie/jour' => 'saisie-jour',
        '/absences' => 'absences',
        '/completude' => 'completude',
    ];

    public function testExportParcoursHtmlForA11yScan(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $schema = array_map(
            static fn (string $class) => $em->getClassMetadata($class),
            [
                Tenant::class, User::class, Role::class,
                Project::class, TimeEntry::class,
                AbsenceRequest::class, AbsenceType::class,
                Holiday::class, ClosurePeriod::class, WorkSchedule::class,
                ReminderPreference::class, AccountingPeriod::class,
            ],
        );
        $tool = new SchemaTool($em);
        $tool->dropSchema($schema);
        $tool->createSchema($schema);

        $tenant = TenantId::generate();
        // Collaborateur (aucun rôle → non habilité CREATE_PROJECT) : « / » rend le tableau de bord (DSH-COLLAB).
        $user = new User($tenant, 'camille@agence.test', new SodiumPasswordHasher()->hash('x'));
        $project = new Project($tenant, 'PRJ-1', 'Refonte SI');
        $em->persist(new Tenant($tenant, 'Agence A'));
        $em->persist($user);
        $em->persist($project);
        $em->persist(new TimeEntry($tenant, $user->id(), $project->id(), new DateTimeImmutable('monday this week'), 210));
        $em->flush();

        $client->loginUser($user);

        $dir = self::getContainer()->getParameter('kernel.project_dir').'/var/a11y';
        if (!is_dir($dir) && !mkdir($dir, 0o777, true) && !is_dir($dir)) {
            self::fail("Impossible de créer le répertoire d'export a11y : {$dir}");
        }

        foreach (self::ROUTES as $path => $slug) {
            $client->request('GET', $path);
            self::assertResponseIsSuccessful(sprintf('La route %s doit répondre 200 (parcours P1).', $path));
            file_put_contents($dir.'/'.$slug.'.html', (string) $client->getResponse()->getContent());
        }

        // Les 5 fichiers doivent exister pour le job de scan.
        foreach (self::ROUTES as $slug) {
            self::assertFileExists($dir.'/'.$slug.'.html');
        }

        $tool->dropSchema($schema);
    }
}
