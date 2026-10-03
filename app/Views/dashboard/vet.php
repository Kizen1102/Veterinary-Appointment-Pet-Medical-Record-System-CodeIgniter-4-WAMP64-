<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <!-- Greeting -->
    <div class="d-flex justify-content-between align-items-start mb-3 page-greeting">
        <div>
            <h1 class="brand-font"><?= greeting() ?>, <?= esc($user['full_name']) ?> 👋</h1>
            <div class="page-date"><?= date('l, F j, Y') ?></div>
        </div>
        <div class="avatar"><?= esc(initials($user['full_name'])) ?></div>
    </div>

    <!-- Numbers at a glance -->
    <div class="row g-2 mb-4">
        <?php
        $stats = [
            ['label' => 'Today', 'value' => $counts['today'], 'icon' => '📅', 'url' => 'vet/appointments?tab=today'],
            ['label' => 'Requests', 'value' => $counts['requests'], 'icon' => '📥', 'url' => 'vet/appointments?tab=pending'],
            ['label' => 'Upcoming', 'value' => $counts['upcoming'], 'icon' => '🗓️', 'url' => 'vet/appointments?tab=upcoming'],
            ['label' => 'Patients', 'value' => $counts['patients'], 'icon' => '🐾', 'url' => 'vet/patients'],
        ];
        ?>
        <?php foreach ($stats as $s): ?>
            <div class="col-6 col-md-3">
                <a <?= $s['url'] ? 'href="' . site_url($s['url']) . '"' : '' ?> class="stat-tile">
                    <span class="stat-icon"><?= $s['icon'] ?></span>
                    <span class="stat-value"><?= $s['value'] ?></span>
                    <span class="stat-label"><?= $s['label'] ?></span>
                </a>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Today's schedule -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="brand-font h5 mb-0">Today's schedule</h3>
        <a href="<?= site_url('vet/appointments') ?>" class="small">All appointments →</a>
    </div>
    <?php if ($today === []): ?>
        <div class="paw-card text-muted text-center mb-4">No appointments today. ☕</div>
    <?php else: ?>
        <div class="mb-4">
            <?php foreach ($today as $a): ?>
                <?= view('vet/_appointment', ['a' => $a]) ?>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <!-- New requests from owners -->
    <h3 class="brand-font h5 mb-2">New requests <?= $counts['requests'] ? '<span class="badge text-bg-warning align-middle">' . $counts['requests'] . '</span>' : '' ?></h3>
    <?php if ($requests === []): ?>
        <div class="paw-card text-muted text-center">No new requests. 🎉</div>
    <?php else: ?>
        <?php foreach ($requests as $a): ?>
            <?= view('vet/_appointment', ['a' => $a]) ?>
        <?php endforeach ?>
        <?php if ($counts['requests'] > count($requests)): ?>
            <a href="<?= site_url('vet/appointments?tab=pending') ?>" class="small">See all <?= $counts['requests'] ?> requests →</a>
        <?php endif ?>
    <?php endif ?>
<?= $this->endSection() ?>
