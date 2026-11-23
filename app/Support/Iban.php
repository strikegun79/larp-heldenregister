<?php

namespace App\Support;

/**
 * Issue #19: IBAN normalisieren, prüfen (ISO 13616, Prüfsumme mod 97) und lesbar formatieren.
 */
final class Iban
{
    /** Leerzeichen entfernen, Großbuchstaben. */
    public static function normalize(?string $iban): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $iban) ?? '');
    }

    public static function isValid(?string $iban): bool
    {
        $iban = self::normalize($iban);

        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban)) {
            return false;
        }

        // Deutsche IBAN: immer 22 Zeichen.
        if (str_starts_with($iban, 'DE') && strlen($iban) !== 22) {
            return false;
        }

        // Ländercode + Prüfziffer ans Ende, Buchstaben → Zahlen (A=10 … Z=35), dann mod 97 == 1.
        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
        }

        $remainder = 0;
        foreach (str_split($numeric, 7) as $chunk) {
            $remainder = (int) ($remainder.$chunk) % 97;
        }

        return $remainder === 1;
    }

    /** In Vierergruppen, z. B. „DE89 3704 0044 0532 0130 00“. */
    public static function format(?string $iban): string
    {
        return trim(chunk_split(self::normalize($iban), 4, ' '));
    }
}
