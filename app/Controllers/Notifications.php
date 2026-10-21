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
     * Only the signed-in user's own alerts are ever sent.
     */
    public function feed()
    {
        $userId        = (int) session('user')['id'];
        $notifications = new NotificationModel();

        $items = array_map(static fn (array $n): array => [
            'id'      => (int) $n['id'],
            'icon'    => NotificationModel::icon($n['type']),
            'title'   => $n['title'],
            'message' => (string) $n['message'],
            'time'    => date('M j, g:i A', strtotime($n['created_at'])),
            'url'     => site_url('notifications/' . $n['id']),
            'unread'  => $n['read_at'] === null,
        ], $notifications->latestFor($userId));

        return $this->response->setJSON([
            'unread' => $notifications->countUnreadFor($userId),
            'items'  => $items,
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
