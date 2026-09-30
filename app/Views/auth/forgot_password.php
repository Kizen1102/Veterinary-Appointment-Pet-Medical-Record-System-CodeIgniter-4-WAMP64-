<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <h2 class="brand-font mb-1">Forgot password?</h2>
    <p class="text-muted mb-4">Enter your email and we'll send you a link to choose a new password.</p>

    <?php if (session('success')): ?>
        <div class="alert alert-success"><?= esc(session('success')) ?></div>
    <?php endif ?>

    <?php if (session('devLink')): ?>
        <div class="alert alert-warning small">
            <strong>Development mode:</strong> XAMPP can't send emails, so here is the link:<br>
            <a href="<?= esc(session('devLink')) ?>" class="text-break"><?= esc(session('devLink')) ?></a>
        </div>
    <?php endif ?>

    <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
    <?php endif ?>

    <?php if (session('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session('errors') as $error): ?>
                <div><?= esc($error) ?></div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <form action="<?= site_url('forgot-password') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-4">
            <label class="form-label" for="email">Email address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" placeholder="you@email.com" required autofocus>
        </div>

        <button type="submit" class="btn btn-paw w-100">Send Reset Link</button>
    </form>

    <p class="text-center mt-3 mb-0">
        Remembered it? <a href="<?= site_url('login') ?>">Back to sign in</a>
    </p>
<?= $this->endSection() ?>
