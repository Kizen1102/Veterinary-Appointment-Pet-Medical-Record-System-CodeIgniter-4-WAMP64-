<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <?php use App\Models\PetModel; ?>

    <div class="chat-hero mb-4">
        <div class="fs-1">🤖</div>
        <div>
            <h1 class="brand-font mb-1">PawDoc AI</h1>
            <div class="small">Ask what the words in your pet's medical records mean. I explain them in simple language.</div>
        </div>
    </div>

    <!-- New question -->
    <form action="<?= site_url('chat') ?>" method="post" class="paw-card mb-3" onsubmit="thinking(this)">
        <?= csrf_field() ?>

        <?php if (count($pets) > 1): ?>
            <label class="form-label" for="pet_id">About which pet?</label>
            <select class="form-select form-control mb-3" id="pet_id" name="pet_id"
                    onchange="location.href='<?= site_url('chat') ?>?pet=' + this.value">
                <?php foreach ($pets as $pet): ?>
                    <option value="<?= $pet['id'] ?>" <?= $selectedPet && $pet['id'] === $selectedPet['id'] ? 'selected' : '' ?>>
                        <?= PetModel::SPECIES[$pet['species']] ?? '🐾' ?> <?= esc($pet['name']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        <?php elseif ($selectedPet): ?>
            <input type="hidden" name="pet_id" value="<?= $selectedPet['id'] ?>">
        <?php endif ?>

        <label class="form-label" for="message">Your question</label>
        <textarea class="form-control" id="message" name="message" rows="3" maxlength="1000" required
                  placeholder="e.g. The vet wrote &quot;otitis externa, give drops BID&quot;. What does that mean?"><?= old('message') ?></textarea>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <?php foreach ($suggestions as $suggestion): ?>
                <button type="button" class="chat-chip" onclick="document.getElementById('message').value = this.textContent.trim()">
                    <?= esc($suggestion) ?>
                </button>
            <?php endforeach ?>
        </div>

        <button type="submit" class="btn btn-paw w-100 mt-3">Ask PawDoc</button>
        <div class="small text-muted mt-2 text-center">
            PawDoc gives general information, not a diagnosis. In an emergency, call the clinic right away.
        </div>
    </form>

    <!-- Earlier conversations -->
    <?php if ($conversations !== []): ?>
        <h3 class="brand-font h5 mt-4 mb-3">Earlier questions</h3>
        <?php foreach ($conversations as $c): ?>
            <a href="<?= site_url('chat/' . $c['id']) ?>" class="paw-card conversation-link mb-2">
                <div class="fw-semibold"><?= esc($c['title'] ?: 'Conversation') ?></div>
                <div class="small text-muted">
                    <?= $c['pet_name'] ? esc($c['pet_name']) . ' · ' : '' ?><?= date('M j, g:i A', strtotime($c['updated_at'])) ?>
                </div>
            </a>
        <?php endforeach ?>
    <?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// The answer can take a few seconds: show that PawDoc is working, and stop double clicks
function thinking(form) {
    const button = form.querySelector('button[type=submit]');
    button.disabled = true;
    button.textContent = 'PawDoc is thinking…';
}
</script>
<?= $this->endSection() ?>
