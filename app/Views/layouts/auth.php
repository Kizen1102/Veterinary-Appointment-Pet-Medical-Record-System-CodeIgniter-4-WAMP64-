<?php
/**
 * Layout for the sign-in pages (login, sign up, forgot/reset password, setup).
 * Redesign D2: same teal look as the landing page.
 * Big screens: teal picture panel on the left, form on the right. Phones: teal header, form below.
 */
$clinicName = config(\Config\Clinic::class)->name;

// Pet photo for the teal panel (optional): public/assets/img/auth-pets.jpg
$photo = is_file(FCPATH . 'assets/img/auth-pets.jpg') ? base_url('assets/img/auth-pets.jpg') : null;
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
    <link href="<?= base_url('assets/css/auth.css') ?>" rel="stylesheet">
</head>
<body class="auth-page">

<div class="auth-shell">
    <!-- Left: teal panel with the logo and what PawRecord does -->
    <aside class="auth-side <?= $photo ? 'has-photo' : '' ?>"
        <?= $photo ? 'style="--auth-photo: url(\'' . esc($photo, 'attr') . '\')"' : '' ?>>
        <span class="auth-paw" style="top: 12%; left: 78%;">🐾</span>
        <span class="auth-paw" style="top: 40%; left: 86%;">🐾</span>
        <span class="auth-paw" style="top: 84%; left: 70%;">🐾</span>

        <a href="<?= site_url('/') ?>" class="auth-logo">
            <span class="auth-logo-badge">🐾</span>
            <span>
                <span class="auth-logo-name">pawrecord</span>
                <span class="auth-logo-sub"><?= esc($clinicName) ?></span>
            </span>
        </a>

        <div class="auth-side-body">
            <h1>Your pet's health,<br>all in one place.</h1>
            <ul class="auth-points">
                <li><i class="bi bi-calendar2-check"></i> Book vet visits online</li>
                <li><i class="bi bi-clock-history"></i> Every record and vaccine on one timeline</li>
                <li><i class="bi bi-capsule"></i> Medicine reminders that don't forget</li>
                <li><i class="bi bi-journal-medical"></i> A daily journal your vet can read</li>
            </ul>
        </div>

        <!-- Or a cut-out animal picture (PNG with a transparent background): public/assets/img/auth-pets.png -->
        <?php if (! $photo && is_file(FCPATH . 'assets/img/auth-pets.png')): ?>
            <img src="<?= base_url('assets/img/auth-pets.png') ?>" alt="Happy dog and cat" class="auth-pets">
        <?php endif ?>

        <div class="auth-side-foot">&copy; <?= date('Y') ?> <?= esc($clinicName) ?></div>
    </aside>

    <!-- Right: the page's form -->
    <section class="auth-main">
        <div class="auth-topbar">
            <a href="<?= site_url('/') ?>" class="auth-back"><i class="bi bi-arrow-left"></i> Back to home</a>
        </div>

        <main class="auth-card">
            <?= $this->renderSection('content') ?>
        </main>

        <footer class="auth-footer">CodeIgniter 4 · PHP · MySQL</footer>
    </section>
</div>

<script src="<?= base_url('assets/js/password-toggle.js') ?>"></script>
</body>
</html>
