<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <?php if (session('success')): ?>
        <div class="alert alert-success"><?= esc(session('success')) ?></div>
    <?php endif ?>

    <h2 class="brand-font">Setup complete</h2>
    <p class="text-muted">The Clinic Staff (Admin) account already exists, so this page is now locked.</p>
    <a href="<?= site_url('login') ?>" class="btn btn-paw w-100">Go to Sign In</a>
<?= $this->endSection() ?>
