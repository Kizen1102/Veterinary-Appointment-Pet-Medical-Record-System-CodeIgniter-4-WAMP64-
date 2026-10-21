<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    public const TYPES = [
        'medication_due', 'missed_dose', 'appointment_reminder', 'appointment_update',
        'journal_reminder', 'vaccine_due', 'follow_up_due', 'journal_summary', 'general',
    ];

    /** The picture of each kind of alert, used by the bell panel, the page and the pop-ups. */
    public const ICONS = [
        'missed_dose'          => '⚠️',
        'medication_due'       => '💊',
        'appointment_reminder' => '📅',
        'appointment_update'   => '📅',
        'journal_reminder'     => '📝',
        'vaccine_due'          => '💉',
        'follow_up_due'        => '📌',
        'journal_summary'      => '🤖',
        'general'              => '🔔',
    ];

    /** How many alerts the bell panel shows before "See all notifications". */
    public const PANEL_LIMIT = 8;

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

    /** The red number on the bell. */
    public function countUnreadFor(int $userId): int
    {
        return $this->where('user_id', $userId)->where('read_at', null)->countAllResults();
    }

    /** The newest alerts of one user, unread or not: what the bell panel and the page show. */
    public function latestFor(int $userId, int $limit = self::PANEL_LIMIT): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }

    public static function icon(string $type): string
    {
        return self::ICONS[$type] ?? self::ICONS['general'];
    }

    public function markRead(int $id, int $userId): void
    {
        $this->where('id', $id)->where('user_id', $userId)->set('read_at', date('Y-m-d H:i:s'))->update();
    }

    /**
     * Marks as read the user's alerts about one record, e.g. once a vet has confirmed
     * the appointment, its "new request" bell is done: markReadFor($vetId, 'appointments', 12).
     */
    public function markReadFor(int $userId, string $table, int $relatedId): void
    {
        $this->where('user_id', $userId)
            ->where('related_table', $table)
            ->where('related_id', $relatedId)
            ->where('read_at', null)
            ->set('read_at', date('Y-m-d H:i:s'))
            ->update();
    }
}
