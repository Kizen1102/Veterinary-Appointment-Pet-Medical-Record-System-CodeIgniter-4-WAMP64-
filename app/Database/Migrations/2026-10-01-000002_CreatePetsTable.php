<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * pets — each animal belongs to one owner and may have a primary vet.
 */
class CreatePetsTable extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'owner_id'           => $this->fk(),
            'primary_vet_id'     => $this->fk(true),
            'name'               => ['type' => 'VARCHAR', 'constraint' => 80],
            'species'            => ['type' => 'VARCHAR', 'constraint' => 40],
            'breed'              => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'sex'                => ['type' => 'ENUM', 'constraint' => ['male', 'female', 'unknown'], 'default' => 'unknown'],
            'is_neutered'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'birth_date'         => ['type' => 'DATE', 'null' => true],
            'weight_kg'          => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'color_markings'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'microchip_number'   => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'blood_type'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'allergies'          => ['type' => 'TEXT', 'null' => true],
            'chronic_conditions' => ['type' => 'TEXT', 'null' => true],
            'photo_path'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notes'              => ['type' => 'TEXT', 'null' => true],
            'is_deceased'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ] + $this->timestamps(true, true));

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('microchip_number', 'uq_pets_microchip');
        $this->forge->addKey('owner_id', false, false, 'idx_pets_owner');
        $this->forge->addKey('primary_vet_id', false, false, 'idx_pets_primary_vet');
        $this->forge->addKey('species', false, false, 'idx_pets_species');
        $this->foreignKey('owner_id', 'users', 'CASCADE', 'fk_pets_owner');
        $this->foreignKey('primary_vet_id', 'users', 'SET NULL', 'fk_pets_primary_vet');
        $this->createTableWithEngine('pets');

        $this->addCheck('pets', 'chk_pets_weight', 'weight_kg IS NULL OR weight_kg > 0');
    }

    public function down(): void
    {
        $this->forge->dropTable('pets', true);
    }
}
