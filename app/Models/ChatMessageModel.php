<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatMessageModel extends Model
{
    protected $table         = 'chat_messages';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = ['conversation_id', 'sender', 'content', 'ai_model', 'input_tokens', 'output_tokens'];
    protected $validationRules = [
        'conversation_id' => 'required|is_natural_no_zero',
        'sender'          => 'required|in_list[user,assistant]',
        'content'         => 'required',
    ];

    public function forConversation(int $conversationId): array
    {
        return $this->where('conversation_id', $conversationId)->orderBy('created_at')->orderBy('id')->findAll();
    }
}
