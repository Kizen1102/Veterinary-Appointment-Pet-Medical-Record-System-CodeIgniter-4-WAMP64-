<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\MedicalRecordModel;
use App\Models\PetModel;
use App\Models\UserModel;
use App\Models\VaccinationModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Pets extends BaseController
{
    private PetModel $pets;

    public function __construct()
    {
        $this->pets = new PetModel();
    }

    public function index()
    {
        return view('pets/index', [
            'title' => $this->isOwner() ? 'My Pets' : 'Pets',
            'pets'  => $this->pets->withOwner($this->isOwner() ? $this->userId() : null),
        ]);
    }

    public function show(int $id)
    {
        $pet = $this->findPetOrFail($id);

        return view('pets/show', [
            'title'        => $pet['name'],
            'pet'          => $pet,
            'owner'        => (new UserModel())->find($pet['owner_id']),
            'records'      => (new MedicalRecordModel())->forPet($id),
            'vaccinations' => (new VaccinationModel())->forPet($id),
            'appointments' => (new AppointmentModel())->detailed()
                ->where('appointments.pet_id', $id)
                ->orderBy('appointment_date', 'DESC')->findAll(),
        ]);
    }

    public function create()
    {
        return view('pets/form', [
            'title'  => 'Add Pet',
            'pet'    => ['owner_id' => $this->request->getGet('owner_id')],
            'owners' => $this->isOwner() ? [] : (new UserModel())->owners(),
        ]);
    }

    public function store()
    {
        $data = $this->petInput();

        if (! $this->pets->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $this->pets->errors());
        }

        return redirect()->to('/pets/' . $this->pets->getInsertID())->with('success', 'Pet added.');
    }

    public function edit(int $id)
    {
        return view('pets/form', [
            'title'  => 'Edit Pet',
            'pet'    => $this->findPetOrFail($id),
            'owners' => $this->isOwner() ? [] : (new UserModel())->owners(),
        ]);
    }

    public function update(int $id)
    {
        $this->findPetOrFail($id);

        if (! $this->pets->update($id, $this->petInput())) {
            return redirect()->back()->withInput()->with('errors', $this->pets->errors());
        }

        return redirect()->to('/pets/' . $id)->with('success', 'Pet updated.');
    }

    public function delete(int $id)
    {
        $pet = $this->findPetOrFail($id);
        $this->pets->delete($id);

        return redirect()->to('/pets')->with('success', $pet['name'] . ' was removed.');
    }

    /**
     * Loads a pet, ensuring owners can only reach their own animals.
     */
    private function findPetOrFail(int $id): array
    {
        $pet = $this->pets->find($id);

        if (! $pet || ($this->isOwner() && (int) $pet['owner_id'] !== $this->userId())) {
            throw PageNotFoundException::forPageNotFound('Pet not found.');
        }

        return $pet;
    }

    private function petInput(): array
    {
        $fields = ['name', 'species', 'breed', 'sex', 'birth_date', 'weight_kg', 'color', 'allergies', 'notes'];
        $data   = [];

        foreach ($fields as $field) {
            $value        = trim((string) $this->request->getPost($field));
            $data[$field] = $value === '' ? null : $value;
        }

        // Owners always register pets under their own account.
        $data['owner_id'] = $this->isOwner() ? $this->userId() : (int) $this->request->getPost('owner_id');

        return $data;
    }
}
