<?php

declare(strict_types=1);

namespace App\Tests\Integration\Pricing;

use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRate;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * US-011 (T-011-07 / DBT-SEC-1) — la RLS isole les tables de tarification au runtime, sous le
 * rôle applicatif non-superutilisateur. Sans contexte de tenant, aucune ligne n'est visible
 * (test d'intrusion) : les coûts de revient d'un tenant ne fuient jamais vers un autre.
 */
final class PricingRlsRuntimeTest extends KernelTestCase
{
    private const array RLS_TABLES = ['profile', 'profile_rate'];

    private EntityManagerInterface $em;
    private Connection $connection;

    /** @var list<\Doctrine\ORM\Mapping\ClassMetadata<object>> */
    private array $schema;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();

        $this->schema = [
            $this->em->getClassMetadata(Profile::class),
            $this->em->getClassMetadata(ProfileRate::class),
        ];
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->schema);
        $tool->createSchema($this->schema);

        foreach (self::RLS_TABLES as $table) {
            $this->connection->executeStatement(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
            $this->connection->executeStatement(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
            $this->connection->executeStatement(sprintf('DROP POLICY IF EXISTS tenant_isolation ON %s', $table));
            $this->connection->executeStatement(sprintf(
                "CREATE POLICY tenant_isolation ON %s USING (tenant_id::text = current_setting('app.current_tenant', true))",
                $table,
            ));
        }
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement('RESET ROLE');
        $this->connection->executeStatement('RESET app.current_tenant');
        new SchemaTool($this->em)->dropSchema($this->schema);
        $this->connection->executeStatement('DROP ROLE IF EXISTS pricing_rls_probe');
        $this->em->close();
        parent::tearDown();
    }

    public function testRlsIsolatesPricingTablesAtRuntime(): void
    {
        $tenantA = TenantId::generate();
        $tenantB = TenantId::generate();

        $profileA = new Profile($tenantA, 'A-senior', CalculationMode::LOADED);
        $profileB = new Profile($tenantB, 'B-senior', CalculationMode::LOADED);
        $this->em->persist($profileA);
        $this->em->persist($profileB);
        $this->em->persist(new ProfileRate($tenantA, $profileA->id(), $this->period(), 45000, 78000));
        $this->em->persist(new ProfileRate($tenantB, $profileB->id(), $this->period(), 50000, 90000));
        $this->em->flush();
        $this->em->clear();

        $this->connection->executeStatement('DROP ROLE IF EXISTS pricing_rls_probe');
        $this->connection->executeStatement('CREATE ROLE pricing_rls_probe NOSUPERUSER');
        $this->connection->executeStatement('GRANT SELECT ON profile, profile_rate TO pricing_rls_probe');

        $this->connection->executeStatement(sprintf("SET app.current_tenant = '%s'", $tenantA->toString()));
        $this->connection->executeStatement('SET ROLE pricing_rls_probe');
        $profilesForA = $this->countRows('profile');
        $ratesForA = $this->countRows('profile_rate');

        $this->connection->executeStatement('RESET app.current_tenant');
        $profilesWithoutContext = $this->countRows('profile');
        $ratesWithoutContext = $this->countRows('profile_rate');

        $this->connection->executeStatement('RESET ROLE');

        self::assertSame(1, $profilesForA, 'Seul le profil du tenant A est visible.');
        self::assertSame(1, $ratesForA, 'Seul le tarif du tenant A est visible.');
        self::assertSame(0, $profilesWithoutContext, 'Sans contexte, la RLS masque les profils.');
        self::assertSame(0, $ratesWithoutContext, 'Sans contexte, la RLS masque les tarifs.');
    }

    private function countRows(string $table): int
    {
        $value = $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));
        if (!is_numeric($value)) {
            self::fail('Le COUNT n\'a pas renvoyé de valeur numérique.');
        }

        return (int) $value;
    }

    private function period(): EffectivePeriod
    {
        return EffectivePeriod::since(new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC')));
    }
}
