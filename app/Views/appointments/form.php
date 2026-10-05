<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\AppointmentModel; ?>
    <?php use App\Models\PetModel; ?>

    <a href="<?= site_url('appointments') ?>" class="text-decoration-none small">← My appointments</a>
    <h1 class="brand-font mt-2 mb-1">Book an appointment</h1>
    <p class="text-muted mb-4">
        Clinic hours: Monday to Saturday, 8:00 AM – 5:00 PM. The clinic will confirm your request.
    </p>

    <form action="<?= site_url('appointments') ?>" method="post" class="paw-card">
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
                <label class="form-label" for="appointment_type">Type of visit</label>
                <select class="form-select form-control" id="appointment_type" name="appointment_type" required>
                    <?php foreach (AppointmentModel::OWNER_TYPES as $type): ?>
                        <option value="<?= $type ?>" <?= old('appointment_type') === $type ? 'selected' : '' ?>>
                            <?= AppointmentModel::TYPE_LABELS[$type] ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label" for="vet_id">Veterinarian <span class="text-muted">(optional)</span></label>
                <select class="form-select form-control" id="vet_id" name="vet_id">
                    <option value="">Any available veterinarian</option>
                    <?php foreach ($vets as $vet): ?>
                        <option value="<?= $vet['id'] ?>" <?= (int) old('vet_id') === (int) $vet['id'] ? 'selected' : '' ?>>
                            <?= esc($vet['full_name']) ?><?= $vet['specialization'] ? ' · ' . esc($vet['specialization']) : '' ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="date">Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?= old('date') ?>" min="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="time">Time</label>
                <select class="form-select form-control" id="time" name="time" required>
                    <option value="">Choose…</option>
                    <?php foreach (AppointmentModel::timeSlots() as $value => $label): ?>
                        <option value="<?= $value ?>" <?= old('time') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label" for="reason">Reason for the visit <span class="text-muted">(optional)</span></label>
                <textarea class="form-control" id="reason" name="reason" rows="3" maxlength="500" placeholder="e.g. scratching a lot, yearly check-up"><?= old('reason') ?></textarea>
                <div class="form-text">✨ Describe the symptoms and our AI will check how urgent the visit is.</div>
            </div>
        </div>

        <button type="submit" class="btn btn-paw w-100 mt-4">Request Appointment</button>
    </form>
<?= $this->endSection() ?>
