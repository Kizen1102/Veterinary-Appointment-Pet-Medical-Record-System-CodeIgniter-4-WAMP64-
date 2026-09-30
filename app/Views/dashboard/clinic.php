<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h1 class="h3 mb-0">Clinic Dashboard</h1>
        <p class="text-muted mb-0"><?= date('l, F j, Y') ?></p>
    </div>
    <a href="<?= site_url('appointments/new') ?>" class="btn btn-teal"><i class="bi bi-calendar-plus"></i> New appointment</a>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['Today\'s appointments', $stats['today'], 'calendar-day', 'appointments?date=' . date('Y-m-d')],
        ['Pending requests', $stats['pending'], 'hourglass-split', 'appointments?status=pending'],
        ['Registered pets', $stats['pets'], 'heart', 'pets'],
        ['Pet owners', $stats['owners'], 'people', 'owners'],
    ] as [$label, $value, $icon, $link]): ?>
        <div class="col-6 col-lg-3">
            <a href="<?= site_url($link) ?>" class="card stat-card shadow-sm text-decoration-none h-100">
                <div class="card-body">
                    <div class="text-muted small"><i class="bi bi-<?= $icon ?>"></i> <?= $label ?></div>
                    <div class="display-6 text-teal"><?= (int) $value ?></div>
                </div>
            </a>
        </div>
    <?php endforeach ?>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">
                Today's schedule <small class="text-muted fw-normal">— most urgent first (AI triage)</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <tbody>
                    <?php foreach ($today as $a): ?>
                        <tr>
                            <td class="text-nowrap"><?= date('g:i A', strtotime($a['appointment_time'])) ?></td>
                            <td><strong><?= esc($a['pet_name']) ?></strong> <small class="text-muted"><?= esc($a['species']) ?></small><br>
                                <small class="text-muted"><?= esc(mb_strimwidth($a['reason'], 0, 60, '…')) ?></small></td>
                            <td><?= esc($a['vet_name'] ?? 'Unassigned') ?></td>
                            <td><?= status_badge($a['triage_level']) ?></td>
                            <td><?= status_badge($a['status']) ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= site_url('appointments/' . $a['id']) ?>">Open</a></td>
                        </tr>
                    <?php endforeach ?>
                    <?php if (empty($today)): ?>
                        <tr><td class="text-muted">No appointments today.</td></tr>
                    <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Pending requests <small class="text-muted fw-normal">— awaiting confirmation</small></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($pending as $a): ?>
                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= site_url('appointments/' . $a['id']) ?>">
                        <span><?= status_badge($a['triage_level']) ?>
                            <strong class="ms-1"><?= esc($a['pet_name']) ?></strong> (<?= esc($a['owner_name']) ?>) —
                            <?= fmt_date($a['appointment_date'], 'M d') ?> <?= date('g:i A', strtotime($a['appointment_time'])) ?></span>
                        <small class="text-muted text-truncate ms-2" style="max-width: 40%"><?= esc($a['reason']) ?></small>
                    </a>
                <?php endforeach ?>
                <?php if (empty($pending)): ?>
                    <li class="list-group-item text-muted">No pending requests.</li>
                <?php endif ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Vaccinations due (14 days)</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($vaccinations as $v): ?>
                    <a class="list-group-item list-group-item-action d-flex justify-content-between" href="<?= site_url('pets/' . $v['pet_id']) ?>">
                        <span><strong><?= esc($v['pet_name']) ?></strong> — <?= esc($v['vaccine_name']) ?><br><small class="text-muted"><?= esc($v['owner_name']) ?></small></span>
                        <span class="<?= $v['next_due_date'] < date('Y-m-d') ? 'text-danger fw-semibold' : '' ?>"><?= fmt_date($v['next_due_date'], 'M d') ?></span>
                    </a>
                <?php endforeach ?>
                <?php if (empty($vaccinations)): ?>
                    <li class="list-group-item text-muted">Nothing due.</li>
                <?php endif ?>
            </ul>
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Follow-ups this week</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($followUps as $f): ?>
                    <a class="list-group-item list-group-item-action d-flex justify-content-between" href="<?= site_url('records/' . $f['id']) ?>">
                        <span><strong><?= esc($f['pet_name']) ?></strong> — <?= esc($f['diagnosis'] ?: 'Follow-up') ?></span>
                        <span><?= fmt_date($f['follow_up_date'], 'M d') ?></span>
                    </a>
                <?php endforeach ?>
                <?php if (empty($followUps)): ?>
                    <li class="list-group-item text-muted">No follow-ups scheduled.</li>
                <?php endif ?>
            </ul>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
