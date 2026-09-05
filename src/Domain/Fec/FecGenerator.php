<?php

declare(strict_types=1);

namespace App\Domain\Fec;

use App\Domain\Margin\ProjectMargin;
use App\Domain\Shared\CalendarMonth;

/**
 * Génère un fichier FEC (US-074, ADR-0021) à partir des marges figées d'une période.
 *
 * Pour chaque projet : une écriture de **produit** (débit tiers / crédit produit = CA reconnu) et, si
 * coût > 0, une écriture de **charge** (débit charge / crédit contrepartie = coût valorisé). Chaque
 * écriture est équilibrée ; le total débit du fichier = total crédit (INV-2, centimes entiers en
 * interne, format décimal FEC en sortie). Moteur unique — aucun recalcul de marge.
 */
final class FecGenerator
{
    /** Les 18 champs FEC, dans l'ordre normé. */
    private const array HEADER = [
        'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate', 'CompteNum', 'CompteLib',
        'CompAuxNum', 'CompAuxLib', 'PieceRef', 'PieceDate', 'EcritureLib', 'Debit', 'Credit',
        'EcritureLet', 'DateLet', 'ValidDate', 'Montantdevise', 'Idevise',
    ];

    /**
     * @param list<ProjectMargin> $margins
     *
     * @return list<FecLine>
     */
    public function lines(FecConfiguration $config, string $period, array $margins): array
    {
        $date = $this->lastDayOf($period);
        $lines = [];
        $ecritureNum = 0;

        foreach ($margins as $margin) {
            if ($margin->revenueCents() > 0) {
                ++$ecritureNum;
                $lib = sprintf('CA reconnu %s %s', $margin->projectName(), $period);
                $piece = sprintf('CA-%s-%s', $period, $margin->projectRef());
                $amount = $this->amount($margin->revenueCents());
                $lines[] = $this->line($config, (string) $ecritureNum, $date, $config->receivableAccountNum(), $config->receivableAccountLib(), $margin, $piece, $lib, $amount, '');
                $lines[] = $this->line($config, (string) $ecritureNum, $date, $config->revenueAccountNum(), $config->revenueAccountLib(), $margin, $piece, $lib, '', $amount);
            }

            if ($margin->costCents() > 0) {
                ++$ecritureNum;
                $lib = sprintf('Coût valorisé %s %s', $margin->projectName(), $period);
                $piece = sprintf('COUT-%s-%s', $period, $margin->projectRef());
                $amount = $this->amount($margin->costCents());
                $lines[] = $this->line($config, (string) $ecritureNum, $date, $config->costAccountNum(), $config->costAccountLib(), $margin, $piece, $lib, $amount, '');
                $lines[] = $this->line($config, (string) $ecritureNum, $date, $config->costCounterpartAccountNum(), $config->costCounterpartAccountLib(), $margin, $piece, $lib, '', $amount);
            }
        }

        return $lines;
    }

    /**
     * Contenu complet du fichier FEC (en-tête + lignes, séparateur tabulation, une ligne par écriture).
     *
     * @param list<FecLine> $lines
     */
    public function render(array $lines): string
    {
        $rows = [implode("\t", self::HEADER)];
        foreach ($lines as $line) {
            $rows[] = implode("\t", $line->toArray());
        }

        return implode("\n", $rows)."\n";
    }

    /**
     * Nom de fichier normé : <SIREN>FEC<AAAAMMJJ>.txt (JJ = dernier jour de la période).
     */
    public function fileName(FecConfiguration $config, string $period): string
    {
        return sprintf('%sFEC%s.txt', $config->siren(), $this->lastDayOf($period));
    }

    private function line(FecConfiguration $config, string $num, string $date, string $compteNum, string $compteLib, ProjectMargin $margin, string $piece, string $lib, string $debit, string $credit): FecLine
    {
        return new FecLine(
            $config->journalCode(),
            $config->journalLib(),
            $num,
            $date,
            $compteNum,
            $compteLib,
            $margin->projectRef(),
            $margin->projectName(),
            $piece,
            $date,
            $lib,
            $debit,
            $credit,
            validDate: $date,
        );
    }

    private function amount(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '');
    }

    private function lastDayOf(string $period): string
    {
        [, $to] = CalendarMonth::bounds($period);

        return $to->modify('-1 day')->format('Ymd');
    }
}
