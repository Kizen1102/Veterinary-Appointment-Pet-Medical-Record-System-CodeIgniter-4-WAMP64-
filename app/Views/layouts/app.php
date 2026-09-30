<?php
/** Main layout for logged-in users: top bar + page content + bottom navigation. */
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'PawRecord') ?> · PawRecord</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/css/pawrecord.css') ?>" rel="stylesheet">
</head>
<body class="app-body">

<header class="app-topbar">
    <div class="app-container d-flex align-items-center justify-content-between">
        <a href="<?= site_url('home') ?>" class="text-decoration-none">
            <div class="brand-font app-brand">PawRecord</div>
            <div class="app-brand-sub">Veterinary Clinic &amp; Pet Medical Record System</div>
        </a>

        <div class="d-flex align-items-center gap-2">
            <span class="role-chip">
                <i class="bi bi-person-fill"></i>
                <?= esc(\App\Models\UserModel::ROLE_LABELS[$user['role']]) ?>
            </span>

            <!-- Notifications (Step 8 will show the real count) -->
            <span class="icon-circle" title="Notifications">
                <i class="bi bi-bell-fill text-warning"></i>
            </span>

            <!-- Sign out (a form, because logging out changes data) -->
            <form action="<?= site_url('logout') ?>" method="post" class="m-0">
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

<?= $this->include('partials/bottom_nav') ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/password-toggle.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
