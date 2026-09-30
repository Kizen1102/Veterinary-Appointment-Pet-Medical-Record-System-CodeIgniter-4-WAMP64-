<?php

namespace App\Controllers;

use App\Models\PetModel;
use App\Models\VaccinationModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Vaccinations extends BaseController
{
    public function create(int $petId)
    {
        return view('vaccinations/form', [
            'title' => 'Record Vaccination',
            'pet'   => $this->findPetOrFail($petId),
        ]);
    }

    public function store(int $petId)
    {
        $this->findPetOrFail($petId);
        $model = new VaccinationModel();

        $data = [
            'pet_id'        => $petId,
            'vet_id'        => $this->hasRole('vet') ? $this->userId() : null,
            'vaccine_name'  => trim((string) $this->request->getPost('vaccine_name')),
            'date_given'    => (string) $this->request->getPost('date_given'),
            'next_due_date' => $this->request->getPost('next_due_date') ?: null,
            'batch_number'  => trim((string) $this->request->getPost('batch_number')) ?: null,
            'notes'         => trim((string) $this->request->getPost('notes')) ?: null,
        ];

        if (! $model->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('/pets/' . $petId)->with('success', 'Vaccination recorded.');
    }

    public function delete(int $id)
    {
        $model       = new VaccinationModel();
        $vaccination = $model->find($id);

        if (! $vaccination) {
            throw PageNotFoundException::forPageNotFound();
        }

        $model->delete($id);

        return redirect()->to('/pets/' . $vaccination['pet_id'])->with('success', 'Vaccination entry removed.');
    }

    private function findPetOrFail(int $id): array
    {
        $pet = (new PetModel())->find($id);

        if (! $pet) {
            throw PageNotFoundException::forPageNotFound('Pet not found.');
        }

        return $pet;
    }
}
