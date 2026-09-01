<?php

declare(strict_types=1);

namespace App\Tests\Functional\Cli;

use App\Application\Reminder\Message\SendDueReminders;
use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * US-056 (T-056-03) — le cron publie un ordre de relance asynchrone par tenant, via le câblage réel
 * (registre Doctrine + bus). Il ne calcule rien lui-même : le worker s'en charge sous contexte tenant.
 */
final class RunRemindersCommandTest extends KernelTestCase
{
    private KernelInterface $bootedKernel;
    private EntityManagerInterface $em;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        $this->bootedKernel = self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->schema = [$this->em->getClassMetadata(Tenant::class)];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);
        $this->transport()->reset();
    }

    protected function tearDown(): void
    {
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->em->close();
        parent::tearDown();
    }

    public function testDispatchesForASingleTenant(): void
    {
        $tenant = TenantId::generate();

        $exitCode = $this->executeCommand(['tenant' => $tenant->toString()]);

        self::assertSame(0, $exitCode);
        $sent = $this->transport()->getSent();
        self::assertCount(1, $sent);
        $message = $sent[0]->getMessage();
        self::assertInstanceOf(SendDueReminders::class, $message);
        self::assertTrue($message->tenantId()->equals($tenant));
    }

    public function testDispatchesForAllRegisteredTenantsWhenNoArgument(): void
    {
        $this->em->persist(new Tenant(TenantId::generate(), 'Alpha'));
        $this->em->persist(new Tenant(TenantId::generate(), 'Bravo'));
        $this->em->flush();
        $this->em->clear();

        $exitCode = $this->executeCommand([]);

        self::assertSame(0, $exitCode);
        self::assertCount(2, $this->transport()->getSent());
    }

    public function testRejectsInvalidTenant(): void
    {
        $exitCode = $this->executeCommand(['tenant' => 'pas-un-uuid']);

        self::assertSame(2, $exitCode, 'Un identifiant invalide doit renvoyer le code INVALID.');
        self::assertCount(0, $this->transport()->getSent());
    }

    /**
     * @param array<string, string> $input
     */
    private function executeCommand(array $input): int
    {
        $tester = new CommandTester(
            new Application($this->bootedKernel)->find('app:reminders:run'),
        );

        return $tester->execute($input);
    }

    private function transport(): InMemoryTransport
    {
        $transport = self::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);

        return $transport;
    }
}
