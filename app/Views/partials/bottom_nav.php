<?php
/**
 * Bottom navigation bar. Each role sees its own menu.
 * 'url' => null means the page is not built yet: it is shown greyed out with "Soon".
 * In each next step we simply fill in the url.
 */
$menus = [
    'owner' => [
        ['label' => 'Home',     'icon' => '🏠', 'url' => 'owner'],
        ['label' => 'AI Chat',  'icon' => '🤖', 'url' => null],
        ['label' => 'Timeline', 'icon' => '📅', 'url' => 'timeline'],
        ['label' => 'Meds',     'icon' => '💊', 'url' => null],
        ['label' => 'Journal',  'icon' => '🐾', 'url' => null],
    ],
    'vet' => [
        ['label' => 'Home',         'icon' => '🏠', 'url' => 'vet'],
        ['label' => 'Patients',     'icon' => '🐶', 'url' => null],
        ['label' => 'Appointments', 'icon' => '📋', 'url' => null],
        ['label' => 'Journals',     'icon' => '🐾', 'url' => null],
    ],
    'admin' => [
        ['label' => 'Home',         'icon' => '🏠', 'url' => 'admin'],
        ['label' => 'Users',        'icon' => '👥', 'url' => null],
        ['label' => 'Pets',         'icon' => '🐶', 'url' => null],
        ['label' => 'Appointments', 'icon' => '📋', 'url' => null],
    ],
];

$items = $menus[current_user()['role']] ?? [];
?>
<nav class="bottom-nav">
    <?php foreach ($items as $item): ?>
        <?php if ($item['url'] === null): ?>
            <span class="bottom-nav-item is-soon" title="Coming soon">
                <span class="icon"><?= $item['icon'] ?></span>
                <?= esc($item['label']) ?>
                <small>Soon</small>
            </span>
        <?php else: ?>
            <a href="<?= site_url($item['url']) ?>"
               class="bottom-nav-item <?= url_is($item['url'] . '*') ? 'is-active' : '' ?>">
                <span class="icon"><?= $item['icon'] ?></span>
                <?= esc($item['label']) ?>
            </a>
        <?php endif ?>
    <?php endforeach ?>
</nav>
