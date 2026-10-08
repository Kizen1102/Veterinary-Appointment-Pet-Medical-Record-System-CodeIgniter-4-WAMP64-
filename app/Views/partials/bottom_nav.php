<?php
/** Bottom navigation bar for phones (hidden on big screens, where the sidebar is shown). Items come from nav_menu(). */
?>
<nav class="bottom-nav d-lg-none">
    <?php foreach (nav_menu() as $item): ?>
        <?php if (! $item['desktop']): ?>
            <a href="<?= site_url($item['url']) ?>" class="bottom-nav-item <?= $item['active'] ? 'is-active' : '' ?>">
                <i class="bi <?= $item['icon'] ?> icon"></i>
                <?= esc($item['label']) ?>
            </a>
        <?php endif ?>
    <?php endforeach ?>
</nav>
