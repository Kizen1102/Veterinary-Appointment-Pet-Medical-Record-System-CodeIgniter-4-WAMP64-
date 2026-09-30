<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'PawRecord') ?> · PawRecord</title>

    <!-- Bootstrap 5 + Google Fonts + our own theme -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/css/pawrecord.css') ?>" rel="stylesheet">
</head>
<body>

<header class="auth-header">
    <div class="paw-badge">🐾</div>
    <h1 class="brand-font">PawRecord</h1>
    <div class="tagline">Veterinary Clinic &amp; Pet Medical Record System</div>
    <div class="clinic mt-1">PawCare Veterinary Clinic · Est. 2019</div>
</header>

<main class="auth-card">
    <?= $this->renderSection('content') ?>
</main>

<footer class="auth-footer">
    CodeIgniter 4 · PHP · MySQL · XAMPP
</footer>

</body>
</html>
