<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <!-- Greeting -->
    <div class="d-flex justify-content-between align-items-start mb-3 page-greeting">
        <div>
            <h1 class="brand-font"><?= greeting() ?>, <?= esc(explode(' ', $user['full_name'])[0]) ?> 👋</h1>
            <div class="page-date"><?= date('l, F j, Y') ?></div>
        </div>
        <div class="avatar"><?= esc(initials($user['full_name'])) ?></div>
    </div>

    <!-- Clinic numbers -->
    <div class="row g-2 mb-4">
        <?php
        $stats = [
            ['label' => 'Pet owners', 'value' => $counts['owner'], 'icon' => '👥', 'url' => 'admin/users?role=owner'],
            ['label' => 'Vets', 'value' => $counts['vet'], 'icon' => '🩺', 'url' => 'admin/users?role=vet'],
            ['label' => 'Pets', 'value' => $counts['pets'], 'icon' => '🐾', 'url' => 'admin/pets'],
            ['label' => 'Today', 'value' => $counts['today'], 'icon' => '📅', 'url' => 'admin/appointments?tab=today'],
        ];
        ?>
        <?php foreach ($stats as $s): ?>
            <div class="col-6 col-md-3">
                <a href="<?= site_url($s['url']) ?>" class="stat-tile">
                    <span class="stat-icon"><?= $s['icon'] ?></span>
                    <span class="stat-value"><?= (int) $s['value'] ?></span>
                    <span class="stat-label"><?= $s['label'] ?></span>
                </a>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Quick actions -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="<?= site_url('admin/users/new?role=vet') ?>" class="btn btn-paw btn-sm">+ Add vet</a>
        <a href="<?= site_url('admin/users/new?role=admin') ?>" class="btn btn-outline-secondary btn-sm">+ Add clinic staff</a>
    </div>

    <!-- Requests that no vet has taken yet -->
    <h3 class="brand-font h5 mb-2">
        Requests with no vet
        <?= $counts['unassigned'] ? '<span class="badge text-bg-warning align-middle">' . (int) $counts['unassigned'] . '</span>' : '' ?>
    </h3>
    <?php if ($unassigned === []): ?>
        <div class="paw-card text-muted text-center mb-4">Every request has a vet. 🎉</div>
    <?php else: ?>
        <div class="mb-4">
            <?php foreach ($unassigned as $a): ?>
                <?= view('admin/_appointment', ['a' => $a, 'vets' => $vets]) ?>
            <?php endforeach ?>
            <?php if ($counts['unassigned'] > count($unassigned)): ?>
                <a href="<?= site_url('admin/appointments?tab=unassigned') ?>" class="small">See all <?= (int) $counts['unassigned'] ?> →</a>
            <?php endif ?>
        </div>
    <?php endif ?>

    <!-- Today's visits in the whole clinic -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="brand-font h5 mb-0">Today in the clinic</h3>
        <a href="<?= site_url('admin/appointments') ?>" class="small">All appointments →</a>
    </div>
    <?php if ($today === []): ?>
        <div class="paw-card text-muted text-center">No appointments today.</div>
    <?php endif ?>
    <?php foreach ($today as $a): ?>
        <?= view('admin/_appointment', ['a' => $a, 'vets' => $vets]) ?>
    <?php endforeach ?>
<?= $this->endSection() ?>
