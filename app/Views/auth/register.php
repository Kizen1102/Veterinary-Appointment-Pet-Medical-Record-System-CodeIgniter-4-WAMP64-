<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <?php $min = \App\Models\UserModel::MIN_PASSWORD_LENGTH ?>
    <h2 class="brand-font mb-1">Create an account</h2>
    <p class="text-muted mb-4">For pet owners. Keep all your pet's health records in one place.</p>

    <?php if (session('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session('errors') as $error): ?>
                <div><?= esc($error) ?></div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <form action="<?= site_url('register') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label" for="full_name">Full name</label>
            <input class="form-control" id="full_name" name="full_name" value="<?= esc(old('full_name') ?? '') ?>" placeholder="Juan Dela Cruz" autocomplete="name" required>
            <div class="form-text">Your first and last name, as on your ID.</div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" placeholder="you@email.com" required>
        </div>

        <div class="mb-3">
            <label class="form-label" for="phone">Mobile number <span class="text-muted">(optional)</span></label>
            <input class="form-control" id="phone" name="phone" value="<?= old('phone') ?>" placeholder="09XX XXX XXXX">
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" minlength="<?= $min ?>" placeholder="At least <?= $min ?> characters" required>
        </div>

        <div class="mb-4">
            <label class="form-label" for="password_confirm">Confirm password</label>
            <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="<?= $min ?>" required>
        </div>

        <button type="submit" class="btn btn-paw w-100">Create Account</button>
    </form>

    <p class="text-center mt-3 mb-0">
        Already have an account? <a href="<?= site_url('login') ?>">Sign in</a>
    </p>
<?= $this->endSection() ?>
