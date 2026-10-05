<?php
/** Sidebar for big screens (Redesign D3): logo, the role's menu, notifications, and the signed-in user. */
$user = current_user();
?>
<aside class="app-sidebar d-none d-lg-flex">
    <a href="<?= site_url('home') ?>" class="side-logo">
        <span class="side-logo-badge">🐾</span>
        <span class="side-logo-name">pawrecord</span>
    </a>

    <nav class="side-nav">
        <?php foreach (nav_menu() as $item): ?>
            <a href="<?= site_url($item['url']) ?>" class="side-link <?= $item['active'] ? 'is-active' : '' ?>">
                <i class="bi <?= $item['icon'] ?>"></i> <?= esc($item['label']) ?>
            </a>
        <?php endforeach ?>

        <a href="<?= site_url('notifications') ?>" class="side-link <?= url_is('notifications*') ? 'is-active' : '' ?>">
            <i class="bi bi-bell"></i> Notifications
            <?php if ($unread > 0): ?>
                <span class="side-count"><?= $unread > 9 ? '9+' : $unread ?></span>
            <?php endif ?>
        </a>
    </nav>

    <!-- Signed-in user + sign out -->
    <div class="side-user">
        <div class="avatar avatar-sm"><?= esc(initials($user['full_name'])) ?></div>
        <div class="side-user-text">
            <div class="side-user-name"><?= esc($user['full_name']) ?></div>
            <div class="side-user-role"><?= esc(\App\Models\UserModel::ROLE_LABELS[$user['role']]) ?></div>
        </div>
        <form action="<?= site_url('logout') ?>" method="post" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="side-logout" title="Sign out" aria-label="Sign out"><i class="bi bi-box-arrow-right"></i></button>
        </form>
    </div>
</aside>
