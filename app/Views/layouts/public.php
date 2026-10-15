<?php
/** Layout for public pages (landing page): clinic status bar, header, footer. */
$user = $user ?? null;
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
    <link href="<?= base_url('assets/css/landing.css') ?>" rel="stylesheet">
</head>
<body class="site">

<!-- Clinic status bar: green dot = open now, red dot = closed -->
<div class="status-bar">
    <span class="status-dot <?= $isOpen ? 'is-open' : '' ?>"></span>
    <?php if ($isOpen): ?>
        Our clinic is open now until <?= esc($closesAt) ?>. Clinic hours: <?= esc($hours) ?>.
    <?php else: ?>
        Our clinic is closed now. Clinic hours: <?= esc($hours) ?>. You can still book online anytime.
    <?php endif ?>
</div>

<!-- Header -->
<header class="site-header">
    <div class="site-container d-flex align-items-center justify-content-between">
        <a href="<?= site_url('/') ?>" class="site-logo">
            <span class="site-logo-badge">🐾</span>
            <span>
                <span class="site-logo-name">pawrecord</span>
                <span class="site-logo-sub"><?= esc($clinicName) ?></span>
            </span>
        </a>

        <nav class="site-links d-none d-lg-flex align-items-center">
            <a href="#top">Home</a>
            <a href="#how">How it works</a>
            <a href="#features">Services</a>
            <a href="#faq">FAQs</a>
            <?php if ($user): ?>
                <a href="<?= site_url('home') ?>" class="site-pill">Go to Dashboard</a>
            <?php else: ?>
                <a href="<?= site_url('register') ?>" class="site-pill">Sign Up Free</a>
                <a href="<?= site_url('login') ?>">Log In</a>
            <?php endif ?>
        </nav>

        <!-- Phones: just the main button -->
        <a href="<?= site_url($user ? 'home' : 'login') ?>" class="site-pill d-lg-none"><?= $user ? 'Dashboard' : 'Log In' ?></a>
    </div>
</header>

<main id="top">
    <?= $this->renderSection('content') ?>
</main>

<footer class="site-footer">
    <div class="site-container footer-grid">
        <div>
            <div class="site-logo">
                <span class="site-logo-badge">🐾</span>
                <span><span class="site-logo-name">pawrecord</span><span class="site-logo-sub"><?= esc($clinicName) ?></span></span>
            </div>
            <p class="small mt-3 mb-0">
                PawRecord helps you take care of your pet together with your veterinarian.
                It does not replace a visit to the clinic.
            </p>
        </div>
        <div>
            <div class="footer-title">Quick Links</div>
            <a href="#top">Home</a>
            <a href="#how">How it works</a>
            <a href="#features">Services</a>
            <a href="#faq">FAQs</a>
        </div>
        <div>
            <div class="footer-title">Account</div>
            <a href="<?= site_url('register') ?>">Sign up</a>
            <a href="<?= site_url('login') ?>">Log in</a>
            <a href="<?= site_url('forgot-password') ?>">Forgot password</a>
        </div>
        <div>
            <div class="footer-title">Clinic</div>
            <span><?= esc($clinicName) ?></span>
            <span><?= esc($hours) ?></span>
        </div>
    </div>
    <div class="site-container footer-copy">© <?= date('Y') ?> PawRecord. All rights reserved.</div>
</footer>

</body>
</html>
