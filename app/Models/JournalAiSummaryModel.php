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

    /** The vets' Journals page: summaries not yet reviewed first, then the newest. */
    public function forVets(int $limit = 50): array
    {
        return $this->select('journal_ai_summaries.*, pets.name AS pet_name, pets.species, owners.full_name AS owner_name')
            ->join('pets', 'pets.id = journal_ai_summaries.pet_id')
            ->join('users AS owners', 'owners.id = pets.owner_id')
            ->where('pets.deleted_at', null)
            ->orderBy('journal_ai_summaries.reviewed_at IS NULL', 'DESC', false)
            ->orderBy('journal_ai_summaries.created_at', 'DESC')
            ->findAll($limit);
    }
}
