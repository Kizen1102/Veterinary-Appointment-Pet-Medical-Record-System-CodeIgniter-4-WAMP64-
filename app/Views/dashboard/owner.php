<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\PetModel; ?>

    <!-- Greeting -->
    <div class="d-flex justify-content-between align-items-start mb-3 page-greeting">
        <div>
            <h1 class="brand-font"><?= greeting() ?>, <?= esc(explode(' ', $user['full_name'])[0]) ?> 👋</h1>
            <div class="page-date"><?= date('l, F j, Y') ?></div>
        </div>
        <div class="avatar"><?= esc(initials($user['full_name'])) ?></div>
    </div>

    <!-- Pet switcher (only when the owner has more than one pet) -->
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php if (count($pets) > 1): ?>
            <?php foreach ($pets as $p): ?>
                <a href="<?= site_url('owner?pet=' . $p['id']) ?>"
                   class="pet-pill <?= $p['id'] === $pet['id'] ? 'is-active' : '' ?>">
                    <?= PetModel::SPECIES[$p['species']] ?? '🐾' ?> <?= esc($p['name']) ?>
                </a>
            <?php endforeach ?>
        <?php endif ?>
        <a href="<?= site_url('pets/new') ?>" class="pet-pill">+ Add pet</a>
    </div>

    <!-- Big screens: pet card + alerts on the left, features on the right (Redesign D3) -->
    <div class="row g-4">
    <div class="col-lg-7">

    <!-- Pet card -->
    <div class="pet-hero mb-4">
        <div class="d-flex gap-3 align-items-center">
            <div class="pet-photo-wrap">
                <?php if ($pet['photo_path']): ?>
                    <img src="<?= base_url($pet['photo_path']) ?>" alt="<?= esc($pet['name']) ?>" class="pet-photo">
                <?php else: ?>
                    <div class="pet-photo"><?= PetModel::SPECIES[$pet['species']] ?? '🐾' ?></div>
                <?php endif ?>

                <!-- Change photo: the camera opens the file picker, choosing a file sends the form -->
                <form action="<?= site_url('pets/' . $pet['id'] . '/photo') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label class="photo-change" title="Change photo">
                        📷
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" hidden onchange="this.form.submit()">
                    </label>
                </form>
            </div>
            <div>
                <h2 class="brand-font mb-0">
                    <?= esc($pet['name']) ?>
                    <a href="<?= site_url('pets/' . $pet['id'] . '/edit') ?>" class="pet-edit-link" title="Edit pet">✏️</a>
                </h2>
                <div class="pet-meta">
                    <?= esc(trim(($pet['breed'] ?? '') . ' ' . $pet['species'])) ?>
                    · <?= esc(PetModel::ageLabel($pet['birth_date'])) ?>
                    · <?= esc(PetModel::sexLabel($pet)) ?>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php if ($pet['vet_name']): ?>
                        <span class="pet-badge">🩺 Vet: <?= esc($pet['vet_name']) ?></span>
                    <?php endif ?>
                    <?php if ($vaccineStatus === 'current'): ?>
                        <span class="pet-badge">✓ All vaccines current</span>
                    <?php elseif ($vaccineStatus === 'due'): ?>
                        <span class="pet-badge is-warning">⚠ Vaccine due</span>
                    <?php else: ?>
                        <span class="pet-badge is-muted">No vaccines recorded</span>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="next-appointment mt-3">
            <div class="small opacity-75">Next Appointment</div>
            <?php if ($nextAppointment): ?>
                <div class="fw-bold">
                    <?= date('M j, Y', strtotime($nextAppointment['scheduled_at'])) ?> —
                    <?= esc($nextAppointment['title'] ?: ucwords(str_replace('_', ' ', $nextAppointment['appointment_type']))) ?>
                </div>
                <div class="small opacity-75">
                    <?= date('g:i A', strtotime($nextAppointment['scheduled_at'])) ?>
                    <?= $nextAppointment['vet_name'] ? '· ' . esc($nextAppointment['vet_name']) : '' ?>
                </div>
                <a href="<?= site_url('appointments') ?>" class="small next-appointment-link">View all appointments →</a>
            <?php else: ?>
                <div class="fw-bold">No upcoming appointment</div>
                <a href="<?= site_url('appointments/new?pet=' . $pet['id']) ?>" class="small next-appointment-link">Book one now →</a>
            <?php endif ?>
        </div>
    </div>

    <!-- Today's Alerts -->
    <h3 class="brand-font h5 mb-3">Today's Alerts</h3>

    <?php foreach ($dosesMissed as $dose): ?>
        <div class="alert-card is-red mb-2">
            <span class="alert-icon">⚠️</span>
            <div class="flex-grow-1">
                <div class="fw-semibold">Missed: <?= esc($dose['name']) ?> — <?= date('g:i A', strtotime($dose['scheduled_for'])) ?></div>
                <div class="small">Give it now if your vet allows a late dose, then tap Mark.</div>
            </div>
            <form action="<?= site_url('meds/doses/' . $dose['id'] . '/take') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-mark">Mark</button>
            </form>
        </div>
    <?php endforeach ?>

    <?php foreach ($dosesDue as $dose): ?>
        <div class="alert-card is-orange mb-2">
            <span class="alert-icon">💊</span>
            <div class="flex-grow-1">
                <div class="fw-semibold"><?= esc($dose['name']) ?> — <?= date('g:i A', strtotime($dose['scheduled_for'])) ?> dose due</div>
                <div class="small"><?= esc($dose['dosage']) ?><?= $dose['instructions'] ? ' · ' . esc($dose['instructions']) : '' ?></div>
            </div>
            <form action="<?= site_url('meds/doses/' . $dose['id'] . '/take') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-mark">Mark</button>
            </form>
        </div>
    <?php endforeach ?>

    <?php if (! $journalLogged): ?>
        <a href="<?= site_url('journal?pet=' . $pet['id']) ?>" class="alert-card is-green mb-2 text-decoration-none">
            <span class="alert-icon">📝</span>
            <div class="flex-grow-1">
                <div class="fw-semibold">Daily health journal not yet logged</div>
                <div class="small">Log <?= esc($pet['name']) ?>'s appetite, mood, and activity</div>
            </div>
            <span class="fw-bold">→</span>
        </a>
    <?php endif ?>

    <?php if ($dosesDue === [] && $dosesMissed === [] && $journalLogged): ?>
        <div class="alert-card mb-2">
            <span class="alert-icon">✅</span>
            <div>All done for today!</div>
        </div>
    <?php endif ?>

    </div><!-- /left column -->
    <div class="col-lg-5">

    <!-- Features (each tile becomes a link when its step is built) -->
    <h3 class="brand-font h5 mb-3">Features</h3>
    <?php
    $features = [
        ['icon' => '🤖', 'title' => 'AI Medical Chatbot', 'text' => 'Translate vet terms', 'url' => 'chat?pet=' . $pet['id']],
        ['icon' => '📅', 'title' => 'Health Timeline', 'text' => 'All visits & records', 'url' => 'timeline?pet=' . $pet['id']],
        ['icon' => '💊', 'title' => 'Medication Tracker', 'text' => $activeMeds . ' active medication' . ($activeMeds === 1 ? '' : 's'), 'url' => 'meds?pet=' . $pet['id']],
        ['icon' => '🐾', 'title' => 'Symptom Journal', 'text' => 'AI-powered insights', 'url' => 'journal?pet=' . $pet['id']],
        ['icon' => '📋', 'title' => 'Book Appointment', 'text' => 'Schedule a clinic visit', 'url' => 'appointments/new?pet=' . $pet['id'], 'green' => true],
    ];
    ?>
    <div class="row g-3">
        <?php foreach ($features as $f): ?>
            <div class="col-6">
                <a <?= $f['url'] ? 'href="' . site_url($f['url']) . '"' : '' ?>
                   class="feature-tile <?= ! empty($f['green']) ? 'is-green' : '' ?> <?= $f['url'] ? '' : 'is-soon' ?>">
                    <span class="feature-icon"><?= $f['icon'] ?></span>
                    <div class="fw-semibold mt-2"><?= esc($f['title']) ?></div>
                    <div class="small"><?= esc($f['text']) ?><?= $f['url'] ? '' : ' · Soon' ?></div>
                </a>
            </div>
        <?php endforeach ?>
    </div>

    </div><!-- /right column -->
    </div><!-- /row -->
<?= $this->endSection() ?>
