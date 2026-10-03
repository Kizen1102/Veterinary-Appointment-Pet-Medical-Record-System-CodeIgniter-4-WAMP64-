<?php
/**
 * Bottom navigation bar. Each role sees its own menu.
 * 'url' => null means the page is not built yet: it is shown greyed out with "Soon".
 * In each next step we simply fill in the url.
 */
$menus = [
    'owner' => [
        ['label' => 'Home',     'icon' => '🏠', 'url' => 'owner'],
        ['label' => 'AI Chat',  'icon' => '🤖', 'url' => 'chat'],
        ['label' => 'Timeline', 'icon' => '📅', 'url' => 'timeline'],
        ['label' => 'Meds',     'icon' => '💊', 'url' => 'meds'],
        ['label' => 'Journal',  'icon' => '🐾', 'url' => 'journal'],
    ],
    'vet' => [
        ['label' => 'Home',         'icon' => '🏠', 'url' => 'vet'],
        ['label' => 'Patients',     'icon' => '🐶', 'url' => 'vet/patients'],
        ['label' => 'Appointments', 'icon' => '📋', 'url' => 'vet/appointments'],
        ['label' => 'Journals',     'icon' => '🐾', 'url' => 'vet/journals'],
    ],
    'admin' => [
        ['label' => 'Home',         'icon' => '🏠', 'url' => 'admin'],
        ['label' => 'Users',        'icon' => '👥', 'url' => 'admin/users'],
        ['label' => 'Pets',         'icon' => '🐶', 'url' => 'admin/pets'],
        ['label' => 'Appointments', 'icon' => '📋', 'url' => 'admin/appointments'],
    ],
];

$role  = current_user()['role'];
$items = $menus[$role] ?? [];
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
            <?php
            // Home (/vet) is active only on itself; the others also on their sub-pages (/vet/patients/5)
            $active = $item['url'] === $role ? url_is($item['url']) : url_is($item['url'] . '*');
            ?>
            <a href="<?= site_url($item['url']) ?>"
               class="bottom-nav-item <?= $active ? 'is-active' : '' ?>">
                <span class="icon"><?= $item['icon'] ?></span>
                <?= esc($item['label']) ?>
            </a>
        <?php endif ?>
    <?php endforeach ?>
</nav>
