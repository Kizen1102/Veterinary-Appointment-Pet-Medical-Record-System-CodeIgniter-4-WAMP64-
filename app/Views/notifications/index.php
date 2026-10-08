<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php
    $icons = [
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
    $hasUnread = in_array(null, array_column($notifications, 'read_at'), true);
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="brand-font mb-0">Notifications</h1>
        <?php if ($hasUnread): ?>
            <form action="<?= site_url('notifications/read') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all as read</button>
            </form>
        <?php endif ?>
    </div>

    <?php if ($notifications === []): ?>
        <div class="paw-card text-center text-muted">
            <div class="fs-1 mb-2">🔔</div>
            No notifications yet.
        </div>
    <?php endif ?>

    <?php foreach ($notifications as $n): ?>
        <!-- Opening an alert marks it read (the red number goes down), then shows its page -->
        <a href="<?= site_url('notifications/' . $n['id']) ?>"
           class="notification-item <?= $n['read_at'] === null ? 'is-unread' : '' ?>">
            <span class="fs-4"><?= $icons[$n['type']] ?? '🔔' ?></span>
            <div class="flex-grow-1">
                <div class="fw-semibold"><?= esc($n['title']) ?></div>
                <?php if ($n['message']): ?>
                    <div class="small"><?= esc($n['message']) ?></div>
                <?php endif ?>
                <div class="small text-muted"><?= date('M j, g:i A', strtotime($n['created_at'])) ?></div>
            </div>
        </a>
    <?php endforeach ?>
<?= $this->endSection() ?>
