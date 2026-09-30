<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $isEdit = ! empty($record['id']); ?>
<h1 class="h3 mb-1"><?= esc($title) ?></h1>
<p class="text-muted"><?= esc($pet['name']) ?> · <?= esc($pet['species']) ?><?= $pet['breed'] ? ' · ' . esc($pet['breed']) : '' ?>
    <?php if ($pet['allergies']): ?><span class="badge text-bg-danger ms-2">Allergies: <?= esc($pet['allergies']) ?></span><?php endif ?></p>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="<?= site_url($isEdit ? 'records/' . $record['id'] : 'records') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
            <input type="hidden" name="appointment_id" value="<?= esc($record['appointment_id'] ?? '') ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="visit_date">Visit date</label>
                    <input type="date" class="form-control" id="visit_date" name="visit_date" value="<?= old('visit_date', $record['visit_date'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="weight_kg">Weight (kg)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="weight_kg" name="weight_kg" value="<?= old('weight_kg', $record['weight_kg'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="temperature_c">Temperature (°C)</label>
                    <input type="number" step="0.1" min="20" max="45" class="form-control" id="temperature_c" name="temperature_c" value="<?= old('temperature_c', $record['temperature_c'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="follow_up_date">Follow-up date</label>
                    <input type="date" class="form-control" id="follow_up_date" name="follow_up_date" value="<?= old('follow_up_date', $record['follow_up_date'] ?? '') ?>">
                </div>
                <?php foreach (['symptoms' => 'Symptoms / history', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment', 'prescription' => 'Prescription', 'notes' => 'Notes'] as $field => $label): ?>
                    <div class="col-md-6">
                        <label class="form-label" for="<?= $field ?>"><?= $label ?></label>
                        <textarea class="form-control" id="<?= $field ?>" name="<?= $field ?>" rows="3"><?= old($field, $record[$field] ?? '') ?></textarea>
                    </div>
                <?php endforeach ?>
            </div>
            <div class="mt-4">
                <button class="btn btn-teal">Save record</button>
                <a href="<?= site_url('pets/' . $pet['id']) ?>" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
