<?php

if (! function_exists('current_user')) {
    function current_user(): ?array
    {
        return session()->get('user');
    }
}

if (! function_exists('has_role')) {
    function has_role(string ...$roles): bool
    {
        return in_array(current_user()['role'] ?? null, $roles, true);
    }
}

if (! function_exists('status_badge')) {
    /**
     * Bootstrap badge for appointment statuses and triage levels.
     */
    function status_badge(?string $value): string
    {
        if ($value === null || $value === '') {
            return '<span class="badge text-bg-light">—</span>';
        }

        $classes = [
            'pending'   => 'warning',
            'confirmed' => 'primary',
            'completed' => 'success',
            'cancelled' => 'secondary',
            'no_show'   => 'dark',
            'checked_in' => 'info',
            'low'       => 'success',
            'medium'    => 'warning',
            'high'      => 'danger',
            'emergency' => 'danger',
        ];
        $class = $classes[$value] ?? 'light';

        // "no_show" → "No show"
        return '<span class="badge text-bg-' . $class . '">' . esc(ucfirst(str_replace('_', ' ', $value))) . '</span>';
    }
}

if (! function_exists('fmt_date')) {
    function fmt_date(?string $date, string $format = 'M d, Y'): string
    {
        return empty($date) ? '—' : date($format, strtotime($date));
    }
}

if (! function_exists('greeting')) {
    /**
     * "Good morning" / "Good afternoon" / "Good evening" based on the clinic's time.
     */
    function greeting(): string
    {
        $hour = (int) date('G');

        if ($hour < 12) {
            return 'Good morning';
        }

        return $hour < 18 ? 'Good afternoon' : 'Good evening';
    }
}

if (! function_exists('initials')) {
    /**
     * "Maria Dela Cruz" → "M" (used for the round avatar).
     */
    function initials(string $name): string
    {
        return strtoupper(mb_substr(trim($name), 0, 1));
    }
}

if (! function_exists('nav_menu')) {
    /**
     * The menu of the signed-in user's role (Redesign D3), used by the sidebar (desktop) and the bottom bar (phones).
     * 'desktop' => true: only in the sidebar, because the bottom bar has room for 5 items.
     * 'active' is true for the current page.
     */
    function nav_menu(): array
    {
        $menus = [
            'owner' => [
                ['label' => 'Home',         'icon' => 'bi-house-door',      'url' => 'owner'],
                ['label' => 'Appointments', 'icon' => 'bi-calendar2-check', 'url' => 'appointments', 'desktop' => true],
                ['label' => 'Timeline',     'icon' => 'bi-clock-history',   'url' => 'timeline'],
                ['label' => 'Meds',         'icon' => 'bi-capsule',         'url' => 'meds'],
                ['label' => 'Journal',      'icon' => 'bi-journal-medical', 'url' => 'journal'],
                ['label' => 'AI Chat',      'icon' => 'bi-chat-dots',       'url' => 'chat'],
            ],
            'vet' => [
                ['label' => 'Home',         'icon' => 'bi-house-door',      'url' => 'vet'],
                ['label' => 'Patients',     'icon' => 'bi-heart-pulse',     'url' => 'vet/patients'],
                ['label' => 'Appointments', 'icon' => 'bi-calendar2-check', 'url' => 'vet/appointments'],
                ['label' => 'Journals',     'icon' => 'bi-journal-medical', 'url' => 'vet/journals'],
            ],
            'admin' => [
                ['label' => 'Home',         'icon' => 'bi-house-door',      'url' => 'admin'],
                ['label' => 'Users',        'icon' => 'bi-people',          'url' => 'admin/users'],
                ['label' => 'Pets',         'icon' => 'bi-heart',           'url' => 'admin/pets'],
                ['label' => 'Appointments', 'icon' => 'bi-calendar2-check', 'url' => 'admin/appointments'],
            ],
        ];

        $role  = current_user()['role'] ?? '';
        $items = $menus[$role] ?? [];

        foreach ($items as &$item) {
            // Home (/vet) is active only on itself; the others also on their sub-pages (/vet/patients/5)
            $item['active']  = $item['url'] === $role ? url_is($item['url']) : url_is($item['url'] . '*');
            $item['desktop'] = $item['desktop'] ?? false;
        }

        return $items;
    }
}

if (! function_exists('triage_badge')) {
    /**
     * Step 15: badge for the AI urgency of an appointment request ('' when there is none).
     */
    function triage_badge(?string $level): string
    {
        $badges = [
            'emergency' => ['danger', '🚨 Emergency'],
            'high'      => ['warning', '⚠️ Urgent'],
            'medium'    => ['info', 'Soon'],
            'low'       => ['light border', 'Routine'],
        ];

        if (! isset($badges[$level])) {
            return '';
        }

        [$class, $label] = $badges[$level];

        return '<span class="badge text-bg-' . $class . '" title="AI urgency check">' . $label . '</span>';
    }
}
