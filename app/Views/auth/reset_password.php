<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <?php $min = \App\Models\UserModel::MIN_PASSWORD_LENGTH ?>
    <h2 class="brand-font mb-1">Choose a new password</h2>
    <p class="text-muted mb-4">Type your new password twice.</p>

    <?php if (session('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session('errors') as $error): ?>
                <div><?= esc($error) ?></div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <form action="<?= site_url('reset-password/' . esc($token, 'url')) ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label" for="password">New password</label>
            <input type="password" class="form-control" id="password" name="password" minlength="<?= $min ?>" placeholder="At least <?= $min ?> characters" required autofocus>
        </div>

        <div class="mb-4">
            <label class="form-label" for="password_confirm">Confirm password</label>
            <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="<?= $min ?>" required>
        </div>

        <button type="submit" class="btn btn-paw w-100">Save New Password</button>
    </form>
<?= $this->endSection() ?>
