<?php /* Flash messages: success (green), error (red), errors (red list from validation) */ ?>
<?php if (session('success')): ?>
    <div class="alert alert-success"><?= esc(session('success')) ?></div>
<?php endif ?>

<?php if (session('error')): ?>
    <div class="alert alert-danger"><?= esc(session('error')) ?></div>
<?php endif ?>

<?php if (session('errors')): ?>
    <div class="alert alert-danger">
        <?php foreach (session('errors') as $error): ?>
            <div><?= esc($error) ?></div>
        <?php endforeach ?>
    </div>
<?php endif ?>
