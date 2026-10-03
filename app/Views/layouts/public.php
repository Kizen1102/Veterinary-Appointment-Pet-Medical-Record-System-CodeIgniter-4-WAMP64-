<?php
/** Layout for public pages (landing page): simple top navigation + footer. */
$user = $user ?? null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'PawRecord') ?> · PawRecord</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/css/landing.css') ?>" rel="stylesheet">
</head>
<body class="site">

<!-- Top navigation -->
<header class="site-nav">
    <div class="site-container d-flex align-items-center justify-content-between">
        <a href="<?= site_url('/') ?>" class="site-logo">🐾 PawRecord</a>

        <nav class="site-links d-none d-md-flex">
            <a href="#features">Features</a>
            <a href="#how">How it works</a>
            <a href="#faq">FAQ</a>
        </nav>

        <div class="d-flex align-items-center gap-2">
            <?php if ($user): ?>
                <a href="<?= site_url('home') ?>" class="site-btn">Go to dashboard</a>
            <?php else: ?>
                <a href="<?= site_url('login') ?>" class="site-link-login">Log in</a>
                <a href="<?= site_url('register') ?>" class="site-btn">Sign up free</a>
            <?php endif ?>
        </div>
    </div>
</header>

<main>
    <?= $this->renderSection('content') ?>
</main>

<footer class="site-footer">
    <div class="site-container d-flex flex-column flex-md-row justify-content-between gap-3">
        <div>
            <div class="site-logo">🐾 PawRecord</div>
            <div class="small mt-1"><?= esc($clinic ?? 'Veterinary Clinic') ?> · Pet Medical Record System</div>
        </div>
        <div class="d-flex gap-4 small">
            <a href="#features">Features</a>
            <a href="#how">How it works</a>
            <a href="#faq">FAQ</a>
            <a href="<?= site_url('login') ?>">Log in</a>
        </div>
    </div>
    <div class="site-container small mt-4 site-footer-note">
        © <?= date('Y') ?> PawRecord. For information only. In an emergency, call your veterinarian right away.
    </div>
</footer>

</body>
</html>
