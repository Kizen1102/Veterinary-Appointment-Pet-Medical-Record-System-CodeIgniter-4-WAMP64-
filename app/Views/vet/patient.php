<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\PetHealthTimelineModel; ?>
    <?php use App\Models\PetModel; ?>

    <?php $concernText = ['none' => 'No concerns', 'low' => 'Low concern', 'moderate' => 'Moderate concern', 'high' => 'High concern']; ?>

    <a href="<?= site_url('vet/patients') ?>" class="text-decoration-none small">← Patients</a>

    <!-- Pet card (same look as the owner's dashboard) -->
    <div class="pet-hero mt-2 mb-3">
        <div class="d-flex gap-3 align-items-center">
            <?php if ($pet['photo_path']): ?>
                <img src="<?= base_url($pet['photo_path']) ?>" alt="<?= esc($pet['name']) ?>" class="pet-photo">
            <?php else: ?>
                <div class="pet-photo"><?= PetModel::SPECIES[$pet['species']] ?? '🐾' ?></div>
            <?php endif ?>
            <div>
                <h2 class="brand-font mb-0"><?= esc($pet['name']) ?></h2>
                <div class="pet-meta">
                    <?= esc(trim(($pet['breed'] ?? '') . ' ' . $pet['species'])) ?>
                    · <?= esc(PetModel::ageLabel($pet['birth_date'])) ?>
                    · <?= esc(PetModel::sexLabel($pet)) ?>
                    <?= $pet['weight_kg'] ? '· ' . (float) $pet['weight_kg'] . ' kg' : '' ?>
                </div>
                <div class="pet-meta small mt-1">
                    👤 <?= esc($pet['owner_name']) ?>
                    <?= $pet['owner_phone'] ? '· 📞 ' . esc($pet['owner_phone']) : '' ?>
                    · ✉️ <?= esc($pet['owner_email']) ?>
                </div>
            </div>
        </div>
        <?php if ($pet['allergies'] || $pet['chronic_conditions']): ?>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <?php if ($pet['allergies']): ?>
                    <span class="pet-badge is-warning">⚠️ Allergies: <?= esc($pet['allergies']) ?></span>
                <?php endif ?>
                <?php if ($pet['chronic_conditions']): ?>
                    <span class="pet-badge is-muted">Chronic: <?= esc($pet['chronic_conditions']) ?></span>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>

    <!-- What the vet can add -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="<?= site_url('vet/patients/' . $pet['id'] . '/records/new') ?>" class="btn btn-paw btn-sm">📝 Add record</a>
        <a href="<?= site_url('vet/patients/' . $pet['id'] . '/vaccines/new') ?>" class="btn btn-outline-secondary btn-sm">💉 Record vaccine</a>
        <a href="<?= site_url('vet/patients/' . $pet['id'] . '/prescriptions/new') ?>" class="btn btn-outline-secondary btn-sm">💊 Prescribe</a>
    </div>

    <!-- Owner's journal: the summary and the last 7 days -->
    <h3 class="brand-font h5 mb-2">Owner's journal</h3>
    <?php if ($summary): ?>
        <div class="paw-card summary-card mb-2">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div class="fw-bold">🤖 Summary</div>
                <span class="concern-badge is-<?= esc($summary['concern_level']) ?>"><?= $concernText[$summary['concern_level']] ?? '' ?></span>
            </div>
            <p class="mb-2"><?= esc($summary['summary']) ?></p>
            <?php if ($summary['notable_changes'] !== []): ?>
                <ul class="small mb-2">
                    <?php foreach ($summary['notable_changes'] as $change): ?>
                        <li><?= esc($change) ?></li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
            <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="small text-muted">
                    <?= date('M j', strtotime($summary['period_start'])) ?> – <?= date('M j', strtotime($summary['period_end'])) ?>
                    · <?= (int) $summary['entries_count'] ?> entries
                </span>
                <?php if ($summary['reviewed_at']): ?>
                    <span class="small text-success">✓ Reviewed</span>
                <?php else: ?>
                    <form action="<?= site_url('vet/journals/' . $summary['id'] . '/review') ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Mark reviewed</button>
                    </form>
                <?php endif ?>
            </div>
        </div>
    <?php endif ?>
    <?php if ($journal === []): ?>
        <div class="paw-card text-muted text-center mb-4">No journal entries in the last 7 days.</div>
    <?php else: ?>
        <div class="mb-4">
            <?php foreach ($journal as $h): ?>
                <div class="paw-card journal-row mb-1">
                    <div class="fw-semibold small"><?= date('D, M j', strtotime($h['entry_date'])) ?> · <?= esc(ucfirst((string) $h['mood'])) ?></div>
                    <div class="small text-muted">
                        Appetite <?= (int) $h['appetite_score'] ?>/5 · Activity <?= (int) $h['activity_score'] ?>/5
                        · Water: <?= esc((string) $h['water_intake']) ?> · Stool: <?= esc((string) $h['bowel_movement']) ?>
                        <?= $h['vomited'] ? '· <span class="text-danger">Vomited</span>' : '' ?>
                    </div>
                    <?php if ($h['symptoms']): ?>
                        <div class="small">🩹 <?= esc($h['symptoms']) ?></div>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <!-- Current medications -->
    <h3 class="brand-font h5 mb-2">Current medications</h3>
    <?php if ($medications === []): ?>
        <div class="paw-card text-muted text-center mb-4">No active medications.</div>
    <?php else: ?>
        <div class="mb-4">
            <?php foreach ($medications as $m): ?>
                <div class="paw-card med-card mb-2">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold">💊 <?= esc($m['name']) ?> · <?= esc($m['dosage']) ?></div>
                            <div class="small text-muted">
                                <?= esc(implode(', ', array_map(static fn ($t) => date('g:i A', strtotime($t)), $m['times']))) ?>
                                · since <?= date('M j', strtotime($m['start_date'])) ?>
                                <?= $m['end_date'] ? '– ' . date('M j', strtotime($m['end_date'])) : '' ?>
                            </div>
                        </div>
                        <div class="text-end small">
                            <?php if ($m['adherence'] !== null): ?>
                                <div class="fw-bold <?= $m['adherence'] < 80 ? 'text-danger' : 'text-success' ?>"><?= (float) $m['adherence'] ?>%</div>
                                <div class="text-muted">given on time</div>
                            <?php else: ?>
                                <div class="text-muted">No doses yet</div>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <!-- Medical records -->
    <h3 class="brand-font h5 mb-2">Medical records</h3>
    <?php if ($records === []): ?>
        <div class="paw-card text-muted text-center mb-4">No records yet.</div>
    <?php else: ?>
        <div class="mb-4">
            <?php foreach ($records as $r): ?>
                <?php $type = PetHealthTimelineModel::EVENT_TYPES[$r['record_type']] ?? ['label' => 'Record', 'icon' => '📝']; ?>
                <details class="paw-card record-card mb-2">
                    <summary>
                        <span class="fw-semibold"><?= $type['icon'] ?> <?= esc($r['title']) ?></span>
                        <span class="small text-muted d-block">
                            <?= date('M j, Y', strtotime($r['visit_date'])) ?> · <?= $type['label'] ?>
                            · <?= $r['vet_name'] ? esc($r['vet_name']) : 'Clinic' ?>
                        </span>
                    </summary>
                    <dl class="record-details small mt-2 mb-0">
                        <?php
                        $vitals = array_filter([
                            $r['weight_kg'] ? (float) $r['weight_kg'] . ' kg' : null,
                            $r['temperature_c'] ? (float) $r['temperature_c'] . ' °C' : null,
                            $r['heart_rate_bpm'] ? $r['heart_rate_bpm'] . ' bpm' : null,
                            $r['respiratory_rate'] ? $r['respiratory_rate'] . ' breaths/min' : null,
                        ]);
                        $parts = [
                            'Complaint'     => $r['chief_complaint'],
                            'Vitals'        => implode(' · ', $vitals),
                            'Findings'      => $r['findings'],
                            'Diagnosis'     => $r['diagnosis'],
                            'Treatment'     => $r['treatment'],
                            'Follow-up'     => $r['follow_up_date'] ? date('M j, Y', strtotime($r['follow_up_date'])) . ($r['follow_up_notes'] ? ' — ' . $r['follow_up_notes'] : '') : null,
                            'Private notes' => $r['vet_notes'],
                        ];
                        ?>
                        <?php foreach ($parts as $label => $text): ?>
                            <?php if ($text): ?>
                                <dt><?= $label ?></dt>
                                <dd><?= nl2br(esc($text)) ?></dd>
                            <?php endif ?>
                        <?php endforeach ?>
                    </dl>
                </details>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <!-- Vaccinations -->
    <h3 class="brand-font h5 mb-2">Vaccinations</h3>
    <?php if ($vaccinations === []): ?>
        <div class="paw-card text-muted text-center">No vaccinations yet.</div>
    <?php endif ?>
    <?php foreach ($vaccinations as $v): ?>
        <?php $overdue = $v['next_due_date'] && $v['next_due_date'] < date('Y-m-d'); ?>
        <div class="paw-card journal-row mb-1 d-flex justify-content-between gap-2">
            <div>
                <div class="fw-semibold">💉 <?= esc($v['vaccine_name']) ?> <?= $v['dose_number'] ? '· dose ' . (int) $v['dose_number'] : '' ?></div>
                <div class="small text-muted">Given <?= date('M j, Y', strtotime($v['date_given'])) ?></div>
            </div>
            <?php if ($v['next_due_date']): ?>
                <div class="small text-end <?= $overdue ? 'text-danger fw-bold' : 'text-muted' ?>">
                    <?= $overdue ? 'Overdue' : 'Next due' ?><br><?= date('M j, Y', strtotime($v['next_due_date'])) ?>
                </div>
            <?php endif ?>
        </div>
    <?php endforeach ?>
<?= $this->endSection() ?>
