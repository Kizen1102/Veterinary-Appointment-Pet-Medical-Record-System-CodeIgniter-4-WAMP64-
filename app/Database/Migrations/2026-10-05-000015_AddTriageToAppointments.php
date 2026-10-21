<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * Step 15: the AI urgency check of an appointment request.
 * triage_level   = emergency | high | medium | low (NULL when the owner wrote no reason)
 * triage_summary = one or two sentences for the vet
 */
class AddTriageToAppointments extends PawMigration
{
    public function up(): void
    {
        $this->forge->addColumn('appointments', [
            'triage_level'   => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'after' => 'reason'],
            'triage_summary' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'triage_level'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('appointments', ['triage_level', 'triage_summary']);
    }
}
