<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Appointments</div>
    <ul class="list-group list-group-flush">
        <?php foreach ($appointments as $a): ?>
            <li class="list-group-item d-flex justify-content-between">
                <span><?= fmt_date($a['appointment_date']) ?> <?= date('g:i A', strtotime($a['appointment_time'])) ?> — <?= esc($a['reason']) ?></span>
                <?= status_badge($a['status']) ?>
            </li>
        <?php endforeach ?>
        <?php if (empty($appointments)): ?>
            <li class="list-group-item text-muted">No appointments yet.</li>
        <?php endif ?>
    </ul>
</div>
