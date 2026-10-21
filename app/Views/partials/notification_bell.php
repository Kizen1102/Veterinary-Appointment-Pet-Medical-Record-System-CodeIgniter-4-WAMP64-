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
use App\Models\NotificationModel;
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
            <span class="fw-semibold">Notifications</span>
            <form action="<?= site_url('notifications/read') ?>" method="post" class="m-0" data-bell-read-all <?= $unread > 0 ? '' : 'hidden' ?>>
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-link btn-sm p-0 bell-read-all">Mark all as read</button>
            </form>
        </div>

        <div class="bell-panel-list" data-bell-list>
            <?php if ($latest === []): ?>
                <div class="bell-empty">
                    <div class="fs-3">🔔</div>
                    No notifications yet.
                </div>
            <?php endif ?>

            <?php foreach ($latest as $n): ?>
                <a href="<?= site_url('notifications/' . $n['id']) ?>" class="bell-item <?= $n['read_at'] === null ? 'is-unread' : '' ?>">
                    <span class="bell-item-icon"><?= NotificationModel::icon($n['type']) ?></span>
                    <span class="bell-item-text">
                        <span class="bell-item-title"><?= esc($n['title']) ?></span>
                        <?php if ($n['message']): ?>
                            <span class="bell-item-message"><?= esc($n['message']) ?></span>
                        <?php endif ?>
                        <span class="bell-item-time"><?= date('M j, g:i A', strtotime($n['created_at'])) ?></span>
                    </span>
                </a>
            <?php endforeach ?>
        </div>

        <a href="<?= site_url('notifications') ?>" class="bell-panel-foot">See all notifications</a>
    </div>
</div>

<!-- Where a new alert pops up while the user is on any page -->
<div class="toast-stack" data-toast-stack></div>
