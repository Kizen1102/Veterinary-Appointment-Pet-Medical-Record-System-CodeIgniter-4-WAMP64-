<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <?php if (session('success')): ?>
        <div class="alert alert-success"><?= esc(session('success')) ?></div>
    <?php endif ?>

    <h2 class="brand-font">Setup complete</h2>
    <p class="text-muted">The Clinic Staff (Admin) account already exists, so this page is now locked.</p>
    <p class="text-muted">The Login page comes in Lesson 3.3.</p>
<?= $this->endSection() ?>
