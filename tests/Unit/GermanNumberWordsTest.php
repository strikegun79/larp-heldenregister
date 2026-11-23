<?php

namespace Tests\Unit;

use App\Support\GermanNumberWords;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Issue #19: Betrag in Worten für die Teamer-Abrechnung. */
class GermanNumberWordsTest extends TestCase
{
    public static function numbers(): array
    {
        return [
            [0, 'null'], [1, 'eins'], [11, 'elf'], [17, 'siebzehn'], [20, 'zwanzig'], [21, 'einundzwanzig'],
            [35, 'fünfunddreißig'], [63, 'dreiundsechzig'], [100, 'einhundert'], [101, 'einhunderteins'],
            [126, 'einhundertsechsundzwanzig'], [999, 'neunhundertneunundneunzig'], [1000, 'eintausend'],
            [1001, 'eintausendeins'], [3000, 'dreitausend'], [21000, 'einundzwanzigtausend'],
            [101000, 'einhunderteintausend'],
        ];
    }

    #[DataProvider('numbers')]
    public function test_number(int $n, string $words): void
    {
        $this->assertSame($words, GermanNumberWords::number($n));
    }

    public function test_euro(): void
    {
        $this->assertSame('dreiundsechzig Euro', GermanNumberWords::euro('63.00'));
        $this->assertSame('fünfunddreißig Euro und fünfzig Cent', GermanNumberWords::euro(35.5));
        $this->assertSame('ein Euro', GermanNumberWords::euro(1));
        $this->assertSame('einhundertein Euro und ein Cent', GermanNumberWords::euro('101.01'));
        $this->assertSame('einhundertsechsundzwanzig Euro', GermanNumberWords::euro('126.00'));
    }
}
