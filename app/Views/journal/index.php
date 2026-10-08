<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\JournalEntryModel; ?>
    <?php use App\Models\PetModel; ?>

    <?php
    // Value of a field: what was just typed (after an error), else the saved entry, else the default.
    $value = static fn (string $field, $default = '') => (string) old($field, $entry[$field] ?? $default, false);

    $isToday     = $date === date('Y-m-d');
    $concernText = ['none' => 'No concerns', 'low' => 'Low concern', 'moderate' => 'Moderate concern', 'high' => 'High concern'];
    ?>

    <h1 class="brand-font mb-1">Health Journal</h1>
    <p class="text-muted mb-3">A minute a day helps <?= esc($pet['name']) ?>'s vet notice changes early.</p>

    <!-- Pet switcher -->
    <?php if (count($pets) > 1): ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($pets as $p): ?>
                <a href="<?= site_url('journal?pet=' . $p['id']) ?>"
                   class="pet-pill <?= $p['id'] === $pet['id'] ? 'is-active' : '' ?>">
                    <?= PetModel::SPECIES[$p['species']] ?? '🐾' ?> <?= esc($p['name']) ?>
                </a>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <!-- AI summary for the vet -->
    <div class="paw-card summary-card mb-4" id="summary">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div class="fw-bold">🤖 Summary for the vet</div>
            <?php if ($summary): ?>
                <span class="concern-badge is-<?= esc($summary['concern_level']) ?>"><?= $concernText[$summary['concern_level']] ?? '' ?></span>
            <?php endif ?>
        </div>

        <?php if ($summary): ?>
            <p class="mb-2"><?= esc($summary['summary']) ?></p>
            <?php if ($summary['notable_changes'] !== []): ?>
                <ul class="small mb-2">
                    <?php foreach ($summary['notable_changes'] as $change): ?>
                        <li><?= esc($change) ?></li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
            <div class="small text-muted mb-3">
                <?= date('M j', strtotime($summary['period_start'])) ?> – <?= date('M j', strtotime($summary['period_end'])) ?>
                · <?= (int) $summary['entries_count'] ?> entries
                · <?= $summary['ai_model'] === 'rules' ? 'made by the built-in checker' : 'made by AI' ?>
                · <?= date('M j, g:i A', strtotime($summary['created_at'])) ?>
            </div>
        <?php else: ?>
            <p class="small text-muted">
                After at least 2 days of entries, create a summary of the last 14 days.
                Your vet will see it at the next visit.
            </p>
        <?php endif ?>

        <form action="<?= site_url('journal/summary') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <?= $summary ? '↻ Update summary' : '✨ Create summary' ?>
            </button>
        </form>
    </div>

    <!-- Entry form -->
    <h3 class="brand-font h5 mb-3">
        <?= $isToday ? 'How was ' . esc($pet['name']) . ' today?' : 'Entry for ' . date('l, M j', strtotime($date)) ?>
        <?php if ($entry): ?><span class="badge text-bg-success align-middle">Logged</span><?php endif ?>
    </h3>

    <form action="<?= site_url('journal') ?>" method="post" class="paw-card mb-4">
        <?= csrf_field() ?>
        <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">

        <div class="row g-3">
            <div class="col-sm-6">
                <label class="form-label" for="entry_date">Date</label>
                <input type="date" class="form-control" id="entry_date" name="entry_date" value="<?= esc($date) ?>"
                       min="<?= date('Y-m-d', strtotime('-7 days')) ?>" max="<?= date('Y-m-d') ?>"
                       onchange="location.href='<?= site_url('journal?pet=' . $pet['id']) ?>&date=' + this.value">
            </div>

            <?php
            // The three 1-5 scores, shown as five tappable buttons each
            $scales = [
                'appetite_score' => ['label' => 'Appetite', 'low' => 'Not eating', 'high' => 'Eats everything'],
                'activity_score' => ['label' => 'Activity', 'low' => 'Very tired', 'high' => 'Very active'],
                'sleep_quality'  => ['label' => 'Sleep quality (optional)', 'low' => 'Restless', 'high' => 'Slept well'],
            ];
            ?>
            <?php foreach ($scales as $field => $scale): ?>
                <div class="col-12">
                    <label class="form-label"><?= $scale['label'] ?></label>
                    <div class="score-picker">
                        <?php for ($n = 1; $n <= 5; $n++): ?>
                            <input type="radio" class="btn-check" name="<?= $field ?>" id="<?= $field . $n ?>" value="<?= $n ?>"
                                <?= $value($field, $field === 'sleep_quality' ? '' : '3') === (string) $n ? 'checked' : '' ?>
                                <?= $field === 'sleep_quality' ? '' : 'required' ?>>
                            <label class="score-option" for="<?= $field . $n ?>"><?= $n ?></label>
                        <?php endfor ?>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span><?= $scale['low'] ?></span><span><?= $scale['high'] ?></span>
                    </div>
                </div>
            <?php endforeach ?>

            <div class="col-sm-6">
                <label class="form-label" for="mood">Mood</label>
                <select class="form-select form-control" id="mood" name="mood" required>
                    <?php foreach (JournalEntryModel::MOODS as $mood): ?>
                        <option value="<?= $mood ?>" <?= $value('mood', 'calm') === $mood ? 'selected' : '' ?>><?= ucfirst($mood) ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="sleep_hours">Sleep hours <span class="text-muted">(optional)</span></label>
                <input type="number" step="0.5" min="0" max="24" class="form-control" id="sleep_hours" name="sleep_hours" value="<?= esc($value('sleep_hours')) ?>" placeholder="12">
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="water_intake">Water intake</label>
                <select class="form-select form-control" id="water_intake" name="water_intake" required>
                    <?php foreach (['less' => 'Less than usual', 'normal' => 'Normal', 'more' => 'More than usual'] as $water => $label): ?>
                        <option value="<?= $water ?>" <?= $value('water_intake', 'normal') === $water ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="bowel_movement">Stool</label>
                <select class="form-select form-control" id="bowel_movement" name="bowel_movement" required>
                    <?php foreach (JournalEntryModel::BOWEL_MOVEMENT as $stool): ?>
                        <option value="<?= $stool ?>" <?= $value('bowel_movement', 'normal') === $stool ? 'selected' : '' ?>><?= ucfirst($stool) ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="weight_kg">Weight in kg <span class="text-muted">(optional)</span></label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="weight_kg" name="weight_kg" value="<?= esc($value('weight_kg')) ?>">
            </div>

            <div class="col-sm-6 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="vomited" name="vomited" value="1" <?= $value('vomited') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="vomited">Vomited today</label>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label" for="symptoms">Symptoms <span class="text-muted">(optional)</span></label>
                <textarea class="form-control" id="symptoms" name="symptoms" rows="2" placeholder="e.g. scratching the left ear, coughing at night"><?= esc($value('symptoms')) ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label" for="behavior_notes">Behavior notes <span class="text-muted">(optional)</span></label>
                <textarea class="form-control" id="behavior_notes" name="behavior_notes" rows="2" placeholder="e.g. hid under the bed, did not want to play"><?= esc($value('behavior_notes')) ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-paw w-100 mt-4"><?= $entry ? 'Save Changes' : 'Save Entry' ?></button>
    </form>

    <!-- History -->
    <h3 class="brand-font h5 mb-3">Last 14 days</h3>

    <?php if ($history === []): ?>
        <div class="paw-card text-center text-muted">No entries yet. Your first one is above. 🐾</div>
    <?php endif ?>

    <?php foreach ($history as $h): ?>
        <div class="paw-card journal-row mb-2">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="fw-semibold"><?= date('D, M j', strtotime($h['entry_date'])) ?> · <?= esc(ucfirst((string) $h['mood'])) ?></div>
                    <div class="small text-muted">
                        Appetite <?= (int) $h['appetite_score'] ?>/5 · Activity <?= (int) $h['activity_score'] ?>/5
                        · Water: <?= esc((string) $h['water_intake']) ?> · Stool: <?= esc((string) $h['bowel_movement']) ?>
                        <?= $h['vomited'] ? '· <span class="text-danger">Vomited</span>' : '' ?>
                    </div>
                    <?php if ($h['symptoms']): ?>
                        <div class="small">🩹 <?= esc($h['symptoms']) ?></div>
                    <?php endif ?>
                </div>
                <div class="d-flex gap-1">
                    <?php if ($h['entry_date'] >= date('Y-m-d', strtotime('-7 days'))): ?>
                        <a href="<?= site_url('journal?pet=' . $pet['id'] . '&date=' . $h['entry_date']) ?>" class="btn btn-outline-secondary btn-sm" title="Edit">✏️</a>
                    <?php endif ?>
                    <form action="<?= site_url('journal/' . $h['id'] . '/delete') ?>" method="post"
                          onsubmit="return confirm('Delete the entry for <?= date('M j', strtotime($h['entry_date'])) ?>?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">🗑</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach ?>
<?= $this->endSection() ?>
