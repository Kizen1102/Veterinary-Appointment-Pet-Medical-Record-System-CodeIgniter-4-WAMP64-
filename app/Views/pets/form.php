<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php
    // One form for both pages: $pet is null on "Add Pet" and holds the pet on "Edit Pet".
    $isEdit = $pet !== null;
    $action = $isEdit ? site_url('pets/' . $pet['id']) : site_url('pets');

    // Value of a field: what was just typed (after an error), else the saved value, else empty.
    // (false = old() does not escape; esc() does it below, once)
    $value = static fn (string $field) => (string) old($field, $pet[$field] ?? '', false);
    ?>

    <a href="<?= site_url($isEdit ? 'owner?pet=' . $pet['id'] : 'owner') ?>" class="text-decoration-none small">← Back to home</a>
    <h1 class="brand-font mt-2 mb-1"><?= $isEdit ? 'Edit ' . esc($pet['name']) : 'Add a pet' ?></h1>
    <p class="text-muted mb-4">
        <?= $isEdit ? 'Update your pet\'s details.' : 'Tell us about your pet.' ?> You can leave optional fields blank.
    </p>

    <form action="<?= $action ?>" method="post" enctype="multipart/form-data" class="paw-card">
        <?= csrf_field() ?>

        <div class="row g-3">
            <?php if (! $isEdit): ?>
                <!-- On "Edit", the photo is changed with the 📷 button on the pet card -->
                <div class="col-12 text-center">
                    <img id="photoPreview" class="pet-photo-preview d-none" alt="Photo preview">
                    <label class="form-label d-block" for="photo">Pet photo <span class="text-muted">(optional)</span></label>
                    <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                    <div class="form-text">JPG, PNG or WEBP, up to 2 MB.</div>
                </div>
            <?php endif ?>

            <div class="col-sm-6">
                <label class="form-label" for="name">Pet name</label>
                <input class="form-control" id="name" name="name" value="<?= esc($value('name')) ?>" placeholder="Luna" required>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="species">Species</label>
                <select class="form-select form-control" id="species" name="species" required>
                    <option value="">Choose one</option>
                    <?php foreach (\App\Models\PetModel::SPECIES as $species => $emoji): ?>
                        <option value="<?= $species ?>" <?= $value('species') === $species ? 'selected' : '' ?>><?= $emoji ?> <?= $species ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="breed">Breed <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="breed" name="breed" value="<?= esc($value('breed')) ?>" placeholder="Persian, Aspin">
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="sex">Sex</label>
                <select class="form-select form-control" id="sex" name="sex" required>
                    <option value="female" <?= $value('sex') === 'female' ? 'selected' : '' ?>>Female</option>
                    <option value="male" <?= $value('sex') === 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="unknown" <?= $value('sex') === 'unknown' ? 'selected' : '' ?>>Unknown</option>
                </select>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_neutered" name="is_neutered" value="1" <?= $value('is_neutered') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_neutered">Spayed / neutered</label>
                </div>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="birth_date">Birth date <span class="text-muted">(optional)</span></label>
                <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?= esc($value('birth_date')) ?>" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="weight_kg">Weight in kg <span class="text-muted">(optional)</span></label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="weight_kg" name="weight_kg" value="<?= esc($value('weight_kg')) ?>" placeholder="4.2">
            </div>

            <div class="col-12">
                <label class="form-label" for="color_markings">Color / markings <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="color_markings" name="color_markings" value="<?= esc($value('color_markings')) ?>" placeholder="White with grey patches">
            </div>

            <div class="col-12">
                <label class="form-label" for="allergies">Known allergies <span class="text-muted">(optional)</span></label>
                <textarea class="form-control" id="allergies" name="allergies" rows="2" placeholder="e.g. chicken protein"><?= esc($value('allergies')) ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-paw w-100 mt-4"><?= $isEdit ? 'Save Changes' : 'Save Pet' ?></button>
    </form>

    <?php if ($isEdit): ?>
        <!-- Archive: hides the pet but keeps its medical history -->
        <div class="paw-card archive-card mt-4">
            <div class="fw-semibold">Archive <?= esc($pet['name']) ?></div>
            <p class="small text-muted mb-3">
                The pet is removed from your dashboard and its upcoming appointments are cancelled.
                Its medical history is kept by the clinic.
            </p>
            <form action="<?= site_url('pets/' . $pet['id'] . '/archive') ?>" method="post"
                  onsubmit="return confirm('Archive <?= esc($pet['name'], 'js') ?>? This cannot be undone from the app.')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">Archive pet</button>
            </form>
        </div>
    <?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Show a preview of the chosen photo before saving (only on "Add Pet")
const photoInput = document.getElementById('photo');
if (photoInput) {
    photoInput.addEventListener('change', function () {
        const preview = document.getElementById('photoPreview');
        if (this.files.length > 0) {
            preview.src = URL.createObjectURL(this.files[0]);
            preview.classList.remove('d-none');
        } else {
            preview.classList.add('d-none');
        }
    });
}
</script>
<?= $this->endSection() ?>
