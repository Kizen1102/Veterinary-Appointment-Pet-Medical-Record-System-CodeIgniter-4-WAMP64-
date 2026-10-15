<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatConversationModel extends Model
{
    protected $table         = 'chat_conversations';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'pet_id', 'medical_record_id', 'title'];
    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'title'   => 'permit_empty|max_length[150]',
    ];

    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId)->orderBy('updated_at', 'DESC')->findAll();
    }
}
