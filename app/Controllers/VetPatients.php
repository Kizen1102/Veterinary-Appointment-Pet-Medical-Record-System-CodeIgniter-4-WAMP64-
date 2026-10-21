<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\JournalAiSummaryModel;
use App\Models\JournalEntryModel;
use App\Models\MedicalRecordModel;
use App\Models\MedicationAdherenceModel;
use App\Models\MedicationModel;
use App\Models\MedicationScheduleModel;
use App\Models\NotificationModel;
use App\Models\PetModel;
use App\Models\VaccinationModel;

/**
 * Vets look up patients and write their medical records, vaccinations and prescriptions.
 * Everything saved here shows up in the owner's Timeline and Meds pages.
 */
class VetPatients extends BaseController
{
    // GET /vet/patients?q=name
    public function index()
    {
        $search = trim((string) $this->request->getGet('q'));

        return view('vet/patients', [
            'title'    => 'Patients',
            'search'   => $search,
            'patients' => (new PetModel())->patients($search),
        ]);
    }

    // GET /vet/patients/<id>
    public function show(int $id)
    {
        $pet = (new PetModel())->findWithPeople($id);
        if (! $pet) {
            return redirect()->to('/vet/patients')->with('error', 'Patient not found.');
        }

        // Latest journal summary made by the owner (Step 9)
        $summary = (new JournalAiSummaryModel())->latestForPet($id);
        if ($summary !== null) {
            $summary['notable_changes'] = json_decode((string) $summary['notable_changes'], true) ?: [];
        }

        // Active medications with their dose times and how well the owner gives them
        $adherence   = array_column((new MedicationAdherenceModel())->forPet($id), 'adherence_pct', 'medication_id');
        $schedules   = new MedicationScheduleModel();
        $medications = (new MedicationModel())->activeForPet($id);
        foreach ($medications as &$medication) {
            $medication['times']     = array_column($schedules->forMedication((int) $medication['id']), 'dose_time');
            $medication['adherence'] = $adherence[$medication['id']] ?? null;
        }
        unset($medication);

        return view('vet/patient', [
            'title'        => $pet['name'],
            'pet'          => $pet,
            'summary'      => $summary,
            'journal'      => array_reverse((new JournalEntryModel())->recent($id, 7)), // newest first
            'records'      => (new MedicalRecordModel())->forPet($id),
            'vaccinations' => (new VaccinationModel())->forPet($id),
            'medications'  => $medications,
        ]);
    }

    // "New medical record" form (GET /vet/patients/<id>/records/new?appointment=ID)
    public function newRecord(int $id)
    {
        $pet = (new PetModel())->findWithPeople($id);
        if (! $pet) {
            return redirect()->to('/vet/patients')->with('error', 'Patient not found.');
        }

        return view('vet/record_form', [
            'title'       => 'New Medical Record',
            'pet'         => $pet,
            'appointment' => $this->findVisit($id, (int) $this->request->getGet('appointment')),
        ]);
    }

