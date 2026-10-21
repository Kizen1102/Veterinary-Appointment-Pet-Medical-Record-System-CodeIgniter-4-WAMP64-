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

    /**
     * The newest alerts of one user, unread or not: what the bell panel and the page show.
     * The pet is joined in for the "Pet: Bantay" line under each alert.
     */
    public function latestFor(int $userId, int $limit = self::PANEL_LIMIT): array
    {
        return $this->select('notifications.*, pets.name AS pet_name, pets.species AS pet_species')
            ->join('pets', 'pets.id = notifications.pet_id', 'left')
            ->where('notifications.user_id', $userId)
            ->orderBy('notifications.created_at', 'DESC')
            ->orderBy('notifications.id', 'DESC')
            ->findAll($limit);
    }

    public static function icon(string $type): string
    {
        return self::ICONS[$type] ?? self::ICONS['general'];
    }

    /**
     * The round picture of an alert: the pet it is about, or — when it is about no pet —
     * the kind of alert itself, so that the small badge beside it is not the same picture twice.
     */
    public static function avatar(?string $species, string $type = 'general'): string
    {
        return PetModel::SPECIES[$species] ?? self::icon($type);
    }

    /** The small picture on the avatar's corner, only when the avatar is a pet. */
    public static function badge(?string $species, string $type): string
    {
        return isset(PetModel::SPECIES[$species]) ? self::icon($type) : '';
    }

    /** The grey bar above a day's alerts: "TODAY", "YESTERDAY" or "FRI, 4 JUN 2021". */
    public static function dayLabel(string $createdAt): string
    {
        $day = date('Y-m-d', strtotime($createdAt));

        if ($day === date('Y-m-d')) {
            return 'TODAY';
        }

        if ($day === date('Y-m-d', strtotime('-1 day'))) {
            return 'YESTERDAY';
        }

        return strtoupper(date('D, j M Y', strtotime($createdAt)));
    }

    /** The pink link under the message, e.g. VIEW for an appointment. */
    public static function actionLabel(string $type): string
    {
        return in_array($type, ['journal_summary', 'journal_reminder'], true) ? 'MORE INFO' : 'VIEW';
    }

    public function markUnread(int $id, int $userId): void
    {
        $this->where('id', $id)->where('user_id', $userId)->set('read_at', null)->update();
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
