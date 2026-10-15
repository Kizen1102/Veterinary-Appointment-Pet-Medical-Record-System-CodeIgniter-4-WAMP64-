<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Daily Symptom & Behavior Journal — one entry per pet per day.
 */
class JournalEntryModel extends Model
{
    public const MOODS          = ['happy', 'calm', 'playful', 'anxious', 'irritable', 'lethargic', 'withdrawn'];
    public const WATER_INTAKE   = ['less', 'normal', 'more'];
    public const BOWEL_MOVEMENT = ['normal', 'diarrhea', 'constipated', 'none', 'bloody'];

    protected $table         = 'journal_entries';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'pet_id', 'logged_by', 'entry_date', 'appetite_score', 'activity_score', 'mood', 'sleep_hours',
        'sleep_quality', 'water_intake', 'bowel_movement', 'vomited', 'weight_kg', 'symptoms',
        'behavior_notes', 'photo_path',
    ];
    protected $validationRules = [
        'pet_id'         => 'required|is_natural_no_zero',
        'entry_date'     => 'required|valid_date[Y-m-d]',
        'appetite_score' => 'permit_empty|in_list[1,2,3,4,5]',
        'activity_score' => 'permit_empty|in_list[1,2,3,4,5]',
        'sleep_quality'  => 'permit_empty|in_list[1,2,3,4,5]',
        'sleep_hours'    => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[24]',
        'mood'           => 'permit_empty|in_list[happy,calm,playful,anxious,irritable,lethargic,withdrawn]',
        'water_intake'   => 'permit_empty|in_list[less,normal,more]',
        'bowel_movement' => 'permit_empty|in_list[normal,diarrhea,constipated,none,bloody]',
    ];

    /** "Daily health journal not yet logged" alert. */
    public function hasEntryToday(int $petId): bool
    {
        return $this->where('pet_id', $petId)->where('entry_date', date('Y-m-d'))->countAllResults() > 0;
    }

    /** Entries of the last $days days, oldest first (input for the AI summary). */
    public function recent(int $petId, int $days = 14): array
    {
        return $this->where('pet_id', $petId)
            ->where('entry_date >=', date('Y-m-d', strtotime('-' . ($days - 1) . ' days')))
            ->orderBy('entry_date')
            ->findAll();
    }
}
