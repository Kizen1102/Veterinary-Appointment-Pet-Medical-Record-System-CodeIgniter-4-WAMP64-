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
            'low'       => 'success',
            'medium'    => 'warning',
            'high'      => 'danger',
            'emergency' => 'danger',
        ];
        $class = $classes[$value] ?? 'light';

        return '<span class="badge text-bg-' . $class . '">' . esc(ucfirst($value)) . '</span>';
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
