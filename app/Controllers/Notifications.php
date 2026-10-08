<?php

namespace App\Controllers;

use App\Models\NotificationModel;

/**
 * The 🔔 bell: a user's alerts (missed doses for now, more types in later steps).
 */
class Notifications extends BaseController
{
    // GET /notifications
    public function index()
    {
        $notifications = (new NotificationModel())
            ->where('user_id', session('user')['id'])
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll(50);

        return view('notifications/index', [
            'title'         => 'Notifications',
            'notifications' => $notifications,
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

        return redirect()->to('/notifications');
    }
}
