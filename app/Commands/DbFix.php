<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

/**
 * Repairs a database left half-imported by phpMyAdmin, in one command:
 *
 *   php spark db:fix
 *
 * phpMyAdmin's Import stops when it reaches the two views, so the database ends up
 * without them AND without the tables' PRIMARY KEY / AUTO_INCREMENT. The app then
 * fails with "Table ... pet_health_timeline doesn't exist" and, once rows have been
 * saved with id 0, "Invalid primary key: '0' is not allowed".
 *
 * This runs database/fix_keys.sql and database/fix_views.sql against the database in
 * .env, so there is nothing to click and no wrong database to pick. Safe to run again.
 */
class DbFix extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:fix';
    protected $description = 'Repairs a broken phpMyAdmin import: puts back the ids (keys) and the two views.';
    protected $usage       = 'db:fix [keys|views]';
    protected $arguments   = ['part' => 'Only "keys" or only "views". Leave empty to do both.'];

    private const SCRIPTS = [
        'keys'  => ['file' => 'fix_keys.sql', 'what' => 'ID numbers (PRIMARY KEY + AUTO_INCREMENT)'],
        'views' => ['file' => 'fix_views.sql', 'what' => 'the views pet_health_timeline and medication_adherence'],
    ];

    public function run(array $params)
    {
        $part = strtolower($params[0] ?? '');

        if ($part !== '' && ! isset(self::SCRIPTS[$part])) {
            CLI::error('Unknown part: ' . $part . '. Use "keys", "views", or leave it empty for both.');

            return EXIT_ERROR;
        }

        $db = Database::connect();
        CLI::write('Database: ' . CLI::color($db->getDatabase(), 'yellow') . '   (from .env)');
        CLI::newLine();

        foreach (self::SCRIPTS as $name => $script) {
            if ($part !== '' && $part !== $name) {
                continue;
            }

            if ($this->runScript($db, $name, $script) === EXIT_ERROR) {
                return EXIT_ERROR;
            }
        }

        CLI::newLine();
        CLI::write('Done. Open the app again, or check it with:  php spark serve  ->  /system-check', 'green');

        return EXIT_SUCCESS;
    }

    private function runScript($db, string $name, array $script): int
    {
        $path = ROOTPATH . 'database' . DIRECTORY_SEPARATOR . $script['file'];

        if (! is_file($path)) {
            CLI::error('Missing file: ' . $path);

            return EXIT_ERROR;
        }

        CLI::write('Repairing ' . $script['what'] . ' ...');

        $report = [];

        foreach ($this->statements((string) file_get_contents($path)) as $sql) {
            try {
                $result = $db->query($sql);
            } catch (Throwable $e) {
                CLI::error('  ' . $e->getMessage());
                CLI::write('  while running: ' . mb_substr(preg_replace('/\s+/', ' ', $sql), 0, 120), 'dark_gray');

                return EXIT_ERROR;
            }

            // fix_keys.sql ends with a report of the rows that still point at the old id 0
            if (is_object($result) && method_exists($result, 'getResultArray')) {
                $report = $result->getResultArray();
            }
        }

        CLI::write('  ' . CLI::color('OK', 'green') . ' (' . $script['file'] . ')');
        $this->showReport($report);

        return EXIT_SUCCESS;
    }

    /** Prints the leftovers fix_keys.sql could not link again by itself. */
    private function showReport(array $report): void
    {
        $left = array_filter($report, static fn (array $row) => (int) ($row['rows_left'] ?? 0) > 0);

        if ($report === [] || $left === []) {
            return;
        }

        CLI::newLine();
        CLI::error('  Some rows still point at the old id 0 and have to be linked by hand:');

        foreach ($left as $row) {
            CLI::write('    ' . $row['points_at_id_0'] . ' — ' . $row['rows_left'] . ' row(s)');
        }

        CLI::write('  Open those tables in phpMyAdmin and choose the right owner, pet or vet.', 'yellow');
    }

    /**
     * Splits one .sql file into single statements: comments out, then cut at every ";".
     * The two repair scripts hold no semicolon inside a quoted string, so this is enough.
     *
     * @return list<string>
     */
    private function statements(string $sql): array
    {
        $lines = array_filter(
            explode("\n", $sql),
            static fn (string $line) => ! str_starts_with(ltrim($line), '--'),
        );

        $parts = array_map('trim', explode(';', implode("\n", $lines)));

        return array_values(array_filter($parts, static fn (string $part) => $part !== ''));
    }
}
