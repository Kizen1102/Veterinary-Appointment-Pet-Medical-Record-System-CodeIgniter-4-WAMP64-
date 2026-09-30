<?php

namespace App\Controllers;

use App\Libraries\VetAssistant;
use App\Models\AppointmentModel;
use App\Models\MedicalRecordModel;
use App\Models\PetModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class MedicalRecords extends BaseController
{
    private MedicalRecordModel $records;

    public function __construct()
    {
        $this->records = new MedicalRecordModel();
    }

    public function show(int $id)
    {
        $record = $this->findOrFail($id);
        $pet    = (new PetModel())->find($record['pet_id']);

        return view('records/show', [
            'title'  => 'Medical Record — ' . $pet['name'],
            'record' => $record,
            'pet'    => $pet,
            'owner'  => (new UserModel())->find($pet['owner_id']),
        ]);
    }

    /**
     * New record for a pet, optionally started from an appointment (GET ?appointment_id=).
     */
    public function create()
    {
        $appointment = null;
        $petId       = (int) $this->request->getGet('pet_id');

        if ($appointmentId = (int) $this->request->getGet('appointment_id')) {
            $appointment = (new AppointmentModel())->find($appointmentId);
            $petId       = (int) ($appointment['pet_id'] ?? 0);
        }

        $pet = (new PetModel())->find($petId);
        if (! $pet) {
            throw PageNotFoundException::forPageNotFound('Pet not found.');
        }

        return view('records/form', [
            'title'  => 'New Medical Record — ' . $pet['name'],
            'pet'    => $pet,
            'record' => [
                'visit_date'     => date('Y-m-d'),
                'appointment_id' => $appointment['id'] ?? null,
                'symptoms'       => $appointment['reason'] ?? null,
                'weight_kg'      => $pet['weight_kg'],
            ],
        ]);
    }

    public function store()
    {
        $data           = $this->recordInput();
        $data['pet_id'] = (int) $this->request->getPost('pet_id');
        $data['vet_id'] = $this->userId();

        if (! (new PetModel())->find($data['pet_id'])) {
            throw PageNotFoundException::forPageNotFound('Pet not found.');
        }

        if (! $this->records->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $this->records->errors());
        }

        $this->syncPetAndAppointment($data);

        return redirect()->to('/records/' . $this->records->getInsertID())->with('success', 'Medical record saved.');
    }

    public function edit(int $id)
    {
        $record = $this->findOrFail($id);

        return view('records/form', [
            'title'  => 'Edit Medical Record',
            'pet'    => (new PetModel())->find($record['pet_id']),
            'record' => $record,
        ]);
    }

    public function update(int $id)
    {
        $record         = $this->findOrFail($id);
        $data           = $this->recordInput();
        $data['pet_id'] = $record['pet_id'];

        if (! $this->records->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $this->records->errors());
        }

        $this->syncPetAndAppointment($data);

        return redirect()->to('/records/' . $id)->with('success', 'Medical record updated.');
    }

    /**
     * Generates an owner-friendly explanation of the record.
     */
    public function aiSummary(int $id)
    {
        $record  = $this->findOrFail($id);
        $pet     = (new PetModel())->find($record['pet_id']);
        $summary = (new VetAssistant())->summarizeRecord($record, $pet);

        $this->records->skipValidation(true)->update($id, ['ai_summary' => $summary['text']]);

        $note = $summary['source'] === 'ai' ? 'AI summary generated.' : 'Summary generated from a template (AI is not configured).';

        return redirect()->to('/records/' . $id)->with('success', $note);
    }

    public function delete(int $id)
    {
        $record = $this->findOrFail($id);
        $this->records->delete($id);

        return redirect()->to('/pets/' . $record['pet_id'])->with('success', 'Medical record deleted.');
    }

    private function recordInput(): array
    {
        $fields = [
            'appointment_id', 'visit_date', 'weight_kg', 'temperature_c', 'symptoms',
            'diagnosis', 'treatment', 'prescription', 'notes', 'follow_up_date',
        ];
        $data = [];

        foreach ($fields as $field) {
            $value        = trim((string) $this->request->getPost($field));
            $data[$field] = $value === '' ? null : $value;
        }

        return $data;
    }

    /**
     * Keeps the pet's current weight up to date and completes the linked appointment.
     */
    private function syncPetAndAppointment(array $data): void
    {
        if ($data['weight_kg'] !== null) {
            (new PetModel())->skipValidation(true)->update($data['pet_id'], ['weight_kg' => $data['weight_kg']]);
        }

        if ($data['appointment_id'] !== null) {
            (new AppointmentModel())->skipValidation(true)
                ->where('pet_id', $data['pet_id'])
                ->update((int) $data['appointment_id'], ['status' => 'completed']);
        }
    }

    private function findOrFail(int $id): array
    {
        $record = $this->records->select('medical_records.*, users.name AS vet_name, pets.owner_id')
            ->join('users', 'users.id = medical_records.vet_id', 'left')
            ->join('pets', 'pets.id = medical_records.pet_id')
            ->where('medical_records.id', $id)
            ->first();

        if (! $record || ($this->isOwner() && (int) $record['owner_id'] !== $this->userId())) {
            throw PageNotFoundException::forPageNotFound('Record not found.');
        }

        return $record;
    }
}
