<?php

namespace App\Models;

use CodeIgniter\Model;

class JournalAiSummaryModel extends Model
{
    public const CONCERN_LEVELS = ['none', 'low', 'moderate', 'high'];

    protected $table         = 'journal_ai_summaries';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = [
        'pet_id', 'requested_by', 'appointment_id', 'period_start', 'period_end', 'entries_count',
        'summary', 'notable_changes', 'concern_level', 'ai_model', 'reviewed_by', 'reviewed_at',
    ];
    protected $validationRules = [
        'pet_id'        => 'required|is_natural_no_zero',
        'period_start'  => 'required|valid_date[Y-m-d]',
        'period_end'    => 'required|valid_date[Y-m-d]',
        'summary'       => 'required',
        'concern_level' => 'permit_empty|in_list[none,low,moderate,high]',
    ];

    public function latestForPet(int $petId): ?array
    {
        return $this->where('pet_id', $petId)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->first();
    }
}
