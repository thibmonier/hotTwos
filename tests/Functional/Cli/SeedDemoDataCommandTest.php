<?php

declare(strict_types=1);

namespace App\Tests\Functional\Cli;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceType;
use App\Domain\Authorization\Role;
use App\Domain\Project\Project;
use App\Domain\Reminder\ReminderRule;
use App\Domain\Tenant\Tenant;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * T-TECH-04 — la commande de seed crée, via le câblage réel, un tenant de démo cohérent couvrant les
 * modules du sprint (utilisateurs, projets, imputations, absence, règle de relance).
 */
final class SeedDemoDataCommandTest extends KernelTestCase
{
    private KernelInterface $bootedKernel;
    private EntityManagerInterface $em;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        $this->bootedKernel = self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->schema = array_map(
            $this->em->getClassMetadata(...),
            [Tenant::class, User::class, Role::class, Project::class, TimeEntry::class, AbsenceType::class, AbsenceRequest::class, ReminderRule::class],
        );
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testSeedsACoherentDemoTenant(): void
    {
        $tester = new CommandTester(new Application($this->bootedKernel)->find('app:demo:seed'));
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        $this->em->clear();

        self::assertSame(3, $this->entityCount(User::class), 'Trois utilisateurs de démo (collaborateur, chef de projet, admin).');
        self::assertSame(1, $this->entityCount(ReminderRule::class), 'Une règle de relance par tenant.');
        self::assertSame(1, $this->entityCount(AbsenceRequest::class), 'Une absence validée.');
        self::assertGreaterThanOrEqual(8, $this->entityCount(TimeEntry::class), 'Des imputations sur plusieurs semaines.');
        // Projets : Alpha, Beta + le projet système « Absence ».
        self::assertGreaterThanOrEqual(3, $this->entityCount(Project::class));
    }

    /**
     * @param class-string $class
     */
    private function entityCount(string $class): int
    {
        $value = $this->em->createQuery('SELECT COUNT(e.id) FROM '.$class.' e')->getSingleScalarResult();

        return is_numeric($value) ? (int) $value : 0;
    }
}
