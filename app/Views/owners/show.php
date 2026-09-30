<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($owner['name']) ?></h1>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="bi bi-envelope"></i> <?= esc($owner['email']) ?></li>
                <li class="list-group-item"><i class="bi bi-telephone"></i> <?= esc($owner['phone'] ?? '—') ?></li>
                <li class="list-group-item"><i class="bi bi-geo-alt"></i> <?= esc($owner['address'] ?? '—') ?></li>
                <li class="list-group-item text-muted small">Client since <?= fmt_date($owner['created_at']) ?></li>
            </ul>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Pets</span>
                <a class="btn btn-sm btn-teal" href="<?= site_url('pets/new?owner_id=' . $owner['id']) ?>">Add pet</a>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($pets as $p): ?>
                    <a class="list-group-item list-group-item-action" href="<?= site_url('pets/' . $p['id']) ?>">
                        <strong><?= esc($p['name']) ?></strong> — <?= esc($p['species']) ?><?= $p['breed'] ? ' · ' . esc($p['breed']) : '' ?>
                    </a>
                <?php endforeach ?>
                <?php if (empty($pets)): ?>
                    <li class="list-group-item text-muted">No pets registered.</li>
                <?php endif ?>
            </ul>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
