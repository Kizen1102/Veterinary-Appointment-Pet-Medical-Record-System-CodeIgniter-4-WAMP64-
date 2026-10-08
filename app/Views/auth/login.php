<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <h2 class="brand-font mb-1">Welcome back</h2>
    <p class="text-muted mb-4">Sign in to access your pet's health records.</p>

    <?php if (session('success')): ?>
        <div class="alert alert-success"><?= esc(session('success')) ?></div>
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

    <form action="<?= site_url('login') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" placeholder="you@email.com" required autofocus>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between">
                <label class="form-label" for="password">Password</label>
                <a href="<?= site_url('forgot-password') ?>" class="small">Forgot password?</a>
            </div>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-paw w-100">Sign In</button>
    </form>

    <p class="text-center mt-3 mb-0">
        New pet owner? <a href="<?= site_url('register') ?>">Create an account</a>
    </p>
<?= $this->endSection() ?>
