<?php

use App\Database\Seeds\DemoSeeder;
use App\Models\AppointmentModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * The demo seeder (php spark db:seed DemoSeeder) fills every screen and runs only once.
 *
 * @internal
 */
final class DemoSeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    public function testDemoDataIsAddedOnce(): void
    {
        ob_start();
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class); // second run adds nothing
        $output = ob_get_clean();

        $this->assertStringContainsString('already loaded', $output);
        $this->assertSame(6, $this->db->table('users')->countAllResults());
        $this->assertSame(4, $this->db->table('pets')->countAllResults());
        $this->assertSame(11, $this->db->table('appointments')->countAllResults());
        $this->assertSame(1, $this->db->table('journal_ai_summaries')->countAllResults());

        // Every demo account signs in with the documented password
        $ana = (new UserModel())->findByEmail('ana@pawrecord.test');
        $this->assertTrue(password_verify(DemoSeeder::PASSWORD, $ana['password_hash']));

        // The chocolate emergency is the first request the clinic sees
        $first = (new AppointmentModel())->forAdminTab('unassigned')[0];
        $this->assertSame('emergency', $first['triage_level']);
        $this->assertStringContainsString('chocolate', $first['reason']);
    }
}
