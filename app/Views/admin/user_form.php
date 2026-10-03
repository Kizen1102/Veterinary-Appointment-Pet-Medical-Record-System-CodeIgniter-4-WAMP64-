<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php $old = static fn (string $field, string $default = '') => esc((string) old($field, $default, false)); ?>
    <?php $selectedRole = old('role') ?? $role; ?>

    <a href="<?= site_url('admin/users') ?>" class="text-decoration-none small">← Users</a>
    <h1 class="brand-font mt-2 mb-1">Add staff</h1>
    <p class="text-muted mb-4">Create an account for a veterinarian or clinic staff. Pet owners sign up by themselves.</p>

    <form action="<?= site_url('admin/users') ?>" method="post" class="paw-card">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label d-block">Role</label>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="role" id="role-vet" value="vet" <?= $selectedRole === 'vet' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-secondary" for="role-vet">🩺 Veterinarian</label>
                    <input type="radio" class="btn-check" name="role" id="role-admin" value="admin" <?= $selectedRole === 'admin' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-secondary" for="role-admin">🗂️ Clinic Staff</label>
                </div>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="full_name">Full name</label>
                <input class="form-control" id="full_name" name="full_name" value="<?= $old('full_name') ?>" placeholder="Dr. Ana Cruz" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="email">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= $old('email') ?>" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="phone">Phone <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="phone" name="phone" value="<?= $old('phone') ?>" placeholder="0917 123 4567">
            </div>

            <!-- Only for vets: hidden when "Clinic Staff" is chosen -->
            <div class="col-sm-6 vet-only">
                <label class="form-label" for="license_number">PRC license no. <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="license_number" name="license_number" value="<?= $old('license_number') ?>">
            </div>
            <div class="col-12 vet-only">
                <label class="form-label" for="specialization">Specialization <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="specialization" name="specialization" value="<?= $old('specialization') ?>" placeholder="Small animals, Dermatology">
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="password">Temporary password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="password_confirm">Confirm password</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
            </div>
        </div>

        <button type="submit" class="btn btn-paw w-100 mt-4">Create Account</button>
    </form>

    <script>
        // Show the license and specialization boxes only for vets
        const showVetFields = () => {
            const isVet = document.getElementById('role-vet').checked;
            document.querySelectorAll('.vet-only').forEach((box) => { box.hidden = !isVet; });
        };
        document.querySelectorAll('[name="role"]').forEach((radio) => radio.addEventListener('change', showVetFields));
        showVetFields();
    </script>
<?= $this->endSection() ?>
