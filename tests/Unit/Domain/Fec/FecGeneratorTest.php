<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Fec;

use App\Domain\Fec\FecConfiguration;
use App\Domain\Fec\FecGenerator;
use App\Domain\Margin\ProjectMargin;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-074 (T-074-04/07, CA-1/CA-2) — génération FEC : 18 champs normés, écritures équilibrées
 * (débit=crédit), format des montants/dates, nommage du fichier.
 */
final class FecGeneratorTest extends TestCase
{
    private const string PROJECT = '018f9c4e-0000-7000-8000-00000000aaaa';

    private TenantId $tenant;
    private FecConfiguration $config;
    private FecGenerator $generator;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->config = new FecConfiguration(
            $this->tenant,
            '123456789',
            'VT',
            'Ventes',
            '706000',
            'Prestations',
            '411000',
            'Clients',
            '641000',
            'Rémunérations',
            '791000',
            'Transferts de charges',
        );
        $this->generator = new FecGenerator();
    }

    public function testHeaderHasTheEighteenFecFieldsInOrder(): void
    {
        $content = $this->generator->render([]);
        $header = explode("\t", explode("\n", $content)[0]);

        self::assertCount(18, $header);
        self::assertSame('JournalCode', $header[0]);
        self::assertSame('EcritureNum', $header[2]);
        self::assertSame('Debit', $header[11]);
        self::assertSame('Credit', $header[12]);
        self::assertSame('Idevise', $header[17]);
    }

    public function testProducesBalancedEntriesPerProject(): void
    {
        $lines = $this->generator->lines($this->config, '2026-11', [$this->margin(10_000_00, 5_800_00)]);

        // 1 écriture produit (2 lignes) + 1 écriture charge (2 lignes) = 4 lignes.
        self::assertCount(4, $lines);

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($lines as $line) {
            self::assertCount(18, $line->toArray());
            $totalDebit += $this->toFloat($line->debit);
            $totalCredit += $this->toFloat($line->credit);
        }
        // Équilibre global : débit = crédit (CA 10 000 + coût 5 800 de chaque côté).
        self::assertSame(15_800.0, $totalDebit);
        self::assertSame(15_800.0, $totalCredit);
    }

    public function testRevenueEntryDebitsReceivableAndCreditsRevenue(): void
    {
        $lines = $this->generator->lines($this->config, '2026-11', [$this->margin(10_000_00, 0)]);

        self::assertCount(2, $lines); // coût = 0 → pas d'écriture de charge
        self::assertSame('411000', $lines[0]->compteNum);   // débit tiers
        self::assertSame('10000,00', $lines[0]->debit);
        self::assertSame('', $lines[0]->credit);
        self::assertSame('706000', $lines[1]->compteNum);   // crédit produit
        self::assertSame('10000,00', $lines[1]->credit);
        self::assertSame('1', $lines[0]->ecritureNum);
        self::assertSame('1', $lines[1]->ecritureNum);
    }

    public function testAmountsUseCommaDecimalAndDatesAreYyyymmdd(): void
    {
        $lines = $this->generator->lines($this->config, '2026-11', [$this->margin(1_234_56, 0)]);

        self::assertSame('1234,56', $lines[0]->debit);
        self::assertSame('20261130', $lines[0]->ecritureDate);
        self::assertSame('20261130', $lines[0]->validDate);
        self::assertSame(self::PROJECT, $lines[0]->compAuxNum);
    }

    public function testFileNameFollowsFecConvention(): void
    {
        self::assertSame('123456789FEC20261130.txt', $this->generator->fileName($this->config, '2026-11'));
    }

    public function testEmptyMarginsProduceHeaderOnly(): void
    {
        $content = $this->generator->render($this->generator->lines($this->config, '2026-11', []));

        self::assertSame(1, substr_count($content, "\n")); // en-tête + newline final uniquement
    }

    private function margin(int $revenue, int $cost): ProjectMargin
    {
        return ProjectMargin::freeze(
            $this->tenant,
            '2026-11',
            self::PROJECT,
            'Site vitrine',
            $revenue,
            $cost,
            10,
            0,
            new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC')),
        );
    }

    private function toFloat(string $fec): float
    {
        return '' === $fec ? 0.0 : (float) str_replace(',', '.', $fec);
    }
}
