<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3">Book an Appointment</h1>

<?php if (empty($pets)): ?>
    <div class="alert alert-info">
        Please <a href="<?= site_url('pets/new') ?>">register a pet</a> before booking an appointment.
    </div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="card-body">
        <form action="<?= site_url('appointments') ?>" method="post" id="bookingForm">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="pet_id">Pet</label>
                    <select class="form-select" id="pet_id" name="pet_id" required>
                        <option value="">Select pet…</option>
                        <?php foreach ($pets as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (string) old('pet_id', $petId) === (string) $p['id'] ? 'selected' : '' ?>>
                                <?= esc($p['name']) ?> (<?= esc($p['species']) ?>)<?= has_role('owner') ? '' : ' — ' . esc($p['owner_name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="vet_id">Veterinarian</label>
                    <select class="form-select" id="vet_id" name="vet_id">
                        <option value="">Any available vet</option>
                        <?php foreach ($vets as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= (string) old('vet_id') === (string) $v['id'] ? 'selected' : '' ?>><?= esc($v['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="appointment_date">Date</label>
                    <input type="date" class="form-control" id="appointment_date" name="appointment_date"
                           min="<?= date('Y-m-d') ?>" value="<?= old('appointment_date') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="duration_minutes">Duration</label>
                    <select class="form-select" id="duration_minutes" name="duration_minutes">
                        <?php foreach (\App\Controllers\Appointments::DURATIONS as $d): ?>
                            <option value="<?= $d ?>" <?= (int) old('duration_minutes', $clinic->slotMinutes) === $d ? 'selected' : '' ?>><?= $d ?> minutes</option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="appointment_time">Time</label>
                    <select class="form-select" id="appointment_time" name="appointment_time" required>
                        <option value="">Pick a date first</option>
                    </select>
                    <div class="form-text">Clinic hours <?= esc($clinic->openTime) ?>–<?= esc($clinic->closeTime) ?></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="reason">Reason for visit / symptoms</label>
                    <textarea class="form-control" id="reason" name="reason" rows="3" required
                              placeholder="e.g. Vomiting since yesterday, not eating, lethargic"><?= old('reason') ?></textarea>
                    <div class="form-text"><i class="bi bi-stars"></i> Our AI assistant reviews the symptoms to help the clinic prioritise urgent cases.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Additional notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"><?= old('notes') ?></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-teal">Book appointment</button>
                <a href="<?= site_url('appointments') ?>" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(() => {
    const date = document.getElementById('appointment_date');
    if (!date) return;
    const vet = document.getElementById('vet_id');
    const duration = document.getElementById('duration_minutes');
    const time = document.getElementById('appointment_time');
    const previous = <?= json_encode(old('appointment_time')) ?>;

    async function loadSlots() {
        if (!date.value) return;
        time.innerHTML = '<option value="">Loading…</option>';
        const params = new URLSearchParams({date: date.value, vet_id: vet.value, duration: duration.value});
        const res = await fetch('<?= site_url('appointments/slots') ?>?' + params, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
        const data = await res.json();
        const slots = data.slots || [];
        time.innerHTML = slots.length ? '' : '<option value="">No free slots on this day</option>';
        for (const s of slots) {
            const [h, m] = s.split(':').map(Number);
            const label = ((h + 11) % 12 + 1) + ':' + String(m).padStart(2, '0') + (h < 12 ? ' AM' : ' PM');
            time.add(new Option(label, s, false, s === previous));
        }
    }

    [date, vet, duration].forEach(el => el.addEventListener('change', loadSlots));
    loadSlots();
})();
</script>
<?= $this->endSection() ?>
