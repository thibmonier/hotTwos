<?php

declare(strict_types=1);

namespace App\Tests\Functional\Home;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Authorization\Role;
use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\Holiday;
use App\Domain\Calendar\WorkSchedule;
use App\Domain\Project\Project;
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
 * US-088 (DSH-COLLAB) — un collaborateur (non habilité à créer des projets) arrive sur son
 * tableau de bord de contrepartie ; l'accès anonyme garde la home historique.
 */
final class CollaboratorDashboardTest extends WebTestCase
{
    public function testCollaboratorSeesDashboardOnHome(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $schema = [
            $em->getClassMetadata(Tenant::class),
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(Role::class),
            $em->getClassMetadata(Project::class),
            $em->getClassMetadata(TimeEntry::class),
            $em->getClassMetadata(AbsenceRequest::class),
            $em->getClassMetadata(Holiday::class),
            $em->getClassMetadata(ClosurePeriod::class),
            $em->getClassMetadata(WorkSchedule::class),
        ];
        $tool = new SchemaTool($em);
        $tool->dropSchema($schema);
        $tool->createSchema($schema);

        $tenant = TenantId::generate();
        $user = new User($tenant, 'camille@agence.test', new SodiumPasswordHasher()->hash('x'));
        $project = new Project($tenant, 'PRJ-1', 'Refonte SI');
        $em->persist(new Tenant($tenant, 'Agence A'));
        $em->persist($user);
        $em->persist($project);
        $em->persist(new TimeEntry($tenant, $user->id(), $project->id(), new DateTimeImmutable('monday this week'), 210));
        $em->flush();

        $client->loginUser($user);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        // Tableau de bord (et non la home historique) : en-tête personnalisé par e-mail (F-S5-4 / F1).
        self::assertSelectorTextContains('h1', 'Bonjour');
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('camille@agence.test', $body);
        self::assertStringContainsString('Heures cette semaine', $body);
        self::assertStringContainsString('Saisir mes temps', $body);
        // La home "socle applicatif" ne doit pas être servie au collaborateur.
        self::assertStringNotContainsString('Socle applicatif', $body);
    }

    public function testAnonymousStillSeesHistoricalHome(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'HotOnes');
    }
}
