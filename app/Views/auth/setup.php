<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <?php $min = \App\Models\UserModel::MIN_PASSWORD_LENGTH ?>
    <h2 class="brand-font">First-time Setup</h2>
    <p class="text-muted">Create the Clinic Staff (Admin) account. You only do this once.</p>

    <?php if (session('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session('errors') as $error): ?>
                <div><?= esc($error) ?></div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <form action="<?= site_url('setup') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label" for="full_name">Full name</label>
            <input class="form-control" id="full_name" name="full_name" value="<?= esc(old('full_name') ?? '') ?>" placeholder="Maria Santos" autocomplete="name" required>
            <div class="form-text">First and last name.</div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" placeholder="you@email.com" required>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" minlength="<?= $min ?>" placeholder="At least <?= $min ?> characters" required>
        </div>

        <div class="mb-4">
            <label class="form-label" for="password_confirm">Confirm password</label>
            <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="<?= $min ?>" required>
        </div>

        <button type="submit" class="btn btn-paw w-100">Create Admin Account</button>
    </form>
<?= $this->endSection() ?>
