<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * database/fix_database.sql is the one file a student imports in phpMyAdmin to repair a
 * half-finished import. It is fix_keys.sql and fix_views.sql joined together, so this
 * guards against the three drifting apart: a statement added to one of the parts but
 * forgotten in the joined file would quietly stop being applied.
 *
 * @internal
 */
final class FixDatabaseSqlTest extends CIUnitTestCase
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
        $whole = self::statements('fix_database.sql');

        foreach (['fix_keys.sql', 'fix_views.sql'] as $part) {
            foreach (self::statements($part) as $statement) {
                $this->assertContains(
                    $statement,
                    $whole,
                    "database/fix_database.sql is missing a statement of {$part}. Join the two parts again.",
                );
            }
        }
    }

    public function testTheJoinedFileAddsNothingOfItsOwn(): void
    {
        $parts = array_merge(self::statements('fix_keys.sql'), self::statements('fix_views.sql'));

        foreach (self::statements('fix_database.sql') as $statement) {
            $this->assertContains($statement, $parts, 'database/fix_database.sql holds a statement neither part has.');
        }
    }

    public function testItRepairsBothTheKeysAndTheViews(): void
    {
        $sql = (string) file_get_contents(self::DIR . 'fix_database.sql');

        $this->assertStringContainsString('AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('CREATE VIEW pet_health_timeline', $sql);
        $this->assertStringContainsString('CREATE VIEW medication_adherence', $sql);
        $this->assertStringContainsString('points_at_id_0', $sql); // the report must come last
        $this->assertStringNotContainsString('DROP TABLE IF EXISTS `users`', $sql); // never the data
    }
}
