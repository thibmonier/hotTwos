<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet;

use App\Domain\Project\Project;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use DateTimeImmutable;

/**
 * US-055 — l'écran de validation présente les imputations en attente sur les projets dont
 * l'utilisateur est responsable, et exige une authentification.
 */
final class ValidationPageTest extends WebTestCase
{
    public function testPageShowsPendingEntriesOfOwnProjects(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $schema = [
            $em->getClassMetadata(Tenant::class),
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(Project::class),
            $em->getClassMetadata(TimeEntry::class),
            $em->getClassMetadata(AccountingPeriod::class),
        ];
        $tool = new SchemaTool($em);
        $tool->dropSchema($schema);
        $tool->createSchema($schema);

        $tenant = TenantId::generate();
        $chef = new User($tenant, 'marc@agence.test', new SodiumPasswordHasher()->hash('x'), ['Chef de projet']);
        $project = new Project($tenant, 'PRJ-1', 'Ma responsabilité', true, $chef->id());
        $em->persist(new Tenant($tenant, 'Agence A'));
        $em->persist($chef);
        $em->persist($project);
        $em->persist(new TimeEntry($tenant, TenantId::generate()->toString(), $project->id(), new DateTimeImmutable('2026-09-15'), 240));
        $em->flush();

        $client->loginUser($chef);
        $client->request('GET', '/validation');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Ma responsabilité', (string) $client->getResponse()->getContent());

        $tool->dropSchema($schema);
        $em->close();
    }

    public function testPageRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request('GET', '/validation');

        self::assertResponseStatusCodeSame(401);
    }
}
