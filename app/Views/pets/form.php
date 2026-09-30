<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <a href="<?= site_url('owner') ?>" class="text-decoration-none small">← Back to home</a>
    <h1 class="brand-font mt-2 mb-1">Add a pet</h1>
    <p class="text-muted mb-4">Tell us about your pet. You can leave optional fields blank.</p>

    <form action="<?= site_url('pets') ?>" method="post" class="paw-card">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-sm-6">
                <label class="form-label" for="name">Pet name</label>
                <input class="form-control" id="name" name="name" value="<?= old('name') ?>" placeholder="Luna" required>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="species">Species</label>
                <select class="form-select form-control" id="species" name="species" required>
                    <option value="">Choose…</option>
                    <?php foreach (\App\Models\PetModel::SPECIES as $species => $emoji): ?>
                        <option value="<?= $species ?>" <?= old('species') === $species ? 'selected' : '' ?>><?= $emoji ?> <?= $species ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="breed">Breed <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="breed" name="breed" value="<?= old('breed') ?>" placeholder="Persian, Aspin…">
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="sex">Sex</label>
                <select class="form-select form-control" id="sex" name="sex" required>
                    <option value="female" <?= old('sex') === 'female' ? 'selected' : '' ?>>Female</option>
                    <option value="male" <?= old('sex') === 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="unknown" <?= old('sex') === 'unknown' ? 'selected' : '' ?>>Unknown</option>
                </select>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_neutered" name="is_neutered" value="1" <?= old('is_neutered') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_neutered">Spayed / neutered</label>
                </div>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="birth_date">Birth date <span class="text-muted">(optional)</span></label>
                <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?= old('birth_date') ?>" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="weight_kg">Weight in kg <span class="text-muted">(optional)</span></label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="weight_kg" name="weight_kg" value="<?= old('weight_kg') ?>" placeholder="4.2">
            </div>

            <div class="col-12">
                <label class="form-label" for="color_markings">Color / markings <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="color_markings" name="color_markings" value="<?= old('color_markings') ?>" placeholder="White with grey patches">
            </div>

            <div class="col-12">
                <label class="form-label" for="allergies">Known allergies <span class="text-muted">(optional)</span></label>
                <textarea class="form-control" id="allergies" name="allergies" rows="2" placeholder="e.g. chicken protein"><?= old('allergies') ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-paw w-100 mt-4">Save Pet</button>
    </form>
<?= $this->endSection() ?>
