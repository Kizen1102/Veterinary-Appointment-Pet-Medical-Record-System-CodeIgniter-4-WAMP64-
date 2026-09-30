<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <!-- Greeting, like the prototype: "Good morning, Maria 👋" -->
    <div class="d-flex justify-content-between align-items-start mb-4 page-greeting">
        <div>
            <h1 class="brand-font"><?= greeting() ?>, <?= esc(explode(' ', $user['full_name'])[0]) ?> 👋</h1>
            <div class="page-date"><?= date('l, F j, Y') ?></div>
        </div>
        <div class="avatar"><?= esc(initials($user['full_name'])) ?></div>
    </div>

    <div class="paw-card">
        <span class="badge rounded-pill text-bg-success mb-2"><?= esc($pageName) ?></span>
        <p class="mb-1">You are signed in as <strong><?= esc($roleLabel) ?></strong> (<?= esc($user['email']) ?>).</p>
        <p class="text-muted mb-0">The dashboard content is built in the next step.</p>
    </div>
<?= $this->endSection() ?>
