<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $isEdit = ! empty($pet['id']); ?>
<h1 class="h3 mb-3"><?= esc($title) ?></h1>
<div class="card shadow-sm">
    <div class="card-body">
        <form action="<?= site_url($isEdit ? 'pets/' . $pet['id'] : 'pets') ?>" method="post">
            <?= csrf_field() ?>
            <div class="row g-3">
                <?php if (! has_role('owner')): ?>
                    <div class="col-md-6">
                        <label class="form-label" for="owner_id">Owner</label>
                        <select class="form-select" id="owner_id" name="owner_id" required>
                            <option value="">Select owner…</option>
                            <?php foreach ($owners as $o): ?>
                                <option value="<?= $o['id'] ?>" <?= (string) old('owner_id', $pet['owner_id'] ?? '') === (string) $o['id'] ? 'selected' : '' ?>>
                                    <?= esc($o['name']) ?> (<?= esc($o['email']) ?>)
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                <?php endif ?>
                <div class="col-md-6">
                    <label class="form-label" for="name">Pet name</label>
                    <input class="form-control" id="name" name="name" value="<?= old('name', $pet['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="species">Species</label>
                    <input class="form-control" id="species" name="species" list="speciesList" value="<?= old('species', $pet['species'] ?? '') ?>" required>
                    <datalist id="speciesList">
                        <option>Dog</option><option>Cat</option><option>Rabbit</option><option>Bird</option>
                        <option>Hamster</option><option>Guinea Pig</option><option>Reptile</option>
                    </datalist>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="breed">Breed</label>
                    <input class="form-control" id="breed" name="breed" value="<?= old('breed', $pet['breed'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sex">Sex</label>
                    <?php $sex = old('sex', $pet['sex'] ?? ''); ?>
                    <select class="form-select" id="sex" name="sex">
                        <option value="">—</option>
                        <?php foreach (['Male', 'Female', 'Unknown'] as $s): ?>
                            <option <?= $sex === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="birth_date">Birth date</label>
                    <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?= old('birth_date', $pet['birth_date'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="weight_kg">Weight (kg)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="weight_kg" name="weight_kg" value="<?= old('weight_kg', $pet['weight_kg'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="color">Color / markings</label>
                    <input class="form-control" id="color" name="color" value="<?= old('color', $pet['color'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="allergies">Known allergies</label>
                    <textarea class="form-control" id="allergies" name="allergies" rows="2"><?= old('allergies', $pet['allergies'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"><?= old('notes', $pet['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-teal">Save</button>
                <a href="<?= site_url($isEdit ? 'pets/' . $pet['id'] : 'pets') ?>" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
