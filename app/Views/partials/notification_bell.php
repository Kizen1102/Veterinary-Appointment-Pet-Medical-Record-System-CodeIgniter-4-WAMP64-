<?php
/**
 * The 🔔 bell with its in-app panel (top bar).
 *
 * The panel opens over the page, so an alert is read without leaving what the user is doing.
 * assets/js/notifications.js keeps the red number and the list fresh, and shows a pop-up
 * when a new alert arrives.
 *
 * @var int   $unread Unread alerts
 * @var array $latest The newest alerts (NotificationModel::latestFor)
 */
?>
<div class="dropdown bell-wrap">
    <button class="icon-circle position-relative" type="button" id="bellButton"
            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
            title="Notifications" aria-label="Notifications">
        <i class="bi bi-bell"></i>
        <span class="bell-count" data-bell-count <?= $unread > 0 ? '' : 'hidden' ?>><?= $unread > 9 ? '9+' : $unread ?></span>
    </button>

    <div class="dropdown-menu dropdown-menu-end bell-panel" aria-labelledby="bellButton">
        <div class="bell-panel-head">
            <span class="bell-panel-name">
                Notifications
                <span class="bell-head-count" data-bell-count <?= $unread > 0 ? '' : 'hidden' ?>><?= $unread ?></span>
            </span>
            <form action="<?= site_url('notifications/read') ?>" method="post" class="m-0" data-bell-read-all <?= $unread > 0 ? '' : 'hidden' ?>>
                <?= csrf_field() ?>
                <button type="submit" class="bell-read-all">Mark All As Read</button>
            </form>
        </div>

        <!-- All / Unread, like the bell panel of a social app. The list below follows the chosen tab. -->
        <div class="bell-tabs" data-bell-tabs>
            <button type="button" class="bell-tab is-active" data-bell-tab="all">All</button>
            <button type="button" class="bell-tab" data-bell-tab="unread">Unread</button>
        </div>

        <div class="bell-panel-list" data-bell-list>
            <?= view('partials/notification_list', ['items' => $latest]) ?>
        </div>

        <a href="<?= site_url('notifications') ?>" class="bell-panel-foot">See all notifications</a>
    </div>
</div>

<!-- Where a new alert pops up while the user is on any page -->
<div class="toast-stack" data-toast-stack></div>
