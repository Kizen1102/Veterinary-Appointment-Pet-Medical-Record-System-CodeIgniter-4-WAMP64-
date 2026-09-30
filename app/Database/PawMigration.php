<?php

namespace App\Database;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Shared helpers for the PawRecord migrations.
 *
 * On MySQL/MariaDB (XAMPP) the tables are created exactly like
 * database/pawrecord_schema.sql. On SQLite (used only by the automated tests)
 * MySQL-only features — ON UPDATE timestamps, CHECK rules added by ALTER TABLE,
 * views — are skipped.
 */
abstract class PawMigration extends Migration
{
    protected function isMySQL(): bool
    {
        return $this->db->DBDriver === 'MySQLi';
    }

    /** INT UNSIGNED AUTO_INCREMENT primary key column. */
    protected function id(): array
    {
        return ['id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true]];
    }

    /** INT UNSIGNED column for a foreign key. */
    protected function fk(bool $nullable = false): array
    {
        return ['type' => 'INT', 'unsigned' => true, 'null' => $nullable];
    }

    /** created_at / updated_at (and optionally deleted_at for soft deletes). */
    protected function timestamps(bool $updatedAt = true, bool $deletedAt = false): array
    {
        $fields = [
            'created_at' => ['type' => 'DATETIME', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ];

        if ($updatedAt) {
            $fields['updated_at'] = [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => new RawSql($this->isMySQL() ? 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP'),
            ];
        }

        if ($deletedAt) {
            $fields['deleted_at'] = ['type' => 'DATETIME', 'null' => true];
        }

        return $fields;
    }

    /** Named foreign key (SQLite does not support FK names, so they are only used on MySQL). */
    protected function foreignKey(string $field, string $table, string $onDelete, string $name): void
    {
        $this->forge->addForeignKey($field, $table, 'id', 'CASCADE', $onDelete, $this->isMySQL() ? $name : '');
    }

    protected function createTableWithEngine(string $table): void
    {
        $this->forge->createTable($table, false, $this->isMySQL() ? ['ENGINE' => 'InnoDB'] : []);
    }

    /** Adds a named CHECK constraint (MySQL 8.0.16+ / MariaDB 10.2+). */
    protected function addCheck(string $table, string $name, string $expression): void
    {
        if ($this->isMySQL()) {
            $this->db->query("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expression})");
        }
    }
}
