<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\AppointmentModel; ?>
    <?php use App\Models\PetModel; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="brand-font mb-0">Appointments</h1>
        <a href="<?= site_url('appointments/new') ?>" class="btn btn-paw btn-sm">+ Book</a>
    </div>

    <!-- Step 15: AI urgency check of the request just made (shown once) -->
    <?php $triage = session('triage'); ?>
    <?php if ($triage): ?>
        <?php $isUrgent = in_array($triage['level'], ['emergency', 'high'], true); ?>
        <div class="alert-card <?= $triage['level'] === 'emergency' ? 'is-red' : ($isUrgent ? 'is-orange' : 'is-green') ?> mb-4">
            <span class="alert-icon"><?= $triage['level'] === 'emergency' ? '🚨' : ($isUrgent ? '⚠️' : '✨') ?></span>
            <div class="flex-grow-1">
                <div class="fw-semibold d-flex flex-wrap align-items-center gap-2">
                    Urgency check <?= triage_badge($triage['level']) ?>
                </div>
                <div class="small"><?= esc($triage['advice']) ?></div>
                <?php if ($triage['level'] === 'emergency'): ?>
                    <div class="small fw-bold mt-1">Do not wait for the appointment: call the clinic or go to the nearest emergency vet now.</div>
                <?php endif ?>
                <div class="small text-muted mt-1">
                    <?= $triage['source'] === 'ai' ? 'Checked by AI' : 'Checked by the built-in symptom checker' ?>.
                    This is not a diagnosis.
                </div>
            </div>
        </div>
    <?php endif ?>

    <!-- Upcoming -->
    <h3 class="brand-font h5 mb-3">Upcoming</h3>

    <?php if ($upcoming === []): ?>
        <div class="paw-card text-center text-muted mb-4">
            No upcoming appointments.
            <a href="<?= site_url('appointments/new') ?>">Book one now</a>
        </div>
    <?php endif ?>

    <?php foreach ($upcoming as $a): ?>
        <div class="paw-card appointment-card mb-3">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="fw-bold"><?= date('D, M j, Y · g:i A', strtotime($a['scheduled_at'])) ?></div>
                    <div>
                        <?= PetModel::SPECIES[$a['species']] ?? '🐾' ?> <?= esc($a['pet_name']) ?>
                        — <?= AppointmentModel::TYPE_LABELS[$a['appointment_type']] ?>
                    </div>
                    <div class="small text-muted">
                        <?= $a['vet_name'] ? '🩺 ' . esc($a['vet_name']) : 'Any available veterinarian' ?>
                    </div>
                    <?php if ($a['reason']): ?>
                        <div class="small text-muted">Reason: <?= esc($a['reason']) ?> <?= triage_badge($a['triage_level']) ?></div>
                    <?php endif ?>
                </div>
                <?= status_badge($a['status']) ?>
            </div>

            <form action="<?= site_url('appointments/' . $a['id'] . '/cancel') ?>" method="post" class="mt-2"
                  onsubmit="return confirm('Cancel this appointment?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">Cancel appointment</button>
            </form>
        </div>
    <?php endforeach ?>

    <!-- History -->
    <?php if ($past !== []): ?>
        <h3 class="brand-font h5 mt-4 mb-3">History</h3>

        <?php foreach ($past as $a): ?>
            <div class="paw-card appointment-card is-past mb-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold"><?= date('M j, Y · g:i A', strtotime($a['scheduled_at'])) ?></div>
                        <div class="small">
                            <?= esc($a['pet_name']) ?> — <?= AppointmentModel::TYPE_LABELS[$a['appointment_type']] ?>
                            <?= $a['vet_name'] ? '· ' . esc($a['vet_name']) : '' ?>
                        </div>
                    </div>
                    <?= status_badge($a['status']) ?>
                </div>
            </div>
        <?php endforeach ?>
    <?php endif ?>
<?= $this->endSection() ?>
