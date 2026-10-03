<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Clinic operating settings used by appointment booking.
 */
class Clinic extends BaseConfig
{
    public string $name = 'PawCare Veterinary Clinic';

    /** Opening / closing time (24h, HH:MM). Appointments must end by closing time. */
    public string $openTime  = '08:00';
    public string $closeTime = '17:00';

    /** Granularity of bookable slots, in minutes. */
    public int $slotMinutes = 30;

    /** Days the clinic is closed (ISO-8601: 1 = Monday … 7 = Sunday). */
    public array $closedDays = [7];

    /** Shown on the landing page status bar, e.g. "Mon–Sat, 8:00 AM–5:00 PM". */
    public string $daysLabel = 'Mon–Sat';
}
