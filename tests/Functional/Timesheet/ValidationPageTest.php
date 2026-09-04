<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Authorization\Role;
use App\Domain\Project\Project;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\User\User;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
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
            $em->getClassMetadata(Role::class),
            $em->getClassMetadata(Project::class),
            $em->getClassMetadata(TimeEntry::class),
            $em->getClassMetadata(AccountingPeriod::class),
            $em->getClassMetadata(AbsenceRequest::class),
        ];
        $tool = new SchemaTool($em);
        $tool->dropSchema($schema);
        $tool->createSchema($schema);

        $tenant = TenantId::generate();
        // La page /validation exige VALIDATE_TIME : les rôles doivent être résolubles (habilitation réelle).
        new InitializeDefaultRoles(new DoctrineRoleRepository($em))->forTenant($tenant);
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

    /**
     * US-069 (T-069-05) — si un projet a disparu (course rare), la ligne affiche un libellé de repli
     * lisible plutôt que l'identifiant technique brut. Cas forcé via un stub du dépôt d'imputations.
     */
    public function testMissingProjectShowsFallbackLabelInsteadOfRawId(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $schema = [
            $em->getClassMetadata(Tenant::class),
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(Role::class),
            $em->getClassMetadata(Project::class),
            $em->getClassMetadata(TimeEntry::class),
            $em->getClassMetadata(AccountingPeriod::class),
            $em->getClassMetadata(AbsenceRequest::class),
        ];
        $tool = new SchemaTool($em);
        $tool->dropSchema($schema);
        $tool->createSchema($schema);

        $tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($em))->forTenant($tenant);
        $chef = new User($tenant, 'marc@agence.test', new SodiumPasswordHasher()->hash('x'), ['Chef de projet']);
        // Le chef a bien un projet (hasProjects vrai) ; l'imputation pointe vers un AUTRE projet, disparu.
        $project = new Project($tenant, 'PRJ-1', 'Ma responsabilité', true, $chef->id());
        $em->persist(new Tenant($tenant, 'Agence A'));
        $em->persist($chef);
        $em->persist($project);
        $em->flush();

        // Le dépôt renvoie une imputation dont le projet n'est pas résolu (supprimé entre-temps).
        $ghostProjectId = 'projet-fantome-non-resolu';
        $entries = self::createStub(TimeEntryRepository::class);
        $entries->method('findPendingForProjects')->willReturn([
            new TimeEntry($tenant, $chef->id(), $ghostProjectId, new DateTimeImmutable('2026-09-15'), 240),
        ]);
        self::getContainer()->set(TimeEntryRepository::class, $entries);

        $client->loginUser($chef);
        $client->request('GET', '/validation');

        self::assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Projet indisponible', $body);
        self::assertStringNotContainsString($ghostProjectId, $body);
        // US-070 (T-070-02) : durée affichée en heures (240 min = 4h00), pas en minutes brutes.
        self::assertStringContainsString('4h00', $body);
        self::assertStringNotContainsString('>240<', $body);

        $tool->dropSchema($schema);
        $em->close();
    }

    /**
     * US-069 (T-069-01) — sur /validation, un SEUL item de navigation est actif (Validation) : l'item
     * Saisie ne capture plus la route timesheet_validation via un préfixe trop large.
     */
    public function testOnlyOneNavItemActiveOnValidationPage(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $schema = [
            $em->getClassMetadata(Tenant::class),
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(Role::class),
            $em->getClassMetadata(Project::class),
            $em->getClassMetadata(TimeEntry::class),
            $em->getClassMetadata(AccountingPeriod::class),
            $em->getClassMetadata(AbsenceRequest::class),
        ];
        $tool = new SchemaTool($em);
        $tool->dropSchema($schema);
        $tool->createSchema($schema);

        $tenant = TenantId::generate();
        new InitializeDefaultRoles(new DoctrineRoleRepository($em))->forTenant($tenant);
        $chef = new User($tenant, 'marc@agence.test', new SodiumPasswordHasher()->hash('x'), ['Chef de projet']);
        $em->persist(new Tenant($tenant, 'Agence A'));
        $em->persist($chef);
        $em->flush();

        $client->loginUser($chef);
        $client->request('GET', '/validation');

        self::assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertSame(1, substr_count($body, 'aria-current="page"'), 'Un seul item de nav doit être actif sur /validation');

        $tool->dropSchema($schema);
        $em->close();
    }

    public function testPageRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request('GET', '/validation');

        // US-068 : route web → redirection vers la page de connexion (plus de 401 web).
        self::assertResponseRedirects('/login');
    }
}
