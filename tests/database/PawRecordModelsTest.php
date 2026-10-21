<?php

use App\Models\AppointmentModel;
use App\Models\JournalEntryModel;
use App\Models\MedicationAdherenceModel;
use App\Models\MedicationLogModel;
use App\Models\MedicationModel;
use App\Models\PetHealthTimelineModel;
use App\Models\PetModel;
use App\Models\UserModel;
use App\Models\VaccinationModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Runs the migrations on the test database and exercises the PawRecord models.
 * View tests run only on MySQL / MariaDB.
 *
 * @internal
 */
final class PawRecordModelsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    private int $ownerId;
    private int $vetId;
    private int $petId;

    protected function setUp(): void
    {
        parent::setUp();

        $users         = new UserModel();
        $this->ownerId = (int) $users->insert(['role' => 'owner', 'full_name' => 'Test Owner', 'email' => 'Owner@Example.com', 'password_hash' => password_hash('x', PASSWORD_DEFAULT)]);
        $this->vetId   = (int) $users->insert(['role' => 'vet', 'full_name' => 'Dr. Test', 'email' => 'vet@example.com', 'password_hash' => 'x', 'license_number' => 'PRC-1']);
        $this->petId   = (int) (new PetModel())->insert(['owner_id' => $this->ownerId, 'primary_vet_id' => $this->vetId, 'name' => 'Luna', 'species' => 'Cat', 'sex' => 'female', 'birth_date' => date('Y-m-d', strtotime('-5 years -2 days'))]);
    }

    private function isMySQL(): bool
    {
        return $this->db->DBDriver === 'MySQLi';
    }

    public function testUsersAreLowercasedAndUnique(): void
    {
        $users = new UserModel();

        $this->assertSame('owner@example.com', $users->find($this->ownerId)['email']);
        $this->assertNotNull($users->findByEmail('OWNER@example.com'));
        $this->assertFalse($users->hasAdmin());

        $this->assertFalse($users->insert(['role' => 'owner', 'full_name' => 'Dup', 'email' => 'owner@example.com', 'password_hash' => 'x']));
        $this->assertArrayHasKey('email', $users->errors());

        $this->assertFalse($users->insert(['role' => 'boss', 'full_name' => 'X', 'email' => 'x@example.com', 'password_hash' => 'x']));
    }

    public function testFirstAdminIsDetected(): void
    {
        (new UserModel())->insert(['role' => 'admin', 'full_name' => 'Admin', 'email' => 'admin@example.com', 'password_hash' => 'x']);

        $this->assertTrue((new UserModel())->hasAdmin());
    }

    public function testPetAgeLabelAndOwnerJoin(): void
    {
        $this->assertSame('5 yrs', PetModel::ageLabel(date('Y-m-d', strtotime('-5 years -2 days'))));
        $this->assertSame('3 mos', PetModel::ageLabel(date('Y-m-d', strtotime('-3 months -2 days'))));
        $this->assertSame('Unknown', PetModel::ageLabel(null));

        $pets = (new PetModel())->withPeople($this->ownerId);
        $this->assertSame('Test Owner', $pets[0]['owner_name']);
        $this->assertSame('Dr. Test', $pets[0]['vet_name']);
    }

    public function testVetDoubleBookingIsDetected(): void
    {
        $appointments = new AppointmentModel();
        $day          = date('Y-m-d', strtotime('+3 days'));

        $appointments->insert([
            'pet_id' => $this->petId, 'owner_id' => $this->ownerId, 'vet_id' => $this->vetId,
            'appointment_type' => 'wellness_exam', 'title' => 'Annual Wellness Exam',
            'scheduled_at' => "{$day} 10:00:00", 'duration_minutes' => 45, 'status' => 'confirmed',
        ]);

        $this->assertTrue($appointments->hasConflict($this->vetId, "{$day} 10:30:00", 30));
        $this->assertTrue($appointments->hasConflict($this->vetId, "{$day} 09:30:00", 45));
        $this->assertFalse($appointments->hasConflict($this->vetId, "{$day} 10:45:00", 30));
        $this->assertFalse($appointments->hasConflict($this->vetId, "{$day} 09:00:00", 60));

        $next = $appointments->nextForPet($this->petId);
        $this->assertSame('Annual Wellness Exam', $next['title']);
        $this->assertSame('Dr. Test', $next['vet_name']);
    }

    public function testDoseLoggingAndMissedDoses(): void
    {
        $medId = (int) (new MedicationModel())->insert([
            'pet_id' => $this->petId, 'prescribed_by' => $this->vetId, 'name' => 'Omega-3 Supplement',
            'dosage' => '500mg', 'form' => 'capsule', 'instructions' => 'with morning meal',
            'start_date' => date('Y-m-d', strtotime('-2 days')), 'total_doses' => 10,
        ]);

        $logs      = new MedicationLogModel();
        $yesterday = (int) $logs->insert(['medication_id' => $medId, 'scheduled_for' => date('Y-m-d 08:00:00', strtotime('-1 day'))]);
        $today     = (int) $logs->insert(['medication_id' => $medId, 'scheduled_for' => date('Y-m-d 23:59:00')]);

        $this->assertCount(1, $logs->todayForPet($this->petId));
        $this->assertSame('Omega-3 Supplement', $logs->todayForPet($this->petId)[0]['name']);

        $this->assertTrue($logs->markTaken($today, $this->ownerId));
        $this->assertSame(1, $logs->markOverdueAsMissed(60));

        $this->assertSame('missed', $logs->find($yesterday)['status']);
        $this->assertSame('taken', $logs->find($today)['status']);

        if ($this->isMySQL()) {
            $adherence = (new MedicationAdherenceModel())->forPet($this->petId)[0];
            $this->assertSame('50.0', (string) $adherence['adherence_pct']);
            $this->assertSame('10.0', (string) $adherence['completion_pct']);
        }
    }

    public function testJournalOneEntryPerDay(): void
    {
        $journal = new JournalEntryModel();
        $this->assertFalse($journal->hasEntryToday($this->petId));

        $journal->insert(['pet_id' => $this->petId, 'logged_by' => $this->ownerId, 'entry_date' => date('Y-m-d'), 'appetite_score' => 3, 'mood' => 'calm']);
        $this->assertTrue($journal->hasEntryToday($this->petId));
        $this->assertCount(1, $journal->recent($this->petId, 7));

        $this->assertFalse($journal->insert(['pet_id' => $this->petId, 'entry_date' => date('Y-m-d'), 'appetite_score' => 9]));
        $this->assertArrayHasKey('appetite_score', $journal->errors());
    }

    public function testVaccinesAndTimeline(): void
    {
        $vaccines = new VaccinationModel();
        $vaccines->insert(['pet_id' => $this->petId, 'vet_id' => $this->vetId, 'vaccine_name' => 'FVRCP', 'date_given' => date('Y-m-d', strtotime('-1 month')), 'next_due_date' => date('Y-m-d', strtotime('+11 months'))]);
        $this->assertTrue($vaccines->allCurrent($this->petId));

        $vaccines->insert(['pet_id' => $this->petId, 'vaccine_name' => 'Anti-Rabies', 'date_given' => date('Y-m-d', strtotime('-13 months')), 'next_due_date' => date('Y-m-d', strtotime('-1 month'))]);
        $this->assertFalse($vaccines->allCurrent($this->petId));

        if ($this->isMySQL()) {
            $timeline = (new PetHealthTimelineModel())->forPet($this->petId);
            $this->assertSame(['FVRCP', 'Anti-Rabies'], array_column($timeline, 'title'));
            $this->assertSame('vaccination', $timeline[0]['event_type']);
        }
    }

    public function testDeletingAPetCascadesItsHistory(): void
    {
        (new JournalEntryModel())->insert(['pet_id' => $this->petId, 'entry_date' => date('Y-m-d')]);

        // Hard delete (the app normally soft-deletes pets).
        (new PetModel())->delete($this->petId, true);

        $this->assertSame(0, $this->db->table('journal_entries')->countAllResults());
    }
}
