<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="row justify-content-center mt-4">
    <div class="col-md-7 col-lg-5">
        <h1 class="h3 text-center mb-4">Create a Pet Owner Account</h1>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="<?= site_url('register') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="name">Full name</label>
                        <input class="form-control" id="name" name="name" value="<?= old('name') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label class="form-label" for="phone">Phone</label>
                            <input class="form-control" id="phone" name="phone" value="<?= old('phone') ?>">
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="form-label" for="address">Address</label>
                            <input class="form-control" id="address" name="address" value="<?= old('address') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="form-label" for="password_confirm">Confirm password</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="8" required>
                        </div>
                    </div>
                    <button class="btn btn-teal w-100">Create account</button>
                </form>
            </div>
        </div>
        <p class="text-center mt-3 small">Already registered? <a href="<?= site_url('login') ?>">Log in</a></p>
    </div>
</div>
<?= $this->endSection() ?>
