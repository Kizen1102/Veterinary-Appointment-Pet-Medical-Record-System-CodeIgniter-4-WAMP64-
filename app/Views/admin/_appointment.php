<?php
/**
 * One appointment card with the admin's actions (assign a vet, cancel).
 * Used by the admin dashboard and the admin Appointments page:
 * view('admin/_appointment', ['a' => $appointment, 'vets' => $vets])
 */
use App\Models\AppointmentModel;
use App\Models\PetModel;

$isUpcoming = in_array($a['status'], ['pending', 'confirmed'], true) && $a['scheduled_at'] > date('Y-m-d H:i:s');
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
                    <?= PetModel::SPECIES[$a['species']] ?? '🐾' ?> <?= esc($a['pet_name']) ?>
                    — <?= AppointmentModel::TYPE_LABELS[$a['appointment_type']] ?>
                </div>
                <div class="small text-muted">
                    Owner: <?= esc($a['owner_name']) ?>
                    · <?= $a['vet_name'] ? esc($a['vet_name']) : '<span class="text-warning-emphasis">No vet yet</span>' ?>
                </div>
                <?php if ($a['reason']): ?>
                    <div class="small">“<?= esc($a['reason']) ?>”</div>
                <?php endif ?>
                <?php if ($a['status'] === 'cancelled' && $a['cancellation_reason']): ?>
                    <div class="small text-muted">Reason: <?= esc($a['cancellation_reason']) ?></div>
                <?php endif ?>
            </div>
        </div>
        <?= status_badge($a['status']) ?>
    </div>

    <?php if ($isUpcoming): ?>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <!-- Assign or change the vet -->
            <form action="<?= site_url('admin/appointments/' . $a['id'] . '/assign') ?>" method="post" class="d-flex gap-1">
                <?= csrf_field() ?>
                <select name="vet_id" class="form-select form-select-sm" required aria-label="Vet">
                    <option value="">Choose vet…</option>
                    <?php foreach ($vets as $vet): ?>
                        <option value="<?= $vet['id'] ?>" <?= (int) $a['vet_id'] === (int) $vet['id'] ? 'selected' : '' ?>><?= esc($vet['full_name']) ?></option>
                    <?php endforeach ?>
                </select>
                <button type="submit" class="btn btn-paw btn-sm"><?= $a['vet_id'] ? 'Change' : 'Assign' ?></button>
            </form>

            <!-- Cancel asks for a reason that the owner will see -->
            <form action="<?= site_url('admin/appointments/' . $a['id'] . '/cancel') ?>" method="post"
                  onsubmit="const r = prompt('Reason for the owner (optional):', 'The clinic is closed on this day'); if (r === null) return false; this.reason.value = r;">
                <?= csrf_field() ?>
                <input type="hidden" name="reason" value="">
                <button type="submit" class="btn btn-outline-danger btn-sm">Cancel</button>
            </form>
        </div>
    <?php endif ?>
</div>
