<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3"><?= esc($title) ?></h1>
<div class="card shadow-sm">
    <div class="card-body">
        <form action="<?= site_url('owners') ?>" method="post">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Full name</label>
                    <input class="form-control" id="name" name="name" value="<?= old('name') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">Phone</label>
                    <input class="form-control" id="phone" name="phone" value="<?= old('phone') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="address">Address</label>
                    <input class="form-control" id="address" name="address" value="<?= old('address') ?>">
                </div>
            </div>
            <p class="form-text mt-3">A temporary password will be generated and shown after saving.</p>
            <button class="btn btn-teal">Register owner</button>
            <a href="<?= site_url('owners') ?>" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
