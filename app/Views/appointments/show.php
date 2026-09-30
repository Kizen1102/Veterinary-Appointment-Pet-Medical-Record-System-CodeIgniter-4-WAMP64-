<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Appointment #<?= $appt['id'] ?> <?= status_badge($appt['status']) ?></h1>
    <div class="d-flex gap-2">
    <?php if (has_role('admin', 'vet') && $appt['status'] !== 'cancelled'): ?>
        <a class="btn btn-teal" href="<?= site_url('records/new?appointment_id=' . $appt['id']) ?>"><i class="bi bi-journal-medical"></i> Write medical record</a>
    <?php endif ?>
    <?php if (! in_array($appt['status'], ['completed', 'cancelled'], true)): ?>
        <form action="<?= site_url('appointments/' . $appt['id'] . '/cancel') ?>" method="post" onsubmit="return confirm('Cancel this appointment?')">
            <?= csrf_field() ?>
            <button class="btn btn-outline-danger">Cancel appointment</button>
        </form>
    <?php endif ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><span class="text-muted">When:</span>
                    <?= fmt_date($appt['appointment_date'], 'l, M d, Y') ?> at <?= date('g:i A', strtotime($appt['appointment_time'])) ?>
                    (<?= (int) $appt['duration_minutes'] ?> min)</li>
                <li class="list-group-item"><span class="text-muted">Pet:</span>
                    <a href="<?= site_url('pets/' . $appt['pet_id']) ?>"><?= esc($appt['pet_name']) ?></a> (<?= esc($appt['species']) ?>)</li>
                <li class="list-group-item"><span class="text-muted">Owner:</span> <?= esc($appt['owner_name']) ?></li>
                <li class="list-group-item"><span class="text-muted">Veterinarian:</span> <?= esc($appt['vet_name'] ?? 'Unassigned') ?></li>
                <li class="list-group-item"><span class="text-muted">Reason:</span><br><?= nl2br(esc($appt['reason'])) ?></li>
                <?php if ($appt['notes']): ?>
                    <li class="list-group-item"><span class="text-muted">Notes:</span><br><?= nl2br(esc($appt['notes'])) ?></li>
                <?php endif ?>
            </ul>
        </div>
        <?php if ($appt['triage_level']): ?>
            <div class="card ai-box shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6"><i class="bi bi-stars"></i> AI triage: <?= status_badge($appt['triage_level']) ?></h2>
                    <p class="mb-1 small"><?= nl2br(esc($appt['triage_notes'] ?? '')) ?></p>
                    <p class="mb-0 small text-muted fst-italic">AI suggestions support, but never replace, a veterinarian's judgement.</p>
                </div>
            </div>
        <?php endif ?>
    </div>

    <?php if (! has_role('owner')): ?>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Manage appointment</div>
            <div class="card-body">
                <form action="<?= site_url('appointments/' . $appt['id']) ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach (\App\Models\AppointmentModel::STATUSES as $s): ?>
                                    <option value="<?= $s ?>" <?= $appt['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="vet_id">Veterinarian</label>
                            <select class="form-select" id="vet_id" name="vet_id">
                                <option value="">Unassigned</option>
                                <?php foreach ($vets as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= (string) $appt['vet_id'] === (string) $v['id'] ? 'selected' : '' ?>><?= esc($v['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="appointment_date">Date</label>
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" value="<?= esc($appt['appointment_date']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="appointment_time">Time</label>
                            <input type="time" class="form-control" id="appointment_time" name="appointment_time" step="900" value="<?= esc(substr($appt['appointment_time'], 0, 5)) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="duration_minutes">Minutes</label>
                            <select class="form-select" id="duration_minutes" name="duration_minutes">
                                <?php foreach (\App\Controllers\Appointments::DURATIONS as $d): ?>
                                    <option value="<?= $d ?>" <?= (int) $appt['duration_minutes'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"><?= esc($appt['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button class="btn btn-teal mt-3">Save changes</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
