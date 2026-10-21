<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\PetHealthTimelineModel; ?>
    <?php use App\Models\PetModel; ?>

    <?php
    $type = PetHealthTimelineModel::EVENT_TYPES[$record['record_type']] ?? ['label' => 'Visit', 'icon' => '🩺'];

    // What the owner sees. The vet's private notes (vet_notes) are never shown here.
    $vitals = array_filter([
        $record['weight_kg'] ? (float) $record['weight_kg'] . ' kg' : null,
        $record['temperature_c'] ? (float) $record['temperature_c'] . ' °C' : null,
        $record['heart_rate_bpm'] ? $record['heart_rate_bpm'] . ' heartbeats/min' : null,
        $record['respiratory_rate'] ? $record['respiratory_rate'] . ' breaths/min' : null,
    ]);
    $parts = [
        'Reason for the visit' => $record['chief_complaint'],
        'Check-up'             => implode(' · ', $vitals),
        'Findings'             => $record['findings'],
        'Diagnosis'            => $record['diagnosis'],
        'Treatment'            => $record['treatment'],
        'Lab results'          => $record['lab_results'],
        'Follow-up'            => $record['follow_up_date'] ? date('F j, Y', strtotime($record['follow_up_date'])) . ($record['follow_up_notes'] ? ' — ' . $record['follow_up_notes'] : '') : null,
    ];
    ?>

    <a href="<?= site_url('timeline?pet=' . $record['pet_id']) ?>" class="text-decoration-none small">← Health Timeline</a>

    <div class="row g-4 mt-0">
        <!-- Left: the record as the vet wrote it -->
        <div class="col-lg-6">
            <div class="paw-card record-card">
                <div class="small text-muted">
                    <?= PetModel::SPECIES[$record['species']] ?? '🐾' ?> <?= esc($record['pet_name']) ?>
                    · <?= $type['icon'] ?> <?= $type['label'] ?>
                </div>
                <h1 class="brand-font h4 mt-1 mb-1"><?= esc($record['title']) ?></h1>
                <div class="small text-muted mb-3">
                    <?= date('l, F j, Y', strtotime($record['visit_date'])) ?>
                    · 🩺 <?= $record['vet_name'] ? esc($record['vet_name']) : 'Clinic' ?>
                </div>

                <dl class="record-details mb-0">
                    <?php foreach ($parts as $label => $text): ?>
                        <?php if ($text): ?>
                            <dt><?= $label ?></dt>
                            <dd><?= nl2br(esc($text)) ?></dd>
                        <?php endif ?>
                    <?php endforeach ?>
                </dl>
            </div>
        </div>

        <!-- Right: the explanation in simple words -->
        <div class="col-lg-6">
            <div class="paw-card summary-card" id="explain">
                <div class="fw-bold mb-2">✨ In simple words</div>

                <?php if ($record['owner_summary']): ?>
                    <p class="mb-2"><?= nl2br(esc($record['owner_summary'])) ?></p>
                    <div class="small text-muted mb-3">
                        Explained <?= date('M j, Y g:i A', strtotime($record['owner_summary_at'])) ?>.
                        This is general information, not a new diagnosis. Ask your vet if something is unclear.
                    </div>
                <?php else: ?>
                    <p class="text-muted small">
                        Vet words like "otitis externa" can be hard to understand.
                        Tap the button and PawRecord's AI will explain this visit in plain language.
                    </p>
                <?php endif ?>

                <form action="<?= site_url('timeline/records/' . $record['id'] . '/explain') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn <?= $record['owner_summary'] ? 'btn-outline-secondary btn-sm' : 'btn-paw' ?>">
                        <?= $record['owner_summary'] ? '↻ Explain again' : '✨ Explain in simple words' ?>
                    </button>
                </form>
            </div>

            <a href="<?= site_url('chat') ?>" class="small d-inline-block mt-2">Still have questions? Ask the AI Chat →</a>
        </div>
    </div>
<?= $this->endSection() ?>
