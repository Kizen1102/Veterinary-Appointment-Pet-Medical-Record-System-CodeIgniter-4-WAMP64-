<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Controllers\VetAppointments; ?>

    <h1 class="brand-font mb-3">Appointments</h1>

    <!-- Tabs (same style as the Health Timeline filters) -->
    <div class="timeline-filters mb-4">
        <?php foreach (VetAppointments::TABS as $key => $label): ?>
            <a href="<?= site_url('vet/appointments?tab=' . $key) ?>"
               class="timeline-filter <?= $tab === $key ? 'is-active' : '' ?>"><?= $label ?></a>
        <?php endforeach ?>
    </div>

    <?php if ($appointments === []): ?>
        <div class="paw-card text-center text-muted">Nothing here.</div>
    <?php endif ?>

    <?php foreach ($appointments as $a): ?>
        <?= view('vet/_appointment', ['a' => $a]) ?>
    <?php endforeach ?>
<?= $this->endSection() ?>
