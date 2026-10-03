<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\PetModel; ?>

    <div class="d-flex justify-content-between align-items-center mb-1">
        <h1 class="brand-font mb-0">Medications</h1>
        <a href="<?= site_url('meds/new?pet=' . $pet['id']) ?>" class="btn btn-paw btn-sm">+ Add</a>
    </div>
    <p class="text-muted mb-3">Mark each dose when you give it, so <?= esc($pet['name']) ?>'s vet can see how the treatment is going.</p>

    <!-- Pet switcher -->
    <?php if (count($pets) > 1): ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($pets as $p): ?>
                <a href="<?= site_url('meds?pet=' . $p['id']) ?>"
                   class="pet-pill <?= $p['id'] === $pet['id'] ? 'is-active' : '' ?>">
                    <?= PetModel::SPECIES[$p['species']] ?? '🐾' ?> <?= esc($p['name']) ?>
                </a>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <?php if ($active === []): ?>
        <div class="paw-card text-center text-muted mb-4">
            <div class="fs-1 mb-2">💊</div>
            No active medications for <?= esc($pet['name']) ?>.
            <div class="mt-2"><a href="<?= site_url('meds/new?pet=' . $pet['id']) ?>">Add a medication from your vet's prescription</a></div>
        </div>
    <?php endif ?>

    <?php foreach ($active as $m): ?>
        <div class="paw-card med-card mb-3">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="fw-bold">💊 <?= esc($m['name']) ?> <span class="text-muted fw-normal">· <?= esc($m['dosage']) ?></span></div>
                    <div class="small text-muted">
                        <?= esc(ucfirst($m['form'] ?? 'other')) ?>
                        · <?= implode(', ', array_map(static fn ($t) => date('g:i A', strtotime($t)), $m['times'])) ?>
                        <?= $m['instructions'] ? '· ' . esc($m['instructions']) : '' ?>
                    </div>
                    <div class="small text-muted">
                        Since <?= date('M j', strtotime($m['start_date'])) ?>
                        <?= $m['end_date'] ? ' until ' . date('M j', strtotime($m['end_date'])) : ' (no end date)' ?>
                    </div>
                </div>

                <!-- Adherence = taken ÷ (taken + missed + skipped) -->
                <div class="text-end">
                    <div class="adherence-number <?= $m['adherence'] !== null && $m['adherence'] < 80 ? 'is-low' : '' ?>">
                        <?= $m['adherence'] === null ? '—' : round($m['adherence']) . '%' ?>
                    </div>
                    <div class="small text-muted">adherence</div>
                </div>
            </div>

            <div class="small text-muted mt-2">
                ✓ <?= $m['taken'] ?> given · ✗ <?= $m['missed'] ?> missed
            </div>

            <!-- Today's doses -->
            <?php if ($m['doses'] !== []): ?>
                <div class="dose-list mt-3">
                    <?php foreach ($m['doses'] as $dose): ?>
                        <div class="dose-row is-<?= $dose['status'] ?>">
                            <span class="fw-semibold"><?= date('g:i A', strtotime($dose['scheduled_for'])) ?></span>

                            <?php if ($dose['status'] === 'taken'): ?>
                                <span class="small">✓ Given at <?= date('g:i A', strtotime($dose['taken_at'])) ?></span>
                            <?php elseif ($dose['status'] === 'skipped'): ?>
                                <span class="small">Skipped</span>
                            <?php else: ?>
                                <span class="small"><?= $dose['status'] === 'missed' ? '⚠ Missed' : 'Due' ?></span>
                                <span class="ms-auto d-flex gap-1">
                                    <form action="<?= site_url('meds/doses/' . $dose['id'] . '/take') ?>" method="post">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-paw btn-sm">Mark given</button>
                                    </form>
                                    <?php if ($dose['status'] === 'pending'): ?>
                                        <form action="<?= site_url('meds/doses/' . $dose['id'] . '/skip') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-secondary btn-sm">Skip</button>
                                        </form>
                                    <?php endif ?>
                                </span>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php else: ?>
                <div class="small text-muted mt-2">No doses today.</div>
            <?php endif ?>

            <!-- Completion bar (only when the vet gave a total number of doses) -->
            <?php if ($m['completion'] !== null): ?>
                <div class="progress mt-3" role="progressbar" aria-label="Course completion">
                    <div class="progress-bar bg-success" style="width: <?= min(100, (float) $m['completion']) ?>%"></div>
                </div>
                <div class="small text-muted mt-1"><?= round($m['completion']) ?>% of the course completed</div>
            <?php endif ?>

            <form action="<?= site_url('meds/' . $m['id'] . '/stop') ?>" method="post" class="mt-3"
                  onsubmit="return confirm('Stop giving <?= esc($m['name'], 'js') ?>? Do this only if your vet said so.')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-link btn-sm text-danger p-0">Stop this medication</button>
            </form>
        </div>
    <?php endforeach ?>

    <!-- Finished medications -->
    <?php if ($finished !== []): ?>
        <h3 class="brand-font h5 mt-4 mb-3">Past medications</h3>
        <?php foreach ($finished as $m): ?>
            <div class="paw-card med-card is-finished mb-2">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="fw-semibold"><?= esc($m['name']) ?> · <?= esc($m['dosage']) ?></div>
                        <div class="small text-muted">
                            <?= date('M j', strtotime($m['start_date'])) ?>
                            <?= $m['end_date'] ? '– ' . date('M j, Y', strtotime($m['end_date'])) : '' ?>
                            · <?= $m['adherence'] === null ? 'no doses recorded' : round($m['adherence']) . '% adherence' ?>
                        </div>
                    </div>
                    <span class="badge text-bg-secondary"><?= esc(ucfirst($m['status'])) ?></span>
                </div>
            </div>
        <?php endforeach ?>
    <?php endif ?>
<?= $this->endSection() ?>
