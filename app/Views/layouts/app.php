<?php
/**
 * Main layout for signed-in users (Redesign D3).
 * Big screens: sidebar on the left + wide page. Phones: top bar + page + bottom navigation.
 */
$user = current_user();

// Unread notifications: the red number on the bell (top bar) and in the sidebar
$unread = (new \App\Models\NotificationModel())->where('user_id', $user['id'])->where('read_at', null)->countAllResults();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'PawRecord') ?> · PawRecord</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/css/pawrecord.css') ?>" rel="stylesheet">
</head>
<body class="app-body">

<div class="app-shell">
    <?= view('partials/sidebar', ['unread' => $unread]) ?>

    <div class="app-content">
        <!-- Top bar: logo on phones, page title on big screens -->
        <header class="app-topbar">
            <div class="app-container d-flex align-items-center justify-content-between">
                <a href="<?= site_url('home') ?>" class="topbar-logo d-lg-none">
                    <span class="side-logo-badge">🐾</span>
                    <span class="side-logo-name">pawrecord</span>
                </a>
                <div class="topbar-title d-none d-lg-block"><?= esc($title ?? '') ?></div>

                <div class="d-flex align-items-center gap-2">
                    <span class="role-chip d-none d-sm-inline">
                        <i class="bi bi-person"></i>
                        <?= esc(\App\Models\UserModel::ROLE_LABELS[$user['role']]) ?>
                    </span>

                    <!-- Notifications: the red number = unread alerts -->
                    <a href="<?= site_url('notifications') ?>" class="icon-circle position-relative" title="Notifications">
                        <i class="bi bi-bell"></i>
                        <?php if ($unread > 0): ?>
                            <span class="bell-count"><?= $unread > 9 ? '9+' : $unread ?></span>
                        <?php endif ?>
                    </a>

                    <!-- Sign out on phones (on big screens it is at the bottom of the sidebar) -->
                    <form action="<?= site_url('logout') ?>" method="post" class="m-0 d-lg-none">
                        <?= csrf_field() ?>
                        <button type="submit" class="icon-circle" title="Sign out" aria-label="Sign out">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="app-container app-main">
            <?= $this->include('partials/alerts') ?>
            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>

<?= $this->include('partials/bottom_nav') ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/password-toggle.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
