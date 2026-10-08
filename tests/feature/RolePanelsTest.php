<?php

use App\Models\AppointmentModel;
use App\Models\JournalAiSummaryModel;
use App\Models\MedicalRecordModel;
use App\Models\MedicationModel;
use App\Models\MedicationScheduleModel;
use App\Models\NotificationModel;
use App\Models\PetModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Filters;

/**
 * Opens the real pages as each role (Steps 11–12): who may open what,
 * and what the vet and admin forms save.
 *
 * @internal
 */
final class RolePanelsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    private array $owner;
    private array $vet;
    private array $admin;
    private int $petId;

    protected function setUp(): void
    {
        parent::setUp();

        // The forms' CSRF tokens are checked in the browser tests; here the POSTs go straight to the controllers
        $filters                    = config(Filters::class);
        $filters->globals['before'] = array_values(array_diff($filters->globals['before'], ['csrf']));

        // Never call the real AI from the tests, even when .env has an API key
        config(\Config\AI::class)->apiKey = '';

        $users       = new UserModel();
        $this->owner = $users->find($users->insert(['role' => 'owner', 'full_name' => 'Maria Santos', 'email' => 'owner@example.com', 'password_hash' => 'x']));
        $this->vet   = $users->find($users->insert(['role' => 'vet', 'full_name' => 'Dr. Ana Cruz', 'email' => 'vet@example.com', 'password_hash' => 'x']));
        $this->admin = $users->find($users->insert(['role' => 'admin', 'full_name' => 'Clinic Admin', 'email' => 'admin@example.com', 'password_hash' => 'x']));
        $this->petId = (int) (new PetModel())->insert(['owner_id' => $this->owner['id'], 'name' => 'Bantay', 'species' => 'Dog']);
    }

    /** Requests are made as this user (what the login page puts in the session). */
    private function as(array $user): self
    {
        return $this->withSession(['user' => [
            'id' => $user['id'], 'role' => $user['role'], 'full_name' => $user['full_name'], 'email' => $user['email'],
        ]]);
    }

    public function testGuestsAreSentToTheLoginPage(): void
    {
        foreach (['owner', 'vet', 'vet/patients', 'admin', 'admin/users'] as $page) {
            $this->get($page)->assertRedirectTo('/login');
        }
    }

    public function testEachRoleOnlyOpensItsOwnPanel(): void
    {
        $this->as($this->owner)->get('vet/patients')->assertRedirectTo('/home');
        $this->as($this->owner)->get('admin/users')->assertRedirectTo('/home');
        $this->as($this->vet)->get('admin/users')->assertRedirectTo('/home');
        $this->as($this->admin)->get('vet/patients')->assertRedirectTo('/home');

        $pages = [
            [$this->vet, 'vet', 'Today'],
            [$this->vet, 'vet/patients', 'Bantay'],
            [$this->admin, 'admin', 'Requests with no vet'],
            [$this->admin, 'admin/users', 'Dr. Ana Cruz'],
        ];
        foreach ($pages as [$user, $page, $text]) {
            $response = $this->as($user)->get($page);
            $response->assertOK();
            $response->assertSee($text);
        }
    }

    public function testDeactivatedUsersAreSignedOut(): void
    {
        (new UserModel())->update($this->owner['id'], ['is_active' => 0]);

        $this->as($this->owner)->get('owner')->assertRedirectTo('/login');
    }

    public function testPatientSearch(): void
    {
        $pets = new PetModel();

        $this->assertCount(1, $pets->patients('maria'));  // owner name
        $this->assertCount(1, $pets->patients('bant'));   // part of the pet name
        $this->assertCount(0, $pets->patients('zzz'));
        $this->assertNull($pets->patients()[0]['last_visit']);
    }

    public function testVetRecordCompletesTheVisitAndNotifiesTheOwner(): void
    {
        $appointmentId = (new AppointmentModel())->insert([
            'pet_id' => $this->petId, 'owner_id' => $this->owner['id'], 'vet_id' => $this->vet['id'],
            'appointment_type' => 'consultation', 'scheduled_at' => date('Y-m-d 08:00:00'), 'status' => 'confirmed',
        ]);

        $this->as($this->vet)->post('vet/patients/' . $this->petId . '/records', [
            'appointment_id' => $appointmentId,
            'record_type'    => 'consultation',
            'visit_date'     => date('Y-m-d'),
            'title'          => 'Ear infection check',
            'weight_kg'      => '12.5',
            'diagnosis'      => 'Otitis externa',
        ])->assertRedirectTo('/vet/patients/' . $this->petId);

        $record = (new MedicalRecordModel())->where('pet_id', $this->petId)->first();
        $this->assertSame('Ear infection check', $record['title']);
        $this->assertNull($record['temperature_c']); // empty box saved as NULL
        $this->assertSame('completed', (new AppointmentModel())->find($appointmentId)['status']);
        $this->assertEquals(12.5, (new PetModel())->find($this->petId)['weight_kg']);
        $this->assertCount(1, (new NotificationModel())->unreadFor($this->owner['id']));
    }

    public function testVetRecordRejectsAFutureVisitDate(): void
    {
        $this->as($this->vet)->post('vet/patients/' . $this->petId . '/records', [
            'record_type' => 'consultation',
            'visit_date'  => date('Y-m-d', strtotime('+3 days')),
            'title'       => 'Too early',
        ]);

        $this->assertSame(0, (new MedicalRecordModel())->countAllResults());
    }

    public function testVetPrescriptionCreatesDoseTimes(): void
    {
        $this->as($this->vet)->post('vet/patients/' . $this->petId . '/prescriptions', [
            'name'       => 'Otomax',
            'dosage'     => '4 drops',
            'form'       => 'drops',
            'start_date' => date('Y-m-d'),
            'times'      => ['20:00', '08:00', '', '08:00'], // unsorted, empty box, duplicate
        ]);

        $medication = (new MedicationModel())->where('pet_id', $this->petId)->first();
        $this->assertSame((string) $this->vet['id'], (string) $medication['prescribed_by']);

        $times = array_column((new MedicationScheduleModel())->forMedication((int) $medication['id']), 'dose_time');
        $this->assertSame(['08:00:00', '20:00:00'], $times);
    }

    public function testVetMarksAJournalSummaryAsReviewed(): void
    {
        $summaries = new JournalAiSummaryModel();
        $id        = $summaries->insert([
            'pet_id' => $this->petId, 'period_start' => date('Y-m-d', strtotime('-1 day')), 'period_end' => date('Y-m-d'),
            'entries_count' => 2, 'summary' => 'Appetite dropped.', 'concern_level' => 'low',
        ]);

        $this->assertNull($summaries->forVets()[0]['reviewed_at']);

        $this->as($this->vet)->post('vet/journals/' . $id . '/review');

        $this->assertSame((string) $this->vet['id'], (string) $summaries->find($id)['reviewed_by']);
    }

    public function testAdminCreatesAVetAccount(): void
    {
        $this->as($this->admin)->post('admin/users', [
            'role'             => 'vet',
            'full_name'        => 'Dr. Carla Lim',
            'email'            => 'Carla@Example.com',
            'license_number'   => 'PRC-123',
            'password'         => 'password123',
            'password_confirm' => 'password123',
        ])->assertRedirectTo('/admin/users?role=vet');

        $vet = (new UserModel())->findByEmail('carla@example.com');
        $this->assertSame('vet', $vet['role']);
        $this->assertSame('PRC-123', $vet['license_number']);
        $this->assertTrue(password_verify('password123', $vet['password_hash']));
    }

    public function testAdminCannotCreateAccountsWithShortPasswordsOrUsedEmails(): void
    {
        $users  = new UserModel();
        $before = $users->countAllResults();

        $this->as($this->admin)->post('admin/users', [
            'role' => 'vet', 'full_name' => 'Short Pass', 'email' => 'new@example.com',
            'password' => 'abc', 'password_confirm' => 'abc',
        ]);
        $this->as($this->admin)->post('admin/users', [
            'role' => 'vet', 'full_name' => 'Used Email', 'email' => 'vet@example.com',
            'password' => 'password123', 'password_confirm' => 'password123',
        ]);
        $this->as($this->admin)->post('admin/users', [
            'role' => 'owner', 'full_name' => 'Wrong Role', 'email' => 'owner2@example.com',
            'password' => 'password123', 'password_confirm' => 'password123',
        ]);

        $this->assertSame($before, $users->countAllResults());
    }

    public function testAdminDeactivatesOthersButNotThemself(): void
    {
        $this->as($this->admin)->post('admin/users/' . $this->vet['id'] . '/toggle');
        $this->as($this->admin)->post('admin/users/' . $this->admin['id'] . '/toggle');

        $users = new UserModel();
        $this->assertSame('0', (string) $users->find($this->vet['id'])['is_active']);
        $this->assertSame('1', (string) $users->find($this->admin['id'])['is_active']);
    }

    public function testAdminAssignsAVetWithoutDoubleBooking(): void
    {
        $appointments = new AppointmentModel();
        $when         = date('Y-m-d 10:00:00', strtotime('+2 days'));
        $base         = ['pet_id' => $this->petId, 'owner_id' => $this->owner['id'], 'appointment_type' => 'consultation', 'scheduled_at' => $when];

        $appointments->insert($base + ['vet_id' => $this->vet['id'], 'status' => 'confirmed']);
        $requestId = $appointments->insert($base + ['status' => 'pending']);

        $this->assertCount(1, $appointments->forAdminTab('unassigned'));

        // Same time as the vet's other visit → refused
        $this->as($this->admin)->post('admin/appointments/' . $requestId . '/assign', ['vet_id' => $this->vet['id']]);
        $this->assertNull($appointments->find($requestId)['vet_id']);

        // A free vet → assigned, and the vet is notified
        $otherVet = (new UserModel())->insert(['role' => 'vet', 'full_name' => 'Dr. Ben Reyes', 'email' => 'ben@example.com', 'password_hash' => 'x']);
        $this->as($this->admin)->post('admin/appointments/' . $requestId . '/assign', ['vet_id' => $otherVet]);
        $this->assertSame((string) $otherVet, (string) $appointments->find($requestId)['vet_id']);
        $this->assertCount(1, (new NotificationModel())->unreadFor($otherVet));

        // An owner account is never accepted as a vet
        $this->as($this->admin)->post('admin/appointments/' . $requestId . '/assign', ['vet_id' => $this->owner['id']]);
        $this->assertSame((string) $otherVet, (string) $appointments->find($requestId)['vet_id']);
    }

    public function testOwnerGetsTheirVisitExplainedButNotOtherOwnersVisits(): void
    {
        $records  = new MedicalRecordModel();
        $recordId = $records->insert([
            'pet_id' => $this->petId, 'vet_id' => $this->vet['id'], 'record_type' => 'consultation',
            'visit_date' => date('Y-m-d'), 'title' => 'Ear check', 'diagnosis' => 'Otitis externa', 'vet_notes' => 'Private note',
        ]);

        // The record page never shows the vet's private notes
        $page = $this->as($this->owner)->get('timeline/records/' . $recordId);
        $page->assertOK();
        $page->assertSee('Otitis externa');
        $page->assertDontSee('Private note');

        // The explanation is saved on the record (offline explainer in tests: no API key)
        $this->as($this->owner)->post('timeline/records/' . $recordId . '/explain')
            ->assertRedirectTo('/timeline/records/' . $recordId . '#explain');
        $this->assertStringContainsString('Otitis externa', (string) $records->find($recordId)['owner_summary']);

        // Another owner cannot open or explain it
        $other = (new UserModel())->insert(['role' => 'owner', 'full_name' => 'Other Owner', 'email' => 'other@example.com', 'password_hash' => 'x']);
        $otherOwner = (new UserModel())->find($other);
        $records->update($recordId, ['owner_summary' => null]);

        $this->as($otherOwner)->get('timeline/records/' . $recordId)->assertRedirectTo('/timeline');
        $this->as($otherOwner)->post('timeline/records/' . $recordId . '/explain');
        $this->assertNull($records->find($recordId)['owner_summary']);
    }

    public function testBookingChecksUrgencyAndPutsUrgentRequestsFirst(): void
    {
        $monday = date('Y-m-d', strtotime('next monday'));
        $book   = fn (string $time, string $reason) => $this->as($this->owner)->post('appointments', [
            'pet_id' => $this->petId, 'appointment_type' => 'consultation', 'date' => $monday, 'time' => $time, 'reason' => $reason,
        ]);

        $book('09:00', 'Yearly check-up');
        $response = $book('10:00', 'He collapsed and has difficulty breathing');
        $response->assertRedirectTo('/appointments');
        $response->assertSessionHas('triage');

        $appointments = new AppointmentModel();
        $this->assertSame('low', $appointments->where('reason', 'Yearly check-up')->first()['triage_level']);
        $urgent = $appointments->where('reason', 'He collapsed and has difficulty breathing')->first();
        $this->assertSame('emergency', $urgent['triage_level']);
        $this->assertNotEmpty($urgent['triage_summary']);

        // The later but urgent request is first for the vet and the admin
        $this->assertSame($urgent['id'], $appointments->forVetTab($this->vet['id'], 'pending')[0]['id']);
        $this->assertSame($urgent['id'], $appointments->forAdminTab('unassigned')[0]['id']);

        $page = $this->as($this->vet)->get('vet/appointments?tab=pending');
        $page->assertSee('Emergency');
    }

    public function testClinicStaffAndVetAreNotifiedOfNewAndCancelledRequests(): void
    {
        $monday        = date('Y-m-d', strtotime('next monday'));
        $notifications = new NotificationModel();

        // No vet chosen: only the clinic staff are told, with a link to "No vet yet"
        $this->as($this->owner)->post('appointments', [
            'pet_id' => $this->petId, 'appointment_type' => 'consultation', 'date' => $monday, 'time' => '09:00',
            'reason' => 'He ate chocolate and is shaking',
        ]);
        $staffNote = $notifications->where('user_id', $this->admin['id'])->first();
        $this->assertStringContainsString('Emergency request: Bantay', $staffNote['title']);
        $this->assertStringContainsString('Maria Santos booked', $staffNote['message']);
        $this->assertSame('admin/appointments?tab=unassigned', $staffNote['link_url']);
        $this->assertSame(0, $notifications->where('user_id', $this->vet['id'])->countAllResults());

        // A vet chosen: the vet is told too
        $this->as($this->owner)->post('appointments', [
            'pet_id' => $this->petId, 'appointment_type' => 'wellness_exam', 'date' => $monday, 'time' => '10:00', 'vet_id' => $this->vet['id'],
        ]);
        $vetNote = $notifications->where('user_id', $this->vet['id'])->first();
        $this->assertSame('New appointment request: Bantay', $vetNote['title']);
        $this->assertSame('vet/appointments?tab=pending', $vetNote['link_url']);

        // The owner cancels it: staff and vet are told again
        $appointment = (new AppointmentModel())->where('vet_id', $this->vet['id'])->first();
        $this->as($this->owner)->post('appointments/' . $appointment['id'] . '/cancel');
        $this->assertSame(2, $notifications->where('user_id', $this->vet['id'])->countAllResults());
        $this->assertSame(3, $notifications->where('user_id', $this->admin['id'])->countAllResults());
        $this->assertSame(0, $notifications->where('user_id', $this->owner['id'])->countAllResults());
    }
}
