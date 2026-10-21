<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= site_url('chat') ?>" class="text-decoration-none small">← All questions</a>
        <form action="<?= site_url('chat/' . $conversation['id'] . '/delete') ?>" method="post"
              onsubmit="return confirm('Delete this conversation?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
        </form>
    </div>

    <h1 class="brand-font h4 mb-1"><?= esc($conversation['title'] ?: 'Conversation') ?></h1>
    <?php if ($conversation['pet_name']): ?>
        <div class="small text-muted mb-3">About <?= esc($conversation['pet_name']) ?></div>
    <?php endif ?>

    <!-- Messages: owner on the right, PawDoc on the left -->
    <div class="chat-thread mb-3">
        <?php foreach ($messages as $m): ?>
            <div class="chat-bubble <?= $m['sender'] === 'user' ? 'is-user' : 'is-bot' ?>">
                <?php if ($m['sender'] === 'assistant'): ?>
                    <div class="small fw-bold mb-1">🤖 PawDoc</div>
                <?php endif ?>
                <!-- nl2br keeps the line breaks; esc() first so no HTML from the text can run -->
                <div><?= nl2br(esc($m['content'])) ?></div>
                <div class="chat-time"><?= date('g:i A', strtotime($m['created_at'])) ?></div>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Follow-up question -->
    <form action="<?= site_url('chat/' . $conversation['id']) ?>" method="post" class="chat-input" id="bottom" onsubmit="thinking(this)">
        <?= csrf_field() ?>
        <textarea class="form-control" name="message" rows="2" maxlength="1000" required placeholder="Ask a follow-up question"></textarea>
        <button type="submit" class="btn btn-paw">Send</button>
    </form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Show the newest message first
window.scrollTo(0, document.body.scrollHeight);

// The answer can take a few seconds: show that PawDoc is working, and stop double clicks
function thinking(form) {
    const button = form.querySelector('button[type=submit]');
    button.disabled = true;
    button.textContent = 'Thinking…';
}
</script>
<?= $this->endSection() ?>
