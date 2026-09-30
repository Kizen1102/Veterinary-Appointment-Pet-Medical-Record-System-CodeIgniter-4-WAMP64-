<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1 class="h3">Dashboard</h1>
<p class="text-muted">Welcome, <?= esc(current_user()['name']) ?>.</p>
<?= $this->endSection() ?>
