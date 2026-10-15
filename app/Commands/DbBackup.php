<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Makes a backup .sql file of the whole database that imports cleanly in phpMyAdmin.
 *
 * phpMyAdmin's own Export writes the two views as empty "stand-in" tables, which
 * fails on import (#1064). This file writes the tables and data first and the
 * views last, so no stand-ins are needed.
 *
 *   php spark db:backup                      -> writable/backups/pawrecord_db_2026-10-01_1530.sql
 *   php spark db:backup D:\pawrecord_db.sql  -> saves straight to the USB drive
 */
class DbBackup extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:backup';
    protected $description = 'Saves the tables, data and views into one .sql file for phpMyAdmin Import.';
    protected $usage       = 'db:backup [file]';
    protected $arguments   = ['file' => 'Where to save the .sql file (default: writable/backups/).'];

    private const ROWS_PER_INSERT = 100;

    public function run(array $params)
    {
        $db     = Database::connect();
        $dbName = $db->getDatabase();
        $file   = $params[0] ?? WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . $dbName . '_' . date('Y-m-d_Hi') . '.sql';

        if (! is_dir(dirname($file)) && ! mkdir(dirname($file), 0775, true)) {
            CLI::error('Cannot create the folder: ' . dirname($file));

            return EXIT_ERROR;
        }

        $tables = [];
        $views  = [];

        foreach ($db->query('SHOW FULL TABLES')->getResultArray() as $row) {
            $row  = array_values($row);
            $name = $row[0];

            if ($row[1] === 'VIEW') {
                $views[] = $name;
            } else {
                $tables[] = $name;
            }
        }

        $out = fopen($file, 'wb');

        if ($out === false) {
            CLI::error('Cannot write the file: ' . $file);

            return EXIT_ERROR;
        }

        fwrite($out, "-- PawRecord backup of `{$dbName}`, made " . date('Y-m-d H:i:s') . " with: php spark db:backup\n");
        fwrite($out, "-- Restore: phpMyAdmin -> select the database -> Import -> this file -> Go.\n");
        fwrite($out, "-- It replaces the tables and views that are already there.\n\n");
        fwrite($out, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

        foreach ($views as $view) {
            fwrite($out, "DROP VIEW IF EXISTS `{$view}`;\n");
        }

        foreach ($tables as $table) {
            $create = $db->query("SHOW CREATE TABLE `{$table}`")->getRowArray()['Create Table'];

            fwrite($out, "\n-- Table `{$table}`\n");
            fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n{$create};\n");

            $count = $this->writeRows($db, $out, $table);
            CLI::write(str_pad($table, 24) . $count . ' rows');
        }

        foreach ($views as $view) {
            $create = $db->query("SHOW CREATE VIEW `{$view}`")->getRowArray()['Create View'];
            // Remove "DEFINER=`root`@`localhost`" so the view also imports on another PC or user.
            $create = preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/', '', $create);

            fwrite($out, "\n-- View `{$view}`\n{$create};\n");
            CLI::write(str_pad($view, 24) . 'view');
        }

        fwrite($out, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($out);

        CLI::write('Backup saved: ' . realpath($file), 'green');

        return EXIT_SUCCESS;
    }

    /** Writes INSERT statements for one table, a few rows at a time. Returns the row count. */
    private function writeRows($db, $out, string $table): int
    {
        $count  = 0;
        $offset = 0;

        do {
            $rows = $db->query("SELECT * FROM `{$table}` LIMIT " . self::ROWS_PER_INSERT . " OFFSET {$offset}")->getResultArray();

            if ($rows === []) {
                break;
            }

            $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
            $values  = [];

            foreach ($rows as $row) {
                $values[] = '(' . implode(', ', array_map(static fn ($value) => $value === null ? 'NULL' : $db->escape($value), $row)) . ')';
            }

            fwrite($out, "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $values) . ";\n");

            $count  += count($rows);
            $offset += self::ROWS_PER_INSERT;
        } while (count($rows) === self::ROWS_PER_INSERT);

        return $count;
    }
}
