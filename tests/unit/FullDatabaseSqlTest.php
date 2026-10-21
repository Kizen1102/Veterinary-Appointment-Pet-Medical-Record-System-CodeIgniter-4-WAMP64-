<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * database/pawrecord_full.sql is the one file to import for a complete, correct database:
 * pawrecord_schema.sql and pawrecord_seed.sql joined together. This guards against the three
 * drifting apart — a table or a row added to one of the parts but forgotten in the joined
 * file would leave anyone who imports it with a database missing that piece.
 *
 * @internal
 */
final class FullDatabaseSqlTest extends CIUnitTestCase
{
    private const DIR = ROOTPATH . 'database' . DIRECTORY_SEPARATOR;

    /** The statements of one .sql file, without its comments and blank lines. */
    private static function statements(string $file): array
    {
        $lines = array_filter(
            explode("\n", (string) file_get_contents(self::DIR . $file)),
            static fn (string $line) => $line !== '' && ! str_starts_with(ltrim($line), '--'),
        );

        $parts = array_map('trim', explode(';', implode("\n", $lines)));

        return array_values(array_filter($parts, static fn (string $part) => $part !== ''));
    }

    public function testTheJoinedFileHoldsEveryStatementOfBothParts(): void
    {
        $whole = self::statements('pawrecord_full.sql');

        foreach (['pawrecord_schema.sql', 'pawrecord_seed.sql'] as $part) {
            foreach (self::statements($part) as $statement) {
                $this->assertContains(
                    $statement,
                    $whole,
                    "database/pawrecord_full.sql is missing a statement of {$part}. Join the two parts again.",
                );
            }
        }
    }

    public function testTheJoinedFileAddsNothingOfItsOwn(): void
    {
        $parts = array_merge(self::statements('pawrecord_schema.sql'), self::statements('pawrecord_seed.sql'));

        foreach (self::statements('pawrecord_full.sql') as $statement) {
            $this->assertContains($statement, $parts, 'database/pawrecord_full.sql holds a statement neither part has.');
        }
    }

    public function testItBuildsTheTablesAndBothViewsAndSaysItReplacesEverything(): void
    {
        $sql = (string) file_get_contents(self::DIR . 'pawrecord_full.sql');

        $this->assertStringContainsString('CREATE DATABASE IF NOT EXISTS pawrecord_db', $sql);
        $this->assertStringContainsString('CREATE VIEW pet_health_timeline', $sql);
        $this->assertStringContainsString('CREATE VIEW medication_adherence', $sql);
        $this->assertStringContainsString('AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('password123', $sql);          // the demo logins are named
        $this->assertStringContainsString('REPLACES everything', $sql);  // and the warning is kept
        $this->assertStringContainsString('fix_database.sql', $sql);     // with the way that keeps data
    }
}
