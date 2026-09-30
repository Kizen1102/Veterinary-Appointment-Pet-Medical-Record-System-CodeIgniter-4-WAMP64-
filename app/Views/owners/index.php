<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Pet Owners</h1>
    <div class="d-flex gap-2">
        <form class="d-flex" method="get">
            <input class="form-control me-2" name="q" value="<?= esc($q) ?>" placeholder="Search name, email, phone">
            <button class="btn btn-outline-secondary">Search</button>
        </form>
        <?php if (has_role('admin', 'staff')): ?>
            <a href="<?= site_url('owners/new') ?>" class="btn btn-teal text-nowrap"><i class="bi bi-person-plus"></i> Walk-in</a>
        <?php endif ?>
    </div>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Phone</th><th>Pets</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($owners as $o): ?>
                <tr>
                    <td class="fw-semibold"><?= esc($o['name']) ?></td>
                    <td><?= esc($o['email']) ?></td>
                    <td><?= esc($o['phone'] ?? '—') ?></td>
                    <td><?= (int) $o['pet_count'] ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= site_url('owners/' . $o['id']) ?>">View</a></td>
                </tr>
            <?php endforeach ?>
            <?php if (empty($owners)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No owners found.</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
