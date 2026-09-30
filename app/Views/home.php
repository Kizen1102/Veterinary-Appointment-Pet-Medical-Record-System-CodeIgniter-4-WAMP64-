<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <h2 class="brand-font">Hello, <?= esc($user['full_name']) ?>! 👋</h2>
    <p class="text-muted">
        You are signed in as <strong><?= esc($roleLabel) ?></strong> (<?= esc($user['email']) ?>).
    </p>
    <p class="text-muted">Your dashboard will be built in the next steps.</p>

    <form action="<?= site_url('logout') ?>" method="post">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-secondary w-100">Sign Out</button>
    </form>
<?= $this->endSection() ?>
