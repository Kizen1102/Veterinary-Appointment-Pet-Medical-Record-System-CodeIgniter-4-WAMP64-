<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\PetHealthTimelineModel; ?>
    <?php use App\Models\PetModel; ?>

    <h1 class="brand-font mb-1">Health Timeline</h1>
    <p class="text-muted mb-3">Every visit, vaccine, medication and appointment of <?= esc($pet['name']) ?>, in one place.</p>

    <!-- Pet switcher -->
    <?php if (count($pets) > 1): ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($pets as $p): ?>
                <a href="<?= site_url('timeline?pet=' . $p['id']) ?>"
                   class="pet-pill <?= $p['id'] === $pet['id'] ? 'is-active' : '' ?>">
                    <?= PetModel::SPECIES[$p['species']] ?? '🐾' ?> <?= esc($p['name']) ?>
                </a>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <!-- Filter tabs -->
    <div class="timeline-filters mb-4">
        <?php foreach (PetHealthTimelineModel::GROUPS as $key => $label): ?>
            <a href="<?= site_url('timeline?pet=' . $pet['id'] . '&type=' . $key) ?>"
               class="timeline-filter <?= $group === $key ? 'is-active' : '' ?>"><?= $label ?></a>
        <?php endforeach ?>
    </div>

    <?php if ($months === []): ?>
        <div class="paw-card text-center text-muted">
            <div class="fs-1 mb-2">📭</div>
            Nothing here yet. Visits, vaccines and medications recorded by the clinic will appear on this timeline.
            <div class="mt-2"><a href="<?= site_url('appointments/new?pet=' . $pet['id']) ?>">Book an appointment</a></div>
        </div>
    <?php endif ?>

    <?php foreach ($months as $month => $events): ?>
        <h3 class="timeline-month"><?= $month ?></h3>

        <div class="timeline">
            <?php foreach ($events as $event): ?>
                <div class="timeline-item <?= $event['isFuture'] ? 'is-future' : '' ?> is-<?= $event['group'] ?>">
                    <div class="timeline-dot"><?= $event['icon'] ?></div>

                    <div class="timeline-card">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="small text-muted">
                                <?= date('D, M j', strtotime($event['date'])) ?> · <?= $event['label'] ?>
                            </div>
                            <?php if ($event['status']): ?>
                                <?= status_badge($event['status']) ?>
                            <?php elseif ($event['isFuture']): ?>
                                <span class="badge text-bg-warning">Upcoming</span>
                            <?php endif ?>
                        </div>

                        <div class="fw-semibold"><?= esc($event['title']) ?></div>

                        <?php if ($event['details']): ?>
                            <div class="small"><?= esc($event['details']) ?></div>
                        <?php endif ?>

                        <?php if ($event['vet']): ?>
                            <div class="small text-muted">🩺 <?= esc($event['vet']) ?></div>
                        <?php endif ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endforeach ?>
<?= $this->endSection() ?>
