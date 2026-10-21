<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php $old = static fn (string $field, string $default = '') => esc((string) old($field, $default, false)); ?>

    <a href="<?= site_url('vet/patients/' . $pet['id']) ?>" class="text-decoration-none small">← <?= esc($pet['name']) ?></a>
    <h1 class="brand-font mt-2 mb-1">Record a vaccine</h1>
    <p class="text-muted mb-4">For <?= esc($pet['name']) ?>. The owner sees it in the Timeline, with the next due date.</p>

    <form action="<?= site_url('vet/patients/' . $pet['id'] . '/vaccines') ?>" method="post" class="paw-card">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-sm-8">
                <label class="form-label" for="vaccine_name">Vaccine</label>
                <!-- list= shows suggestions, but any name can be typed -->
                <input class="form-control" id="vaccine_name" name="vaccine_name" list="vaccine-names" value="<?= $old('vaccine_name') ?>" maxlength="100" required>
                <datalist id="vaccine-names">
                    <option value="Anti-rabies">
                    <option value="5-in-1 (DHPPi)">
                    <option value="6-in-1 (DHPPi + Lepto)">
                    <option value="Kennel cough (Bordetella)">
                    <option value="4-in-1 (FVRCP + Chlamydia)">
                    <option value="3-in-1 (FVRCP)">
                </datalist>
            </div>
            <div class="col-sm-4">
                <label class="form-label" for="dose_number">Dose no. <span class="text-muted">(optional)</span></label>
                <input type="number" min="1" max="19" class="form-control" id="dose_number" name="dose_number" value="<?= $old('dose_number') ?>">
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="date_given">Date given</label>
                <input type="date" class="form-control" id="date_given" name="date_given" value="<?= $old('date_given', date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="next_due_date">Next due date <span class="text-muted">(optional)</span></label>
                <input type="date" class="form-control" id="next_due_date" name="next_due_date" value="<?= $old('next_due_date') ?>">
                <!-- Quick buttons fill the date: date given + N days -->
                <div class="d-flex gap-1 mt-1">
                    <?php foreach (['+2 wks' => 14, '+3 wks' => 21, '+1 yr' => 365] as $label => $days): ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0" data-days="<?= $days ?>"><?= $label ?></button>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="batch_number">Batch / lot no. <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="batch_number" name="batch_number" value="<?= $old('batch_number') ?>" maxlength="50">
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="notes">Notes <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="notes" name="notes" value="<?= $old('notes') ?>" maxlength="255">
            </div>
        </div>

        <button type="submit" class="btn btn-paw w-100 mt-4">Save Vaccine</button>
    </form>

    <script>
        document.querySelectorAll('[data-days]').forEach((button) => {
            button.addEventListener('click', () => {
                const given = document.getElementById('date_given').value;
                if (!given) return;
                const date = new Date(given + 'T00:00:00');
                date.setDate(date.getDate() + Number(button.dataset.days));
                // Format as YYYY-MM-DD for the date box
                const pad = (n) => String(n).padStart(2, '0');
                document.getElementById('next_due_date').value = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
            });
        });
    </script>
<?= $this->endSection() ?>
