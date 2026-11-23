<?php

namespace Tests\Unit;

use App\Support\Iban;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Issue #19: IBAN-Prüfung (mod 97) ohne zusätzliches Paket. */
class IbanTest extends TestCase
{
    public static function valid(): array
    {
        return [
            'DE mit Leerzeichen' => ['DE89 3704 0044 0532 0130 00'],
            'DE kompakt, klein' => ['de89370400440532013000'],
            'AT' => ['AT611904300234573201'],
            'NL' => ['NL91ABNA0417164300'],
        ];
    }

    public static function invalid(): array
    {
        return [
            'Prüfziffer falsch' => ['DE89 3704 0044 0532 0130 01'],
            'DE zu kurz' => ['DE89 3704 0044 0532 0130 0'],
            'Unsinn' => ['Hallo Welt'],
            'leer' => [''],
            'null' => [null],
        ];
    }

    #[DataProvider('valid')]
    public function test_valid_ibans(string $iban): void
    {
        $this->assertTrue(Iban::isValid($iban));
    }

    #[DataProvider('invalid')]
    public function test_invalid_ibans(?string $iban): void
    {
        $this->assertFalse(Iban::isValid($iban));
    }

    public function test_normalize_and_format(): void
    {
        $this->assertSame('DE89370400440532013000', Iban::normalize(' de89 3704 0044 0532 0130 00 '));
        $this->assertSame('DE89 3704 0044 0532 0130 00', Iban::format('DE89370400440532013000'));
    }
}
