<?php
/**
 * One appointment card with the vet's action buttons.
 * Used by the vet dashboard and the vet Appointments page: view('vet/_appointment', ['a' => $appointment])
 */
use App\Models\AppointmentModel;
use App\Models\PetModel;

$vetId    = (int) current_user()['id'];
$isMine   = (int) $a['vet_id'] === $vetId;
$isFuture = $a['scheduled_at'] > date('Y-m-d H:i:s');
$action   = site_url('vet/appointments/' . $a['id'] . '/status');
?>
<div class="paw-card vet-appt is-<?= esc($a['status']) ?> mb-2">
    <div class="d-flex justify-content-between align-items-start gap-2">
        <div class="d-flex gap-3">
            <div class="vet-appt-time">
                <div class="fw-bold"><?= date('g:i A', strtotime($a['scheduled_at'])) ?></div>
                <div class="small text-muted"><?= date('M j', strtotime($a['scheduled_at'])) ?></div>
            </div>
            <div>
                <div class="fw-semibold">
                    <a href="<?= site_url('vet/patients/' . $a['pet_id']) ?>" class="text-decoration-none">
                        <?= PetModel::SPECIES[$a['species']] ?? '🐾' ?> <?= esc($a['pet_name']) ?>
                    </a>
                    — <?= AppointmentModel::TYPE_LABELS[$a['appointment_type']] ?>
                </div>
                <div class="small text-muted">
                    Owner: <?= esc($a['owner_name']) ?>
                    · <?= $a['vet_name'] ? ($isMine ? 'You' : esc($a['vet_name'])) : '<span class="text-warning-emphasis">No vet yet</span>' ?>
                </div>
                <?php if ($a['reason']): ?>
                    <div class="small">“<?= esc($a['reason']) ?>”</div>
                <?php endif ?>
            </div>
        </div>
        <?= status_badge($a['status']) ?>
    </div>

    <!-- Buttons depend on the status: pending → Confirm/Decline, confirmed (mine) → Complete/No-show -->
    <div class="d-flex flex-wrap gap-2 mt-2">
        <?php if ($a['status'] === 'pending' && $isFuture): ?>
            <form action="<?= $action ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="confirm">
                <button type="submit" class="btn btn-paw btn-sm"><?= $a['vet_id'] ? 'Confirm' : 'Confirm & assign to me' ?></button>
            </form>
        <?php endif ?>

        <?php if (in_array($a['status'], ['confirmed', 'completed'], true) && $isMine): ?>
            <!-- Step 11B: write the record of this visit (saving it also marks the visit completed) -->
            <a href="<?= site_url('vet/patients/' . $a['pet_id'] . '/records/new?appointment=' . $a['id']) ?>" class="btn btn-outline-secondary btn-sm">📝 Add record</a>
        <?php endif ?>

        <?php if ($a['status'] === 'confirmed' && $isMine): ?>
            <form action="<?= $action ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="complete">
                <button type="submit" class="btn btn-paw btn-sm">Mark completed</button>
            </form>
            <?php if (! $isFuture): ?>
                <form action="<?= $action ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="no_show">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">No-show</button>
                </form>
            <?php endif ?>
        <?php endif ?>

        <?php if (in_array($a['status'], ['pending', 'confirmed'], true) && $isFuture): ?>
            <!-- Decline asks for a short reason that the owner will see -->
            <form action="<?= $action ?>" method="post"
                  onsubmit="const r = prompt('Reason for the owner (optional):', 'The vet is not available at this time'); if (r === null) return false; this.reason.value = r;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="decline">
                <input type="hidden" name="reason" value="">
                <button type="submit" class="btn btn-outline-danger btn-sm">Decline</button>
            </form>
        <?php endif ?>
    </div>
</div>
