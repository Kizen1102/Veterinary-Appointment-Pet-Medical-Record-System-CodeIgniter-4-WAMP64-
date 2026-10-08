<?php

namespace App\Validation;

/**
 * Validation rule "full_name": the person's real first and last name.
 *
 *   ok:  Juan Dela Cruz · Ma. Clara Santos · José Rizal Jr. · Anne-Marie O'Brien · Dr. Ana Cruz
 *   no:  Juan · juan123 · J D · @@@
 *
 * Used by the sign-up, first-time setup and "Add staff" forms ('rules' => 'required|full_name|...').
 */
class NameRules
{
    /** Shown for an empty, one-word or fake name (also used for "required" in the forms). */
    public const MESSAGE = 'Please enter your full name.';

    public function full_name(?string $str, ?string &$error = null): bool
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $str));

        // Only letters (also ñ, é ...), spaces, periods, apostrophes and hyphens
        if (! preg_match("/^[\\p{L}][\\p{L} .'-]*$/u", $name)) {
            $error = self::MESSAGE;

            return false;
        }

        // At least two names with 2 or more letters each, e.g. a first name and a last name
        $words = array_filter(explode(' ', $name), static fn ($word) => preg_match_all('/\p{L}/u', $word) >= 2);

        if (count($words) < 2) {
            $error = self::MESSAGE;

            return false;
        }

        return true;
    }
}
