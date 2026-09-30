<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 d-print-none">
    <a href="<?= site_url('pets/' . $pet['id']) ?>" class="btn btn-link px-0"><i class="bi bi-arrow-left"></i> Back to <?= esc($pet['name']) ?></a>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print</button>
        <?php if (has_role('admin', 'vet')): ?>
            <a href="<?= site_url('records/' . $record['id'] . '/edit') ?>" class="btn btn-outline-secondary">Edit</a>
            <form action="<?= site_url('records/' . $record['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Delete this record?')">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger">Delete</button>
            </form>
        <?php endif ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
            <div>
                <h1 class="h4 mb-0"><?= esc(config('Clinic')->name) ?></h1>
                <div class="text-muted">Medical Record</div>
            </div>
            <div class="text-end small">
                <div><strong>Visit:</strong> <?= fmt_date($record['visit_date']) ?></div>
                <div><strong>Veterinarian:</strong> <?= esc($record['vet_name'] ?? '—') ?></div>
            </div>
        </div>
        <div class="row small mb-3">
            <div class="col-sm-6">
                <strong>Patient:</strong> <?= esc($pet['name']) ?> (<?= esc($pet['species']) ?><?= $pet['breed'] ? ', ' . esc($pet['breed']) : '' ?>)<br>
                <strong>Sex / Age:</strong> <?= esc($pet['sex'] ?? '—') ?> / <?= esc(\App\Models\PetModel::ageLabel($pet['birth_date'])) ?><br>
                <strong>Allergies:</strong> <?= esc($pet['allergies'] ?? 'None known') ?>
            </div>
            <div class="col-sm-6">
                <strong>Owner:</strong> <?= esc($owner['name']) ?><br>
                <strong>Contact:</strong> <?= esc($owner['phone'] ?? $owner['email']) ?><br>
                <strong>Weight / Temp:</strong> <?= $record['weight_kg'] ? esc($record['weight_kg']) . ' kg' : '—' ?> / <?= $record['temperature_c'] ? esc($record['temperature_c']) . ' °C' : '—' ?>
            </div>
        </div>

        <?php foreach (['symptoms' => 'Symptoms / History', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment', 'prescription' => 'Prescription', 'notes' => 'Notes'] as $field => $label): ?>
            <?php if (! empty($record[$field])): ?>
                <h2 class="h6 text-teal mt-3"><?= $label ?></h2>
                <p class="mb-2"><?= nl2br(esc($record[$field])) ?></p>
            <?php endif ?>
        <?php endforeach ?>

        <?php if ($record['follow_up_date']): ?>
            <div class="alert alert-info mt-3 mb-0"><i class="bi bi-calendar-check"></i> Follow-up visit on <strong><?= fmt_date($record['follow_up_date']) ?></strong></div>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
