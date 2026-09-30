<?php

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\AI;

/**
 * End-to-end HTTP tests against an in-memory SQLite database seeded with demo data.
 *
 * @internal
 */
final class ClinicFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;
    protected $seed        = \App\Database\Seeds\DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Force the offline triage so tests never call the real API.
        $ai         = new AI();
        $ai->apiKey = '';
        Factories::injectMock('config', AI::class, $ai);

        // CSRF is covered by the framework; disable it for request simulation.
        $filters                    = config('Filters');
        $filters->globals['before'] = array_values(array_diff($filters->globals['before'], ['csrf']));
    }

    private function loginAs(string $email): array
    {
        $user = $this->db->table('users')->where('email', $email)->get()->getRowArray();

        return ['user' => ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']]];
    }

    /** Next open clinic day (not Sunday) at least two days ahead. */
    private function futureDate(): string
    {
        $t = strtotime('+2 days');
        while ((int) date('N', $t) === 7) {
            $t = strtotime('+1 day', $t);
        }

        return date('Y-m-d', $t);
    }

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->get('dashboard')->assertRedirectTo('/login');
    }

    public function testLoginWithValidAndInvalidPassword(): void
    {
        $result = $this->post('login', ['email' => 'owner@vetclinic.test', 'password' => 'password123']);
        $result->assertRedirectTo('/dashboard');
        $result->assertSessionHas('user');

        $this->post('login', ['email' => 'owner@vetclinic.test', 'password' => 'wrong'])
            ->assertSessionHas('error', 'Invalid email or password.');
    }

    public function testRegistrationCreatesOwnerAccount(): void
    {
        $this->post('register', [
            'name'             => 'New Owner',
            'email'            => 'new@example.com',
            'password'         => 'longpassword',
            'password_confirm' => 'longpassword',
            'role'             => 'admin', // must be ignored
        ])->assertRedirectTo('/login');

        $this->seeInDatabase('users', ['email' => 'new@example.com', 'role' => 'owner']);
    }

    public function testOwnerCannotSeeAnotherOwnersPet(): void
    {
        $this->db->table('users')->insert(['name' => 'Other', 'email' => 'other@example.com', 'password_hash' => 'x', 'role' => 'owner', 'is_active' => 1]);
        $session = $this->loginAs('other@example.com');

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->withSession($session)->get('pets/1');
    }

    public function testOwnerCannotReachStaffPages(): void
    {
        $this->withSession($this->loginAs('owner@vetclinic.test'))->get('owners')->assertRedirectTo('/dashboard');
        $this->withSession($this->loginAs('owner@vetclinic.test'))->get('users')->assertRedirectTo('/dashboard');
    }

    public function testBookingIsTriagedAndDoubleBookingIsRejected(): void
    {
        $session = $this->loginAs('owner@vetclinic.test');
        $vetId   = $this->db->table('users')->where('email', 'vet@vetclinic.test')->get()->getRow()->id;
        $date    = $this->futureDate();

        $booking = [
            'pet_id'           => 1,
            'vet_id'           => $vetId,
            'appointment_date' => $date,
            'appointment_time' => '10:00',
            'duration_minutes' => 30,
            'reason'           => 'He is having a seizure and collapsed',
        ];

        $this->withSession($session)->post('appointments', $booking)->assertRedirect();
        $this->seeInDatabase('appointments', ['appointment_date' => $date, 'status' => 'pending', 'triage_level' => 'emergency']);

        // Overlapping slot for the same vet
        $booking['appointment_time'] = '10:15';
        $this->withSession($session)->post('appointments', $booking)
            ->assertSessionHas('error', 'The selected veterinarian already has an appointment at that time.');
        $this->assertSame(1, $this->db->table('appointments')->where('appointment_date', $date)->countAllResults());
    }

    public function testSlotsEndpointExcludesBookedTimes(): void
    {
        $vetId = $this->db->table('users')->where('email', 'vet@vetclinic.test')->get()->getRow()->id;
        $date  = $this->futureDate();
        $this->db->table('appointments')->insert([
            'pet_id' => 1, 'owner_id' => 5, 'vet_id' => $vetId, 'appointment_date' => $date,
            'appointment_time' => '09:00:00', 'duration_minutes' => 60, 'reason' => 'Check', 'status' => 'confirmed',
        ]);

        $result = $this->withSession($this->loginAs('owner@vetclinic.test'))
            ->get("appointments/slots?date={$date}&vet_id={$vetId}&duration=30");
        $slots = json_decode($result->getJSON(), true)['slots'];

        $this->assertContains('08:30', $slots);
        $this->assertNotContains('09:00', $slots);
        $this->assertNotContains('09:30', $slots);
        $this->assertContains('10:00', $slots);
    }

    public function testVetRecordCompletesAppointment(): void
    {
        $session = $this->loginAs('vet@vetclinic.test');

        $this->withSession($session)->post('records', [
            'pet_id'         => 1,
            'appointment_id' => 1,
            'visit_date'     => date('Y-m-d'),
            'weight_kg'      => '13.40',
            'diagnosis'      => 'Healthy',
        ])->assertRedirect();

        $this->seeInDatabase('appointments', ['id' => 1, 'status' => 'completed']);
        $this->seeInDatabase('pets', ['id' => 1, 'weight_kg' => '13.40']);
    }
}
