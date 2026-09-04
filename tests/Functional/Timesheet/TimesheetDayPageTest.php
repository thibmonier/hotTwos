<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet;

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
 * US-052 — la vue de saisie quotidienne mobile présente les projets du jour et les imputations
 * existantes, avec une structure mobile-first accessible (cibles tactiles, clavier numérique,
 * alternative au swipe), en réutilisant l'API de saisie US-050. Authentification requise.
 */
final class TimesheetDayPageTest extends WebTestCase
{
    public function testDayViewListsProjectsAndExistingEntry(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $schema = [
            $em->getClassMetadata(Tenant::class),
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(Project::class),
            $em->getClassMetadata(TimeEntry::class),
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
        $em->persist(new TimeEntry($tenant, $user->id(), $project->id(), new DateTimeImmutable('2026-09-01'), 210));
        $em->flush();

        $client->loginUser($user);
        $client->request('GET', '/saisie/jour/2026-09-01');

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent() ?: '';
        self::assertStringContainsString('Refonte SI', $body);
        self::assertStringContainsString('data-controller="timesheet-day"', $body);
        self::assertStringContainsString('inputmode="decimal"', $body);
        self::assertStringContainsString('Enregistrer la journée', $body);
        self::assertStringContainsString('name="viewport"', $body);
        // Alternative accessible au swipe : flèches jour précédent/suivant.
        self::assertStringContainsString('/saisie/jour/2026-08-31', $body);
        // Saisie en heures décimales : 210 min = 3,5 h.
        self::assertStringContainsString('value="3.5"', $body);

        $tool->dropSchema($schema);
        $em->close();
    }

    public function testDayViewRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request('GET', '/saisie/jour/2026-09-01');

        // US-068 : route web → redirection vers la page de connexion (plus de 401 web).
        self::assertResponseRedirects('/login');
    }
}
