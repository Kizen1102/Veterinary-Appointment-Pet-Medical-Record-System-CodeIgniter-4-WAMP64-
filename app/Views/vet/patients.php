<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\PetModel; ?>

    <h1 class="brand-font mb-3">Patients</h1>

    <!-- Search by pet or owner name (GET, so the search stays in the URL) -->
    <form action="<?= site_url('vet/patients') ?>" method="get" class="d-flex gap-2 mb-3">
        <input type="search" class="form-control" name="q" value="<?= esc($search) ?>" placeholder="Search pet or owner name">
        <button type="submit" class="btn btn-paw">Search</button>
    </form>

    <?php if ($search !== ''): ?>
        <p class="small text-muted">
            <?= count($patients) ?> result<?= count($patients) === 1 ? '' : 's' ?> for “<?= esc($search) ?>”
            · <a href="<?= site_url('vet/patients') ?>">Show all</a>
        </p>
    <?php endif ?>

    <?php if ($patients === []): ?>
        <div class="paw-card text-center text-muted">No patients found.</div>
    <?php endif ?>

    <?php foreach ($patients as $p): ?>
        <a href="<?= site_url('vet/patients/' . $p['id']) ?>" class="paw-card patient-row mb-2">
            <span class="patient-emoji"><?= PetModel::SPECIES[$p['species']] ?? '🐾' ?></span>
            <span class="flex-grow-1">
                <span class="fw-semibold d-block"><?= esc($p['name']) ?></span>
                <span class="small text-muted">
                    <?= esc(trim(($p['breed'] ?? '') . ' ' . $p['species'])) ?> · <?= esc(PetModel::ageLabel($p['birth_date'])) ?>
                    · Owner: <?= esc($p['owner_name']) ?>
                </span>
            </span>
            <span class="small text-muted text-end">
                <?= $p['last_visit'] ? 'Last visit<br>' . date('M j, Y', strtotime($p['last_visit'])) : 'No visits yet' ?>
            </span>
        </a>
    <?php endforeach ?>
<?= $this->endSection() ?>
