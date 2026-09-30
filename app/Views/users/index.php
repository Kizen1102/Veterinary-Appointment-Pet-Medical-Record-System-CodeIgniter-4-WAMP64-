<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">User Accounts</h1>
    <a href="<?= site_url('users/new') ?>" class="btn btn-teal"><i class="bi bi-plus-lg"></i> New user</a>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= esc($u['name']) ?></td>
                    <td><?= esc($u['email']) ?></td>
                    <td><span class="badge text-bg-info"><?= esc(ucfirst($u['role'])) ?></span></td>
                    <td><?= $u['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Disabled</span>' ?></td>
                    <td class="text-end">
                        <?php if ((int) $u['id'] !== (int) current_user()['id']): ?>
                            <form action="<?= site_url('users/' . $u['id'] . '/toggle') ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'danger' : 'success' ?>"><?= $u['is_active'] ? 'Disable' : 'Enable' ?></button>
                            </form>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