    // Saves the medical record (POST /vet/patients/<id>/records)
    public function storeRecord(int $id)
    {
        $pet = (new PetModel())->find($id);
        if (! $pet) {
            return redirect()->to('/vet/patients')->with('error', 'Patient not found.');
        }

        $rules = [
            'record_type'      => ['label' => 'Type', 'rules' => 'required|in_list[' . implode(',', MedicalRecordModel::TYPES) . ']'],
            'visit_date'       => ['label' => 'Visit date', 'rules' => 'required|valid_date[Y-m-d]'],
            'title'            => ['label' => 'Title', 'rules' => 'required|max_length[150]'],
            'chief_complaint'  => ['label' => 'Chief complaint', 'rules' => 'permit_empty|max_length[1000]'],
            'weight_kg'        => ['label' => 'Weight', 'rules' => 'permit_empty|decimal|greater_than[0]|less_than[200]'],
            'temperature_c'    => ['label' => 'Temperature', 'rules' => 'permit_empty|decimal|greater_than_equal_to[25]|less_than_equal_to[45]'],
            'heart_rate_bpm'   => ['label' => 'Heart rate', 'rules' => 'permit_empty|is_natural_no_zero|less_than[400]'],
            'respiratory_rate' => ['label' => 'Breathing rate', 'rules' => 'permit_empty|is_natural_no_zero|less_than[200]'],
            'findings'         => ['label' => 'Findings', 'rules' => 'permit_empty|max_length[2000]'],
            'diagnosis'        => ['label' => 'Diagnosis', 'rules' => 'permit_empty|max_length[2000]'],
            'treatment'        => ['label' => 'Treatment', 'rules' => 'permit_empty|max_length[2000]'],
            'vet_notes'        => ['label' => 'Private notes', 'rules' => 'permit_empty|max_length[2000]'],
            'follow_up_date'   => ['label' => 'Follow-up date', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'follow_up_notes'  => ['label' => 'Follow-up notes', 'rules' => 'permit_empty|max_length[255]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $visitDate = $this->request->getPost('visit_date');
        $followUp  = $this->request->getPost('follow_up_date');
        if ($visitDate > date('Y-m-d')) {
            return redirect()->back()->withInput()->with('error', 'The visit date cannot be in the future.');
        }
        if ($followUp && $followUp <= $visitDate) {
            return redirect()->back()->withInput()->with('error', 'The follow-up date must be after the visit date.');
        }

        // The visit (appointment) this record is about, if the vet came from an appointment card
        $appointment = $this->findVisit($id, (int) $this->request->getPost('appointment_id'));

        $data = [
            'pet_id'         => $id,
            'vet_id'         => session('user')['id'],
            'appointment_id' => $appointment['id'] ?? null,
        ];
        foreach (array_keys($rules) as $field) {
            // Empty boxes are saved as NULL, not as ''
            $data[$field] = trim((string) $this->request->getPost($field)) ?: null;
        }

        (new MedicalRecordModel())->insert($data);

        // Writing the record also closes the visit
        if ($appointment && $appointment['status'] === 'confirmed') {
            (new AppointmentModel())->update($appointment['id'], ['status' => 'completed']);
        }

        // Keep the pet's weight up to date
        if ($data['weight_kg'] !== null) {
            (new PetModel())->update($id, ['weight_kg' => $data['weight_kg']]);
        }

        $this->notifyOwner($pet, 'New visit record', 'A ' . str_replace('_', ' ', $data['record_type']) . ' record was added for ' . $pet['name'] . ': ' . $data['title'] . '.', 'timeline?pet=' . $id);

        return redirect()->to('/vet/patients/' . $id)->with('success', 'Medical record saved. The owner can see it in the Timeline.');
    }

    // "Record vaccine" form (GET /vet/patients/<id>/vaccines/new)
    public function newVaccine(int $id)
    {
        $pet = (new PetModel())->findWithPeople($id);
        if (! $pet) {
            return redirect()->to('/vet/patients')->with('error', 'Patient not found.');
        }

        return view('vet/vaccine_form', ['title' => 'Record Vaccine', 'pet' => $pet]);
    }

    // Saves the vaccination (POST /vet/patients/<id>/vaccines)
    public function storeVaccine(int $id)
    {
        $pet = (new PetModel())->find($id);
        if (! $pet) {
            return redirect()->to('/vet/patients')->with('error', 'Patient not found.');
        }

        $rules = [
            'vaccine_name'  => ['label' => 'Vaccine', 'rules' => 'required|max_length[100]'],
            'dose_number'   => ['label' => 'Dose number', 'rules' => 'permit_empty|is_natural_no_zero|less_than[20]'],
            'batch_number'  => ['label' => 'Batch number', 'rules' => 'permit_empty|max_length[50]'],
            'date_given'    => ['label' => 'Date given', 'rules' => 'required|valid_date[Y-m-d]'],
            'next_due_date' => ['label' => 'Next due date', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'notes'         => ['label' => 'Notes', 'rules' => 'permit_empty|max_length[255]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dateGiven = $this->request->getPost('date_given');
        $nextDue   = $this->request->getPost('next_due_date');
        if ($dateGiven > date('Y-m-d')) {
            return redirect()->back()->withInput()->with('error', 'The date given cannot be in the future.');
        }
        if ($nextDue && $nextDue <= $dateGiven) {
            return redirect()->back()->withInput()->with('error', 'The next due date must be after the date given.');
        }

        $data = ['pet_id' => $id, 'vet_id' => session('user')['id']];
        foreach (array_keys($rules) as $field) {
            $data[$field] = trim((string) $this->request->getPost($field)) ?: null;
        }

        (new VaccinationModel())->insert($data);

        $due = $nextDue ? ' Next dose: ' . date('M j, Y', strtotime($nextDue)) . '.' : '';
        $this->notifyOwner($pet, 'Vaccine recorded', $pet['name'] . ' received ' . $data['vaccine_name'] . '.' . $due, 'timeline?pet=' . $id);

        return redirect()->to('/vet/patients/' . $id)->with('success', 'Vaccination saved. 💉');
    }

    // "Prescribe medication" form (GET /vet/patients/<id>/prescriptions/new)
    public function newPrescription(int $id)
    {
        $pet = (new PetModel())->findWithPeople($id);
        if (! $pet) {
            return redirect()->to('/vet/patients')->with('error', 'Patient not found.');
        }

        return view('vet/prescription_form', ['title' => 'Prescribe Medication', 'pet' => $pet]);
    }

    // Saves the prescription and its dose times (POST /vet/patients/<id>/prescriptions)
    public function storePrescription(int $id)
    {
        $pet = (new PetModel())->find($id);
        if (! $pet) {
            return redirect()->to('/vet/patients')->with('error', 'Patient not found.');
        }

        $rules = [
            'name'         => ['label' => 'Medicine name', 'rules' => 'required|max_length[120]'],
            'dosage'       => ['label' => 'Dosage', 'rules' => 'required|max_length[60]'],
            'form'         => ['label' => 'Form', 'rules' => 'required|in_list[' . implode(',', MedicationModel::FORMS) . ']'],
            'purpose'      => ['label' => 'Purpose', 'rules' => 'permit_empty|max_length[255]'],
            'instructions' => ['label' => 'Instructions', 'rules' => 'permit_empty|max_length[255]'],
            'start_date'   => ['label' => 'Start date', 'rules' => 'required|valid_date[Y-m-d]'],
            'end_date'     => ['label' => 'End date', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'times'        => ['label' => 'Dose times', 'rules' => 'required'],
            'times.*'      => ['label' => 'Dose time', 'rules' => 'permit_empty|regex_match[/^([01]\d|2[0-3]):[0-5]\d$/]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $startDate = $this->request->getPost('start_date');
        $endDate   = $this->request->getPost('end_date') ?: null;
        if ($endDate !== null && $endDate < $startDate) {
            return redirect()->back()->withInput()->with('error', 'The end date cannot be before the start date.');
        }

        // Same as the owner's form (Step 8): drop empty boxes and duplicates, then sort
        $times = array_unique(array_filter((array) $this->request->getPost('times')));
        sort($times);
        if ($times === []) {
            return redirect()->back()->withInput()->with('error', 'Please enter at least one dose time.');
        }

        $name         = trim($this->request->getPost('name'));
        $medicationId = (new MedicationModel())->insert([
            'pet_id'        => $id,
            'prescribed_by' => session('user')['id'],
            'name'          => $name,
            'dosage'        => trim($this->request->getPost('dosage')),
            'form'          => $this->request->getPost('form'),
            'purpose'       => trim((string) $this->request->getPost('purpose')) ?: null,
            'instructions'  => trim((string) $this->request->getPost('instructions')) ?: null,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'status'        => 'active',
        ]);

        $schedules = new MedicationScheduleModel();
        foreach ($times as $time) {
            $schedules->insert(['medication_id' => $medicationId, 'dose_time' => $time . ':00']);
        }

        $this->notifyOwner($pet, 'New prescription', 'Your vet prescribed ' . $name . ' for ' . $pet['name'] . '. Dose reminders start on ' . date('M j', strtotime($startDate)) . '.', 'meds?pet=' . $id);

        return redirect()->to('/vet/patients/' . $id)->with('success', 'Prescription saved. 💊 The owner will get dose reminders.');
    }

    // Journal summaries from all owners (GET /vet/journals)
    public function journals()
    {
        $summaries = (new JournalAiSummaryModel())->forVets();

        foreach ($summaries as &$summary) {
            $summary['notable_changes'] = json_decode((string) $summary['notable_changes'], true) ?: [];
        }
        unset($summary);

        return view('vet/journals', ['title' => 'Journals', 'summaries' => $summaries]);
    }

    // The vet has read a journal summary (POST /vet/journals/<id>/review)
    public function reviewSummary(int $id)
    {
        $summaries = new JournalAiSummaryModel();

        if (! $summaries->find($id)) {
            return redirect()->back()->with('error', 'Summary not found.');
        }

        $summaries->update($id, ['reviewed_by' => session('user')['id'], 'reviewed_at' => date('Y-m-d H:i:s')]);

        return redirect()->back()->with('success', 'Summary marked as reviewed.');
    }

    /** A confirmed or completed visit of this pet with the logged-in vet, or null. */
    private function findVisit(int $petId, int $appointmentId): ?array
    {
        if ($appointmentId === 0) {
            return null;
        }

        return (new AppointmentModel())
            ->where('pet_id', $petId)
            ->where('vet_id', session('user')['id'])
            ->whereIn('status', ['confirmed', 'completed'])
            ->find($appointmentId);
    }

    /** Puts a message in the pet owner's notification bell. */
    private function notifyOwner(array $pet, string $title, string $message, string $link): void
    {
        (new NotificationModel())->insert([
            'user_id'  => $pet['owner_id'],
            'pet_id'   => $pet['id'],
            'type'     => 'general',
            'title'    => $title,
            'message'  => $message,
            'link_url' => $link,
        ]);
    }
}
