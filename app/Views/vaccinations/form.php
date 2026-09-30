<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3">Record Vaccination — <?= esc($pet['name']) ?></h1>
<div class="card shadow-sm">
    <div class="card-body">
        <form action="<?= site_url('pets/' . $pet['id'] . '/vaccinations') ?>" method="post">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="vaccine_name">Vaccine</label>
                    <input class="form-control" id="vaccine_name" name="vaccine_name" list="vaccineList" value="<?= old('vaccine_name') ?>" required>
                    <datalist id="vaccineList">
                        <option>Anti-Rabies</option><option>DHPP (5-in-1)</option><option>DHLPP (6-in-1)</option>
                        <option>Bordetella</option><option>Leptospirosis</option><option>FVRCP</option><option>FeLV</option>
                    </datalist>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="batch_number">Batch / lot no.</label>
                    <input class="form-control" id="batch_number" name="batch_number" value="<?= old('batch_number') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="date_given">Date given</label>
                    <input type="date" class="form-control" id="date_given" name="date_given" value="<?= old('date_given', date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="next_due_date">Next due</label>
                    <input type="date" class="form-control" id="next_due_date" name="next_due_date" value="<?= old('next_due_date', date('Y-m-d', strtotime('+1 year'))) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"><?= old('notes') ?></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-teal">Save</button>
                <a href="<?= site_url('pets/' . $pet['id']) ?>" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
