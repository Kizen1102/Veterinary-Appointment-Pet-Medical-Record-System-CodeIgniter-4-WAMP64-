<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= esc($title) ?></h1>
    <a href="<?= site_url('pets/new') ?>" class="btn btn-teal"><i class="bi bi-plus-lg"></i> Add Pet</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>Name</th><th>Species / Breed</th><th>Sex</th><th>Age</th>
                <?php if (! has_role('owner')): ?><th>Owner</th><?php endif ?>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pets as $pet): ?>
                <tr>
                    <td class="fw-semibold"><?= esc($pet['name']) ?></td>
                    <td><?= esc($pet['species']) ?><?= $pet['breed'] ? ' · ' . esc($pet['breed']) : '' ?></td>
                    <td><?= esc($pet['sex'] ?? '—') ?></td>
                    <td><?= esc(\App\Models\PetModel::ageLabel($pet['birth_date'])) ?></td>
                    <?php if (! has_role('owner')): ?><td><?= esc($pet['owner_name']) ?></td><?php endif ?>
                    <td class="text-end"><a href="<?= site_url('pets/' . $pet['id']) ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
            <?php endforeach ?>
            <?php if (empty($pets)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No pets registered yet.</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
