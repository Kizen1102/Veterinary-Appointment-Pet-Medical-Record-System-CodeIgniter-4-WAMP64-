<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h1 class="h3 mb-0">Hello, <?= esc(current_user()['name']) ?> 👋</h1>
        <p class="text-muted mb-0">Here's what's happening with your pets.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= site_url('ai/symptom-checker') ?>" class="btn btn-outline-secondary"><i class="bi bi-stars"></i> Check symptoms</a>
        <a href="<?= site_url('appointments/new') ?>" class="btn btn-teal"><i class="bi bi-calendar-plus"></i> Book appointment</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Upcoming appointments</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($upcoming as $a): ?>
                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= site_url('appointments/' . $a['id']) ?>">
                        <span>
                            <strong><?= esc($a['pet_name']) ?></strong> — <?= fmt_date($a['appointment_date'], 'D, M d') ?> at <?= date('g:i A', strtotime($a['appointment_time'])) ?>
                            <br><small class="text-muted"><?= esc($a['vet_name'] ?? 'Vet to be assigned') ?> · <?= esc(mb_strimwidth($a['reason'], 0, 60, '…')) ?></small>
                        </span>
                        <?= status_badge($a['status']) ?>
                    </a>
                <?php endforeach ?>
                <?php if (empty($upcoming)): ?>
                    <li class="list-group-item text-muted">No upcoming appointments.</li>
                <?php endif ?>
            </ul>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Vaccinations due (next 30 days)</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($vaccinations as $v): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><strong><?= esc($v['pet_name']) ?></strong> — <?= esc($v['vaccine_name']) ?></span>
                        <span class="<?= $v['next_due_date'] < date('Y-m-d') ? 'text-danger fw-semibold' : '' ?>"><?= fmt_date($v['next_due_date']) ?></span>
                    </li>
                <?php endforeach ?>
                <?php if (empty($vaccinations)): ?>
                    <li class="list-group-item text-muted">All vaccinations are up to date.</li>
                <?php endif ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">My pets</span>
                <a href="<?= site_url('pets/new') ?>" class="btn btn-sm btn-outline-secondary">Add pet</a>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($pets as $p): ?>
                    <a class="list-group-item list-group-item-action" href="<?= site_url('pets/' . $p['id']) ?>">
                        <i class="bi bi-heart text-teal"></i> <strong><?= esc($p['name']) ?></strong>
                        <small class="text-muted"><?= esc($p['species']) ?> · <?= esc(\App\Models\PetModel::ageLabel($p['birth_date'])) ?></small>
                    </a>
                <?php endforeach ?>
                <?php if (empty($pets)): ?>
                    <li class="list-group-item text-muted">You haven't added any pets yet.</li>
                <?php endif ?>
            </ul>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
