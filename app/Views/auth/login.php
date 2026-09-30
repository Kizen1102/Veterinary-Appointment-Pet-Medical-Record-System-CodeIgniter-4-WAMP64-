<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="row justify-content-center mt-4">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4">
            <i class="bi bi-heart-pulse text-teal display-5"></i>
            <h1 class="h3 mt-2">VetCare Clinic</h1>
            <p class="text-muted">Sign in to manage appointments and pet records</p>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="<?= site_url('login') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button class="btn btn-teal w-100">Log in</button>
                </form>
            </div>
        </div>
        <p class="text-center mt-3 small">
            New pet owner? <a href="<?= site_url('register') ?>">Create an account</a>
        </p>
    </div>
</div>
<?= $this->endSection() ?>
