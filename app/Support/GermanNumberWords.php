<?php

namespace App\Support;

/**
 * Issue #19: Beträge in deutschen Zahlwörtern („dreiundsechzig Euro“) für die Teamer-Abrechnung.
 * Ganze Zahlen von 0 bis 999 999.
 */
final class GermanNumberWords
{
    private const UNITS = ['', 'eins', 'zwei', 'drei', 'vier', 'fünf', 'sechs', 'sieben', 'acht', 'neun'];

    private const TEENS = ['zehn', 'elf', 'zwölf', 'dreizehn', 'vierzehn', 'fünfzehn', 'sechzehn', 'siebzehn', 'achtzehn', 'neunzehn'];

    private const TENS = ['', '', 'zwanzig', 'dreißig', 'vierzig', 'fünfzig', 'sechzig', 'siebzig', 'achtzig', 'neunzig'];

    public static function number(int $n): string
    {
        if ($n < 0 || $n > 999999) {
            throw new \InvalidArgumentException('Zahl außerhalb von 0 bis 999 999.');
        }
        if ($n === 0) {
            return 'null';
        }

        $thousands = intdiv($n, 1000);
        $rest = $n % 1000;

        $words = '';
        if ($thousands > 0) {
            // „eins“ wird vor „tausend“ zu „ein“ (eintausend, hunderteintausend)
            $words .= preg_replace('/eins$/', 'ein', self::belowThousand($thousands)).'tausend';
        }

        return $words.self::belowThousand($rest);
    }

    /** Euro-Betrag, z. B. 63.5 → „dreiundsechzig Euro und fünfzig Cent“. */
    public static function euro(float|string $amount): string
    {
        $cents = (int) round(((float) $amount) * 100);
        $euros = intdiv($cents, 100);
        $rest = $cents % 100;

        // Vor einer Einheit wird „eins“ zu „ein“ (ein Euro, einhundertein Euro, ein Cent).
        $words = self::beforeUnit(self::number($euros)).' Euro';

        return $rest > 0 ? $words.' und '.self::beforeUnit(self::number($rest)).' Cent' : $words;
    }

    private static function beforeUnit(string $words): string
    {
        return preg_replace('/eins$/', 'ein', $words) ?? $words;
    }

    private static function belowThousand(int $n): string
    {
        $hundreds = intdiv($n, 100);
        $rest = $n % 100;
        $words = $hundreds > 0 ? ($hundreds === 1 ? 'ein' : self::UNITS[$hundreds]).'hundert' : '';

        return $words.self::belowHundred($rest);
    }

    private static function belowHundred(int $n): string
    {
        if ($n < 10) {
            return self::UNITS[$n];
        }
        if ($n < 20) {
            return self::TEENS[$n - 10];
        }

        $unit = $n % 10;
        $tens = self::TENS[intdiv($n, 10)];

        return $unit > 0 ? ($unit === 1 ? 'ein' : self::UNITS[$unit]).'und'.$tens : $tens;
    }
}
