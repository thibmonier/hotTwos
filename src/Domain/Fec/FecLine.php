<?php

declare(strict_types=1);

namespace App\Domain\Fec;

/**
 * Une ligne d'écriture FEC — les 18 champs obligatoires (art. A47 A-1 du LPF), dans l'ordre normé.
 *
 * Champs non applicables = chaîne vide (jamais "N/A"). Debit XOR Credit non nul. Montants formatés
 * en décimal FEC (séparateur virgule, 2 décimales, sans séparateur de milliers).
 */
final readonly class FecLine
{
    public function __construct(
        public string $journalCode,
        public string $journalLib,
        public string $ecritureNum,
        public string $ecritureDate,
        public string $compteNum,
        public string $compteLib,
        public string $compAuxNum,
        public string $compAuxLib,
        public string $pieceRef,
        public string $pieceDate,
        public string $ecritureLib,
        public string $debit,
        public string $credit,
        public string $ecritureLet = '',
        public string $dateLet = '',
        public string $validDate = '',
        public string $montantDevise = '',
        public string $idevise = '',
    ) {
    }

    /**
     * @return list<string> les 18 champs dans l'ordre FEC
     */
    public function toArray(): array
    {
        return [
            $this->journalCode, $this->journalLib, $this->ecritureNum, $this->ecritureDate,
            $this->compteNum, $this->compteLib, $this->compAuxNum, $this->compAuxLib,
            $this->pieceRef, $this->pieceDate, $this->ecritureLib, $this->debit, $this->credit,
            $this->ecritureLet, $this->dateLet, $this->validDate, $this->montantDevise, $this->idevise,
        ];
    }
}
