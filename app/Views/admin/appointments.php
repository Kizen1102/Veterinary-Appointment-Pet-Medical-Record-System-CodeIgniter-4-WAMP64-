<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Controllers\Admin; ?>

    <h1 class="brand-font mb-3">Appointments</h1>

    <div class="timeline-filters mb-4">
        <?php foreach (Admin::APPOINTMENT_TABS as $key => $label): ?>
            <a href="<?= site_url('admin/appointments?tab=' . $key) ?>"
               class="timeline-filter <?= $tab === $key ? 'is-active' : '' ?>"><?= $label ?></a>
        <?php endforeach ?>
    </div>

    <?php if ($vets === [] && $tab !== 'past'): ?>
        <div class="alert alert-warning">There are no active vets yet. <a href="<?= site_url('admin/users/new') ?>">Add a vet</a> to assign appointments.</div>
    <?php endif ?>

    <?php if ($appointments === []): ?>
        <div class="paw-card text-center text-muted">Nothing here.</div>
    <?php endif ?>

    <?php foreach ($appointments as $a): ?>
        <?= view('admin/_appointment', ['a' => $a, 'vets' => $vets]) ?>
    <?php endforeach ?>
<?= $this->endSection() ?>
