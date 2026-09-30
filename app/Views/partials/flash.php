<?php foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $key => $class): ?>
    <?php if (session()->getFlashdata($key)): ?>
        <div class="alert alert-<?= $class ?> alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata($key)) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>
<?php endforeach ?>

<?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ((array) $errors as $e): ?>
                <li><?= esc($e) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>
