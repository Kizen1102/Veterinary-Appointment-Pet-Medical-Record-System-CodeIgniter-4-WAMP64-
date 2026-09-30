<div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Medical history</span>
        <?php if (has_role('admin', 'vet')): ?>
            <a class="btn btn-sm btn-teal" href="<?= site_url('records/new?pet_id=' . $pet['id']) ?>"><i class="bi bi-plus-lg"></i> New record</a>
        <?php endif ?>
    </div>
    <ul class="list-group list-group-flush">
        <?php foreach ($records as $r): ?>
            <a class="list-group-item list-group-item-action" href="<?= site_url('records/' . $r['id']) ?>">
                <div class="d-flex justify-content-between">
                    <strong><?= esc($r['diagnosis'] ?: 'Consultation') ?></strong>
                    <small class="text-muted"><?= fmt_date($r['visit_date']) ?></small>
                </div>
                <small class="text-muted"><?= esc($r['vet_name'] ?? '') ?><?= $r['treatment'] ? ' · ' . esc(mb_strimwidth($r['treatment'], 0, 80, '…')) : '' ?></small>
            </a>
        <?php endforeach ?>
        <?php if (empty($records)): ?>
            <li class="list-group-item text-muted">No medical records yet.</li>
        <?php endif ?>
    </ul>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Vaccinations</span>
        <?php if (has_role('admin', 'vet', 'staff')): ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('pets/' . $pet['id'] . '/vaccinations/new') ?>"><i class="bi bi-plus-lg"></i> Add</a>
        <?php endif ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Vaccine</th><th>Given</th><th>Next due</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($vaccinations as $v): ?>
                <?php $overdue = $v['next_due_date'] && $v['next_due_date'] < date('Y-m-d'); ?>
                <tr>
                    <td><?= esc($v['vaccine_name']) ?></td>
                    <td><?= fmt_date($v['date_given']) ?></td>
                    <td class="<?= $overdue ? 'text-danger fw-semibold' : '' ?>"><?= fmt_date($v['next_due_date']) ?><?= $overdue ? ' (overdue)' : '' ?></td>
                    <td class="text-end">
                        <?php if (has_role('admin', 'vet')): ?>
                            <form action="<?= site_url('vaccinations/' . $v['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Remove this entry?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                            </form>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            <?php if (empty($vaccinations)): ?>
                <tr><td colspan="4" class="text-muted">No vaccinations recorded.</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Appointments</span>
        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('appointments/new?pet_id=' . $pet['id']) ?>"><i class="bi bi-calendar-plus"></i> Book</a>
    </div>
    <ul class="list-group list-group-flush">
        <?php foreach ($appointments as $a): ?>
            <a class="list-group-item list-group-item-action d-flex justify-content-between" href="<?= site_url('appointments/' . $a['id']) ?>">
                <span><?= fmt_date($a['appointment_date']) ?> <?= date('g:i A', strtotime($a['appointment_time'])) ?> — <?= esc($a['reason']) ?></span>
                <?= status_badge($a['status']) ?>
            </a>
        <?php endforeach ?>
        <?php if (empty($appointments)): ?>
            <li class="list-group-item text-muted">No appointments yet.</li>
        <?php endif ?>
    </ul>
</div>
