<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Appointments</h1>
    <a href="<?= site_url('appointments/new') ?>" class="btn btn-teal"><i class="bi bi-calendar-plus"></i> Book appointment</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-auto">
        <input type="date" class="form-control" name="date" value="<?= esc($filters['date']) ?>">
    </div>
    <div class="col-auto">
        <select class="form-select" name="status">
            <option value="">All statuses</option>
            <?php foreach (\App\Models\AppointmentModel::STATUSES as $s): ?>
                <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <?php if (has_role('vet')): ?>
        <div class="col-auto form-check align-self-center ms-2">
            <input class="form-check-input" type="checkbox" name="scope" value="mine" id="scope" <?= $filters['scope'] === 'mine' ? 'checked' : '' ?>>
            <label class="form-check-label" for="scope">Only my patients</label>
        </div>
    <?php endif ?>
    <div class="col-auto">
        <button class="btn btn-outline-secondary">Filter</button>
        <a class="btn btn-link" href="<?= site_url('appointments') ?>">Reset</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr><th>Date</th><th>Time</th><th>Pet</th><th>Owner</th><th>Vet</th><th>Reason</th><th>Triage</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $a): ?>
                <tr>
                    <td class="text-nowrap"><?= fmt_date($a['appointment_date']) ?></td>
                    <td class="text-nowrap"><?= date('g:i A', strtotime($a['appointment_time'])) ?></td>
                    <td><?= esc($a['pet_name']) ?> <small class="text-muted"><?= esc($a['species']) ?></small></td>
                    <td><?= esc($a['owner_name']) ?></td>
                    <td><?= esc($a['vet_name'] ?? 'Unassigned') ?></td>
                    <td class="text-truncate" style="max-width: 220px"><?= esc($a['reason']) ?></td>
                    <td><?= status_badge($a['triage_level']) ?></td>
                    <td><?= status_badge($a['status']) ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= site_url('appointments/' . $a['id']) ?>">Open</a></td>
                </tr>
            <?php endforeach ?>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No appointments found.</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
