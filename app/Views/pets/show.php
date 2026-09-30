<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-heart text-teal"></i> <?= esc($pet['name']) ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= site_url('pets/' . $pet['id'] . '/edit') ?>" class="btn btn-outline-secondary">Edit</a>
        <form action="<?= site_url('pets/' . $pet['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Delete this pet and all its records?')">
            <?= csrf_field() ?>
            <button class="btn btn-outline-danger">Delete</button>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Profile</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><span class="text-muted">Species:</span> <?= esc($pet['species']) ?></li>
                <li class="list-group-item"><span class="text-muted">Breed:</span> <?= esc($pet['breed'] ?? '—') ?></li>
                <li class="list-group-item"><span class="text-muted">Sex:</span> <?= esc($pet['sex'] ?? '—') ?></li>
                <li class="list-group-item"><span class="text-muted">Age:</span> <?= esc(\App\Models\PetModel::ageLabel($pet['birth_date'])) ?></li>
                <li class="list-group-item"><span class="text-muted">Weight:</span> <?= $pet['weight_kg'] ? esc($pet['weight_kg']) . ' kg' : '—' ?></li>
                <li class="list-group-item"><span class="text-muted">Color:</span> <?= esc($pet['color'] ?? '—') ?></li>
                <li class="list-group-item"><span class="text-muted">Allergies:</span>
                    <?= $pet['allergies'] ? '<span class="text-danger">' . esc($pet['allergies']) . '</span>' : 'None known' ?></li>
                <li class="list-group-item"><span class="text-muted">Owner:</span> <?= esc($owner['name'] ?? '—') ?>
                    <?php if (! empty($owner['phone'])): ?><br><small><?= esc($owner['phone']) ?></small><?php endif ?></li>
            </ul>
            <?php if ($pet['notes']): ?>
                <div class="card-body small text-muted"><?= nl2br(esc($pet['notes'])) ?></div>
            <?php endif ?>
        </div>
    </div>
    <div class="col-lg-8">
        <?= $this->include('pets/_history') ?>
    </div>
</div>
<?= $this->endSection() ?>
