<?php

declare(strict_types=1);

namespace App\Tests\Functional\Cli;

use App\Domain\Authorization\Role;
use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\WorkSchedule;
use App\Domain\Calendar\Holiday;
use App\Domain\Client\Client;
use App\Domain\Currency\ReferenceCurrency;
use App\Domain\Pricing\Profile;
use App\Domain\Skill\SkillLevelScale;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * US-019 (EF-REF-29, CA-2/CA-4/CA-5) — la commande tenant:init provisionne les défauts (idempotente)
 * et refuse un tenant inexistant/identifiant invalide.
 */
final class InitializeTenantCommandTest extends KernelTestCase
{
    private KernelInterface $bootedKernel;
    private EntityManagerInterface $em;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        $this->bootedKernel = self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->schema = [
            $this->em->getClassMetadata(Tenant::class),
            $this->em->getClassMetadata(Role::class),
            $this->em->getClassMetadata(ReferenceCurrency::class),
            $this->em->getClassMetadata(SkillLevelScale::class),
            $this->em->getClassMetadata(Profile::class),
            $this->em->getClassMetadata(Client::class),
            $this->em->getClassMetadata(Holiday::class),
            $this->em->getClassMetadata(ClosurePeriod::class),
            $this->em->getClassMetadata(WorkSchedule::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testProvisionsDefaultsIdempotently(): void
    {
        $tenant = TenantId::generate();
        $this->em->persist(new Tenant($tenant, 'Nouveau tenant'));
        $this->em->flush();

        $tester = $this->tester();
        self::assertSame(0, $tester->execute(['tenantId' => $tenant->toString()]));

        // Provisioning attendu.
        self::assertSame(1, $this->countEntities(ReferenceCurrency::class));
        self::assertSame(1, $this->countEntities(SkillLevelScale::class));
        self::assertSame(1, $this->countEntities(Profile::class));
        self::assertSame(1, $this->countEntities(Client::class));
        self::assertSame(11, $this->countEntities(Holiday::class)); // 8 fixes + 3 mobiles (US-023)

        // Rejeu : aucun doublon (idempotence, CA-4).
        self::assertSame(0, $this->tester()->execute(['tenantId' => $tenant->toString()]));
        self::assertSame(1, $this->countEntities(ReferenceCurrency::class));
        self::assertSame(1, $this->countEntities(Profile::class));
        self::assertSame(1, $this->countEntities(Client::class));
        self::assertSame(11, $this->countEntities(Holiday::class)); // 8 fixes + 3 mobiles (US-023)
    }

    public function testFailsForUnknownTenant(): void
    {
        self::assertSame(1, $this->tester()->execute(['tenantId' => TenantId::generate()->toString()]));
    }

    public function testRejectsInvalidTenantId(): void
    {
        self::assertSame(2, $this->tester()->execute(['tenantId' => 'pas-un-uuid']));
    }

    private function tester(): CommandTester
    {
        return new CommandTester(new Application($this->bootedKernel)->find('tenant:init'));
    }

    /**
     * @param class-string $class
     */
    private function countEntities(string $class): int
    {
        return (int) $this->em->createQuery('SELECT COUNT(e.id) FROM '.$class.' e')->getSingleScalarResult();
    }
}
