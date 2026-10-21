<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Read-only access to the pet_health_timeline view (MySQL / MariaDB).
 */
class PetHealthTimelineModel extends Model
{
    protected $table      = 'pet_health_timeline';
    protected $primaryKey = 'source_id';
    protected $returnType = 'array';

    /**
     * How each kind of event looks on the timeline, and which filter tab it belongs to.
     * The first ones are the medical record types, then the other event types.
     */
    public const EVENT_TYPES = [
        'consultation'       => ['label' => 'Consultation', 'icon' => '🩺', 'group' => 'visits'],
        'treatment'          => ['label' => 'Treatment', 'icon' => '💉', 'group' => 'visits'],
        'surgery'            => ['label' => 'Surgery', 'icon' => '🏥', 'group' => 'visits'],
        'lab_test'           => ['label' => 'Lab Test', 'icon' => '🧪', 'group' => 'visits'],
        'follow_up'          => ['label' => 'Follow-up Visit', 'icon' => '🔁', 'group' => 'visits'],
        'emergency'          => ['label' => 'Emergency', 'icon' => '🚨', 'group' => 'visits'],
        'other'              => ['label' => 'Record', 'icon' => '📄', 'group' => 'visits'],
        'vaccination'        => ['label' => 'Vaccination', 'icon' => '💉', 'group' => 'vaccines'],
        'medication_started' => ['label' => 'Medication Started', 'icon' => '💊', 'group' => 'meds'],
        'follow_up_due'      => ['label' => 'Follow-up Due', 'icon' => '📌', 'group' => 'appointments'],
        'appointment'        => ['label' => 'Appointment', 'icon' => '📅', 'group' => 'appointments'],
    ];

    /** Filter tabs above the timeline. */
    public const GROUPS = [
        'all'          => 'All',
        'visits'       => 'Visits',
        'vaccines'     => 'Vaccines',
        'meds'         => 'Medications',
        'appointments' => 'Appointments',
    ];

    public function forPet(int $petId): array
    {
        return $this->where('pet_id', $petId)->orderBy('event_date', 'DESC')->findAll();
    }
}
