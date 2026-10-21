<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\MedicalRecordModel; ?>
    <?php use App\Models\PetHealthTimelineModel; ?>

    <?php
    // What was typed before a validation error (safe to print), else the default
    $old = static fn (string $field, string $default = '') => esc((string) old($field, $default, false));
    ?>

    <a href="<?= site_url('vet/patients/' . $pet['id']) ?>" class="text-decoration-none small">← <?= esc($pet['name']) ?></a>
    <h1 class="brand-font mt-2 mb-1">New medical record</h1>
    <p class="text-muted mb-4">
        For <?= esc($pet['name']) ?> (owner: <?= esc($pet['owner_name']) ?>).
        <?php if ($appointment): ?>
            Visit of <?= date('M j, g:i A', strtotime($appointment['scheduled_at'])) ?> — saving will mark it completed.
        <?php endif ?>
    </p>

    <form action="<?= site_url('vet/patients/' . $pet['id'] . '/records') ?>" method="post" class="paw-card">
        <?= csrf_field() ?>
        <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?? '' ?>">

        <div class="row g-3">
            <div class="col-sm-6">
                <label class="form-label" for="record_type">Type</label>
                <select class="form-select form-control" id="record_type" name="record_type" required>
                    <?php foreach (MedicalRecordModel::TYPES as $type): ?>
                        <option value="<?= $type ?>" <?= old('record_type') === $type ? 'selected' : '' ?>>
                            <?= PetHealthTimelineModel::EVENT_TYPES[$type]['label'] ?? ucfirst($type) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="visit_date">Visit date</label>
                <input type="date" class="form-control" id="visit_date" name="visit_date" value="<?= $old('visit_date', date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-12">
                <label class="form-label" for="title">Title</label>
                <input class="form-control" id="title" name="title" value="<?= $old('title') ?>" placeholder="Skin allergy check-up" maxlength="150" required>
            </div>

            <div class="col-12">
                <label class="form-label" for="chief_complaint">Chief complaint <span class="text-muted">(why they came)</span></label>
                <textarea class="form-control" id="chief_complaint" name="chief_complaint" rows="2"><?= $old('chief_complaint') ?></textarea>
            </div>

            <!-- Vitals -->
            <div class="col-6 col-md-3">
                <label class="form-label" for="weight_kg">Weight (kg)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="weight_kg" name="weight_kg" value="<?= $old('weight_kg') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="temperature_c">Temp (°C)</label>
                <input type="number" step="0.1" min="25" max="45" class="form-control" id="temperature_c" name="temperature_c" value="<?= $old('temperature_c') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="heart_rate_bpm">Heart rate</label>
                <input type="number" min="1" class="form-control" id="heart_rate_bpm" name="heart_rate_bpm" value="<?= $old('heart_rate_bpm') ?>" placeholder="bpm">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="respiratory_rate">Breathing</label>
                <input type="number" min="1" class="form-control" id="respiratory_rate" name="respiratory_rate" value="<?= $old('respiratory_rate') ?>" placeholder="per min">
            </div>

            <div class="col-12">
                <label class="form-label" for="findings">Findings</label>
                <textarea class="form-control" id="findings" name="findings" rows="2"><?= $old('findings') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="diagnosis">Diagnosis</label>
                <textarea class="form-control" id="diagnosis" name="diagnosis" rows="2"><?= $old('diagnosis') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="treatment">Treatment</label>
                <textarea class="form-control" id="treatment" name="treatment" rows="2"><?= $old('treatment') ?></textarea>
            </div>

            <div class="col-sm-5">
                <label class="form-label" for="follow_up_date">Follow-up date <span class="text-muted">(optional)</span></label>
                <input type="date" class="form-control" id="follow_up_date" name="follow_up_date" value="<?= $old('follow_up_date') ?>">
            </div>
            <div class="col-sm-7">
                <label class="form-label" for="follow_up_notes">Follow-up notes</label>
                <input class="form-control" id="follow_up_notes" name="follow_up_notes" value="<?= $old('follow_up_notes') ?>" placeholder="Recheck the skin" maxlength="255">
            </div>

            <div class="col-12">
                <label class="form-label" for="vet_notes">Private notes <span class="text-muted">(vets only, the owner does not see these)</span></label>
                <textarea class="form-control" id="vet_notes" name="vet_notes" rows="2"><?= $old('vet_notes') ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-paw w-100 mt-4">Save Record</button>
    </form>
<?= $this->endSection() ?>
