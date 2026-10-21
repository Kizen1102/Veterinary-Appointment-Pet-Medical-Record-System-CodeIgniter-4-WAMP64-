<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php $hasUnread = in_array(null, array_column($notifications, 'read_at'), true); ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="brand-font mb-0">Notifications</h1>
        <?php if ($hasUnread): ?>
            <form action="<?= site_url('notifications/read') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">Mark All As Read</button>
            </form>
        <?php endif ?>
    </div>

    <!-- The same list as the bell panel, grouped by day -->
    <div class="paw-card p-0 overflow-hidden">
        <?= view('partials/notification_list', ['items' => $notifications]) ?>
    </div>
<?= $this->endSection() ?>
