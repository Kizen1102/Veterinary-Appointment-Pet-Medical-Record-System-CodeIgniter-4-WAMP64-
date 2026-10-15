<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\PetModel; ?>

    <?php $concernText = ['none' => 'No concerns', 'low' => 'Low concern', 'moderate' => 'Moderate concern', 'high' => 'High concern']; ?>

    <h1 class="brand-font mb-1">Journal summaries</h1>
    <p class="text-muted mb-4">Summaries the owners made from their pets' daily journal. New ones are on top.</p>

    <?php if ($summaries === []): ?>
        <div class="paw-card text-center text-muted">No summaries yet.</div>
    <?php endif ?>

    <?php foreach ($summaries as $s): ?>
        <div class="paw-card summary-card mb-2 <?= $s['reviewed_at'] ? 'is-reviewed' : '' ?>">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                <a href="<?= site_url('vet/patients/' . $s['pet_id']) ?>" class="fw-bold text-decoration-none">
                    <?= PetModel::SPECIES[$s['species']] ?? '🐾' ?> <?= esc($s['pet_name']) ?>
                </a>
                <span class="concern-badge is-<?= esc($s['concern_level']) ?>"><?= $concernText[$s['concern_level']] ?? '' ?></span>
            </div>
            <div class="small text-muted mb-2">
                Owner: <?= esc($s['owner_name']) ?>
                · <?= date('M j', strtotime($s['period_start'])) ?> – <?= date('M j', strtotime($s['period_end'])) ?>
                · <?= (int) $s['entries_count'] ?> entries
            </div>
            <p class="mb-2"><?= esc($s['summary']) ?></p>
            <?php if ($s['notable_changes'] !== []): ?>
                <ul class="small mb-2">
                    <?php foreach ($s['notable_changes'] as $change): ?>
                        <li><?= esc($change) ?></li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
            <?php if ($s['reviewed_at']): ?>
                <div class="small text-success">✓ Reviewed <?= date('M j, g:i A', strtotime($s['reviewed_at'])) ?></div>
            <?php else: ?>
                <form action="<?= site_url('vet/journals/' . $s['id'] . '/review') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Mark reviewed</button>
                </form>
            <?php endif ?>
        </div>
    <?php endforeach ?>
<?= $this->endSection() ?>
