<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\UserModel; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="brand-font mb-0">Users</h1>
        <a href="<?= site_url('admin/users/new') ?>" class="btn btn-paw btn-sm">+ Add staff</a>
    </div>

    <!-- Role tabs with counts -->
    <div class="timeline-filters mb-3">
        <a href="<?= site_url('admin/users') ?>" class="timeline-filter <?= $role === '' ? 'is-active' : '' ?>">All (<?= array_sum($counts) ?>)</a>
        <?php foreach (['owner' => 'Pet Owners', 'vet' => 'Vets', 'admin' => 'Staff'] as $key => $label): ?>
            <a href="<?= site_url('admin/users?role=' . $key) ?>"
               class="timeline-filter <?= $role === $key ? 'is-active' : '' ?>"><?= $label ?> (<?= (int) $counts[$key] ?>)</a>
        <?php endforeach ?>
    </div>

    <form action="<?= site_url('admin/users') ?>" method="get" class="d-flex gap-2 mb-3">
        <input type="hidden" name="role" value="<?= esc($role) ?>">
        <input type="search" class="form-control" name="q" value="<?= esc($search) ?>" placeholder="Search name or email">
        <button type="submit" class="btn btn-paw">Search</button>
    </form>

    <?php if ($users === []): ?>
        <div class="paw-card text-center text-muted">No users found.</div>
    <?php endif ?>

    <?php foreach ($users as $u): ?>
        <div class="paw-card user-row mb-2 <?= $u['is_active'] ? '' : 'is-inactive' ?>">
            <div class="avatar avatar-sm"><?= esc(initials($u['full_name'])) ?></div>
            <div class="flex-grow-1">
                <div class="fw-semibold">
                    <?= esc($u['full_name']) ?>
                    <span class="badge text-bg-light border"><?= UserModel::ROLE_LABELS[$u['role']] ?></span>
                    <?php if (! $u['is_active']): ?>
                        <span class="badge text-bg-secondary">Inactive</span>
                    <?php endif ?>
                </div>
                <div class="small text-muted">
                    <?= esc($u['email']) ?><?= $u['phone'] ? ' · ' . esc($u['phone']) : '' ?>
                    <?= $u['license_number'] ? ' · Lic. ' . esc($u['license_number']) : '' ?>
                    <?= $u['specialization'] ? ' · ' . esc($u['specialization']) : '' ?>
                </div>
                <div class="small text-muted">
                    Last sign-in: <?= $u['last_login_at'] ? date('M j, Y g:i A', strtotime($u['last_login_at'])) : 'never' ?>
                </div>
            </div>
            <?php if ((int) $u['id'] !== (int) current_user()['id']): ?>
                <form action="<?= site_url('admin/users/' . $u['id'] . '/toggle') ?>" method="post"
                      onsubmit="return confirm('<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?> <?= esc($u['full_name'], 'js') ?>?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                        <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                </form>
            <?php else: ?>
                <span class="small text-muted">You</span>
            <?php endif ?>
        </div>
    <?php endforeach ?>
<?= $this->endSection() ?>
