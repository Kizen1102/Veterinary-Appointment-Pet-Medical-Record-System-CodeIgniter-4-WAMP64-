<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <div class="page-greeting mb-4">
        <h1 class="brand-font"><?= greeting() ?>, <?= esc(explode(' ', $user['full_name'])[0]) ?> 👋</h1>
        <div class="page-date"><?= date('l, F j, Y') ?></div>
    </div>

    <div class="paw-card text-center py-5">
        <div class="display-4 mb-2">🐾</div>
        <h2 class="brand-font h4">Add your first pet</h2>
        <p class="text-muted">Register your pet to start keeping its health records, medications and journal in one place.</p>
        <a href="<?= site_url('pets/new') ?>" class="btn btn-paw px-4">+ Add Pet</a>
    </div>
<?= $this->endSection() ?>
