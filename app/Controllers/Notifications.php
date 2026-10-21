<?php

namespace App\Controllers;

use App\Models\NotificationModel;

/**
 * The 🔔 bell: a user's alerts, in the app itself.
 *
 *   - the panel under the bell and the full page show them,
 *   - feed() is read every few seconds by assets/js/notifications.js, so a new alert
 *     appears (with a small pop-up) without the user refreshing the page.
 */
class Notifications extends BaseController
{
    // GET /notifications
    public function index()
    {
        $notifications = (new NotificationModel())->latestFor((int) session('user')['id'], 50);

        return view('notifications/index', [
            'title'         => 'Notifications',
            'notifications' => $notifications,
        ]);
    }

    /**
     * The alerts as JSON for the bell panel (GET /notifications/feed).
     * "?filter=unread" is the panel's Unread tab. Only the user's own alerts are ever sent.
     */
    public function feed()
    {
        $userId        = (int) session('user')['id'];
        $unreadOnly    = $this->request->getGet('filter') === 'unread';
        $notifications = new NotificationModel();

        $items = array_map(static fn (array $n): array => [
            'id'      => (int) $n['id'],
            'day'     => NotificationModel::dayLabel($n['created_at']),
            'avatar'  => NotificationModel::avatar($n['pet_species'] ?? null, $n['type']),
            'badge'   => NotificationModel::badge($n['pet_species'] ?? null, $n['type']),
            'icon'    => NotificationModel::icon($n['type']),
            'title'   => $n['title'],
            'message' => (string) $n['message'],
            'action'  => $n['link_url'] ? NotificationModel::actionLabel($n['type']) : '',
            'pet'     => (string) ($n['pet_name'] ?? ''),
            'time'    => date('j M Y H:i', strtotime($n['created_at'])),
            'url'     => site_url('notifications/' . $n['id']),
            'toggle'  => site_url('notifications/' . $n['id'] . '/toggle'),
            'unread'  => $n['read_at'] === null,
        ], $notifications->latestFor($userId, NotificationModel::PANEL_LIMIT, $unreadOnly));

        return $this->response->setJSON([
            'unread' => $notifications->countUnreadFor($userId),
            'items'  => $items,
            // The forms the panel draws need the token of this moment: it changes on every POST
            'csrf'   => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }

    // Opens one alert: marks it read, then goes to its page (GET /notifications/<id>)
    public function open(int $id)
    {
        $notifications = new NotificationModel();
        $notification  = $notifications->where('user_id', session('user')['id'])->find($id); // only your own

        if (! $notification) {
            return redirect()->to('/notifications');
        }

        $notifications->markRead($id, (int) session('user')['id']);

        return redirect()->to($notification['link_url'] ? site_url($notification['link_url']) : '/notifications');
    }

    /**
     * The dot at the right of an alert: read becomes unread and back
     * (POST /notifications/<id>/toggle). Answers JSON when the panel asks.
     */
    public function toggle(int $id)
    {
        $userId        = (int) session('user')['id'];
        $notifications = new NotificationModel();
        $notification  = $notifications->where('user_id', $userId)->find($id); // only your own

        if (! $notification) {
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(404)->setJSON(['ok' => false])
                : redirect()->to('/notifications');
        }

        if ($notification['read_at'] === null) {
            $notifications->markRead($id, $userId);
        } else {
            $notifications->markUnread($id, $userId);
        }

        return $this->request->isAJAX()
            ? $this->response->setJSON(['ok' => true, 'unread' => $notifications->countUnreadFor($userId)])
            : redirect()->back();
    }

    // Marks all of the user's alerts as read (POST /notifications/read)
    public function readAll()
    {
        (new NotificationModel())
            ->where('user_id', session('user')['id'])
            ->where('read_at', null)
            ->set('read_at', date('Y-m-d H:i:s'))
            ->update();

        return redirect()->back()->with('success', 'All notifications are marked as read.');
    }
}
