<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Schutz vor Datenverlust: Bevor RefreshDatabase & Co. greifen, wird geprüft,
     * dass die Tests gegen eine eigene Testdatenbank (Name endet auf "_test") laufen.
     * So können Tests niemals die Entwicklungs- oder Produktionsdatenbank leeren.
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database   = (string) config("database.connections.{$connection}.database");

        $isSqliteMemory = $database === ':memory:';

        if (! $isSqliteMemory && ! str_ends_with($database, '_test')) {
            throw new RuntimeException(
                "Abbruch: Tests würden die Datenbank „{$database}“ leeren. "
                .'Tests dürfen nur gegen eine Datenbank laufen, deren Name auf "_test" endet '
                .'(siehe DB_DATABASE in phpunit.xml).'
            );
        }

        return parent::setUpTraits();
    }
}
