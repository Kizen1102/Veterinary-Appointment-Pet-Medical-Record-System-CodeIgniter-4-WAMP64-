<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1 class="h3 mb-3">New User</h1>
<div class="card shadow-sm">
    <div class="card-body">
        <form action="<?= site_url('users') ?>" method="post">
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
                <div class="col-md-4">
                    <label class="form-label" for="role">Role</label>
                    <select class="form-select" id="role" name="role">
                        <?php foreach (\App\Models\UserModel::ROLES as $r): ?>
                            <option value="<?= $r ?>" <?= old('role', 'staff') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="phone">Phone</label>
                    <input class="form-control" id="phone" name="phone" value="<?= old('phone') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-teal">Create user</button>
                <a href="<?= site_url('users') ?>" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
