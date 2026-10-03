<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    public const TYPES = [
        'medication_due', 'missed_dose', 'appointment_reminder', 'appointment_update',
        'journal_reminder', 'vaccine_due', 'follow_up_due', 'journal_summary', 'general',
    ];

    protected $table         = 'notifications';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = ['user_id', 'pet_id', 'type', 'title', 'message', 'link_url', 'related_table', 'related_id', 'read_at'];
    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'type'    => 'required|in_list[medication_due,missed_dose,appointment_reminder,appointment_update,journal_reminder,vaccine_due,follow_up_due,journal_summary,general]',
        'title'   => 'required|max_length[150]',
    ];

    public function unreadFor(int $userId): array
    {
        return $this->where('user_id', $userId)->where('read_at', null)->orderBy('created_at', 'DESC')->findAll();
    }

    public function markRead(int $id, int $userId): void
    {
        $this->where('id', $id)->where('user_id', $userId)->set('read_at', date('Y-m-d H:i:s'))->update();
    }
}
