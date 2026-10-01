<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\MedicationModel; ?>
    <?php use App\Models\PetModel; ?>

    <a href="<?= site_url('meds') ?>" class="text-decoration-none small">← Medications</a>
    <h1 class="brand-font mt-2 mb-1">Add a medication</h1>
    <p class="text-muted mb-4">Copy the details from your vet's prescription. Each dose will remind you at its time.</p>

    <form action="<?= site_url('meds') ?>" method="post" class="paw-card">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-sm-6">
                <label class="form-label" for="pet_id">Pet</label>
                <select class="form-select form-control" id="pet_id" name="pet_id" required>
                    <?php foreach ($pets as $pet): ?>
                        <?php $chosen = (int) (old('pet_id') ?? $selectedPet) === (int) $pet['id']; ?>
                        <option value="<?= $pet['id'] ?>" <?= $chosen ? 'selected' : '' ?>>
                            <?= PetModel::SPECIES[$pet['species']] ?? '🐾' ?> <?= esc($pet['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="name">Medicine name</label>
                <input class="form-control" id="name" name="name" value="<?= old('name') ?>" placeholder="Cephalexin" required>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="dosage">Dosage</label>
                <input class="form-control" id="dosage" name="dosage" value="<?= old('dosage') ?>" placeholder="75mg, 1 tablet, 2 drops" required>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="form">Form</label>
                <select class="form-select form-control" id="form" name="form" required>
                    <?php foreach (MedicationModel::FORMS as $form): ?>
                        <option value="<?= $form ?>" <?= old('form') === $form ? 'selected' : '' ?>><?= ucfirst($form) ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Dose times <span class="text-muted">(one box per dose each day)</span></label>
                <div class="row g-2">
                    <?php $oldTimes = old('times') ?? ['08:00', '', '']; ?>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="col-4">
                            <input type="time" class="form-control" name="times[]" value="<?= esc($oldTimes[$i] ?? '') ?>" <?= $i === 0 ? 'required' : '' ?>>
                        </div>
                    <?php endfor ?>
                </div>
                <div class="form-text">Example: once a day = 8:00 AM only. Every 12 hours = 8:00 AM and 8:00 PM.</div>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="start_date">Start date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= old('start_date') ?? date('Y-m-d') ?>" required>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="end_date">End date <span class="text-muted">(optional)</span></label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= old('end_date') ?>">
            </div>

            <div class="col-12">
                <label class="form-label" for="instructions">Instructions <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="instructions" name="instructions" value="<?= old('instructions') ?>" placeholder="with food">
            </div>
        </div>

        <button type="submit" class="btn btn-paw w-100 mt-4">Save Medication</button>
    </form>
<?= $this->endSection() ?>
