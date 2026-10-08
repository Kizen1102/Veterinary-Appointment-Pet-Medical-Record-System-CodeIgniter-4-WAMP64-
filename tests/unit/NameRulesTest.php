<?php

use App\Validation\NameRules;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * The "full_name" rule of the sign-up, setup and Add staff forms.
 *
 * @internal
 */
final class NameRulesTest extends CIUnitTestCase
{
    public function testRealFullNamesPass(): void
    {
        $rules = new NameRules();

        foreach (['Juan Dela Cruz', 'Ma. Clara Santos', 'José Rizal Jr.', "Anne-Marie O'Brien", 'Dr. Ana Cruz', 'Niño  Reyes'] as $name) {
            $this->assertTrue($rules->full_name($name), $name);
        }
    }

    public function testIncompleteOrFakeNamesFail(): void
    {
        $rules = new NameRules();

        foreach (['Juan', '  Juan  ', 'J D', 'juan123 cruz', '@@@ ###', 'Juan_Dela Cruz', '', 'Dr. Ana', 'Dra. Cruz'] as $name) {
            $this->assertFalse($rules->full_name($name, $error), $name);
            $this->assertNotEmpty($error);
        }
    }
}
