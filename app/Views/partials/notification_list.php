<?php
/**
 * One list of alerts, grouped by day. Shared by the bell panel and the Notifications page,
 * and copied by assets/js/notifications.js when it refreshes the panel.
 *
 * Each alert shows the pet it is about, what happened, a VIEW link, and a dot on the right
 * that turns it read or unread.
 *
 * @var array  $items  Alerts, newest first (NotificationModel::latestFor)
 * @var string $empty  What to say when there is nothing
 */
use App\Models\NotificationModel;

$day = null;
?>
<?php if ($items === []): ?>
    <div class="bell-empty">
        <div class="fs-3">🔔</div>
        <?= esc($empty ?? 'No notifications yet.') ?>
    </div>
<?php endif ?>

<?php foreach ($items as $n): ?>
    <?php $label = NotificationModel::dayLabel($n['created_at']); ?>
    <?php if ($label !== $day): ?>
        <div class="bell-day"><?= esc($day = $label) ?></div>
    <?php endif ?>

    <div class="bell-item <?= $n['read_at'] === null ? 'is-unread' : '' ?>">
        <?php $badge = NotificationModel::badge($n['pet_species'] ?? null, $n['type']); ?>
        <span class="bell-avatar">
            <?= NotificationModel::avatar($n['pet_species'] ?? null, $n['type']) ?>
            <?php if ($badge !== ''): ?>
                <span class="bell-avatar-badge"><?= $badge ?></span>
            <?php endif ?>
        </span>

        <div class="bell-item-text">
            <a href="<?= site_url('notifications/' . $n['id']) ?>" class="bell-item-title"><?= esc($n['title']) ?></a>

            <?php if ($n['message']): ?>
                <div class="bell-item-message"><?= esc($n['message']) ?></div>
            <?php endif ?>

            <?php if ($n['link_url']): ?>
                <a href="<?= site_url('notifications/' . $n['id']) ?>" class="bell-item-action">
                    <?= NotificationModel::actionLabel($n['type']) ?>
                </a>
            <?php endif ?>

            <div class="bell-item-meta">
                <?php if (! empty($n['pet_name'])): ?>
                    <span class="bell-item-pet">Pet: <strong><?= esc($n['pet_name']) ?></strong></span>
                <?php endif ?>
                <span><?= date('j M Y H:i', strtotime($n['created_at'])) ?></span>
            </div>
        </div>

        <!-- Filled dot = unread. Pressing it turns the alert read or unread again. -->
        <form action="<?= site_url('notifications/' . $n['id'] . '/toggle') ?>" method="post" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="bell-dot <?= $n['read_at'] === null ? 'is-unread' : '' ?>"
                    title="<?= $n['read_at'] === null ? 'Mark as read' : 'Mark as unread' ?>"
                    aria-label="<?= $n['read_at'] === null ? 'Mark as read' : 'Mark as unread' ?>"></button>
        </form>
    </div>
<?php endforeach ?>
