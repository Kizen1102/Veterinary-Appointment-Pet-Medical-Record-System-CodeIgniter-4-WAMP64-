<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\PetModel; ?>

    <h1 class="brand-font mb-3">Pets</h1>

    <form action="<?= site_url('admin/pets') ?>" method="get" class="d-flex gap-2 mb-3">
        <input type="search" class="form-control" name="q" value="<?= esc($search) ?>" placeholder="Search pet or owner name">
        <button type="submit" class="btn btn-paw">Search</button>
    </form>

    <?php if ($pets === []): ?>
        <div class="paw-card text-center text-muted">No pets found.</div>
    <?php endif ?>

    <?php foreach ($pets as $p): ?>
        <div class="paw-card mb-2">
            <div class="d-flex align-items-center gap-3">
                <span class="patient-emoji"><?= PetModel::SPECIES[$p['species']] ?? '🐾' ?></span>
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?= esc($p['name']) ?></div>
                    <div class="small text-muted">
                        <?= esc(trim(($p['breed'] ?? '') . ' ' . $p['species'])) ?> · <?= esc(PetModel::ageLabel($p['birth_date'])) ?>
                        · Owner: <?= esc($p['owner_name']) ?>
                        · <?= $p['last_visit'] ? 'Last visit ' . date('M j, Y', strtotime($p['last_visit'])) : 'No visits yet' ?>
                    </div>
                </div>
            </div>

            <!-- Primary vet: the vet the owner usually sees -->
            <form action="<?= site_url('admin/pets/' . $p['id'] . '/vet') ?>" method="post" class="d-flex gap-1 mt-2">
                <?= csrf_field() ?>
                <select name="vet_id" class="form-select form-select-sm" aria-label="Primary vet">
                    <option value="">No primary vet</option>
                    <?php foreach ($vets as $vet): ?>
                        <option value="<?= $vet['id'] ?>" <?= (int) $p['primary_vet_id'] === (int) $vet['id'] ? 'selected' : '' ?>><?= esc($vet['full_name']) ?></option>
                    <?php endforeach ?>
                </select>
                <button type="submit" class="btn btn-outline-secondary btn-sm text-nowrap">Save vet</button>
            </form>
        </div>
    <?php endforeach ?>
<?= $this->endSection() ?>
