<?php

namespace App\Controllers;

use App\Models\PetModel;

/**
 * Pet Owners add their pets here.
 */
class Pets extends BaseController
{
    // "Add Pet" form (GET /pets/new)
    public function create()
    {
        return view('pets/form', ['title' => 'Add Pet']);
    }

    // Saves the new pet (POST /pets)
    public function store()
    {
        $rules = [
            'name'       => ['label' => 'Pet name', 'rules' => 'required|max_length[80]'],
            'species'    => ['label' => 'Species', 'rules' => 'required|in_list[' . implode(',', array_keys(PetModel::SPECIES)) . ']'],
            'breed'      => ['label' => 'Breed', 'rules' => 'permit_empty|max_length[80]'],
            'sex'        => ['label' => 'Sex', 'rules' => 'required|in_list[male,female,unknown]'],
            'birth_date' => ['label' => 'Birth date', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'weight_kg'  => ['label' => 'Weight', 'rules' => 'permit_empty|decimal|greater_than[0]|less_than[200]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // The birth date cannot be in the future
        $birthDate = $this->request->getPost('birth_date') ?: null;
        if ($birthDate && $birthDate > date('Y-m-d')) {
            return redirect()->back()->withInput()->with('error', 'The birth date cannot be in the future.');
        }

        $pets  = new PetModel();
        $petId = $pets->insert([
            'owner_id'       => session('user')['id'], // always the logged-in owner
            'name'           => trim($this->request->getPost('name')),
            'species'        => $this->request->getPost('species'),
            'breed'          => trim((string) $this->request->getPost('breed')) ?: null,
            'sex'            => $this->request->getPost('sex'),
            'is_neutered'    => $this->request->getPost('is_neutered') ? 1 : 0,
            'birth_date'     => $birthDate,
            'weight_kg'      => $this->request->getPost('weight_kg') ?: null,
            'color_markings' => trim((string) $this->request->getPost('color_markings')) ?: null,
            'allergies'      => trim((string) $this->request->getPost('allergies')) ?: null,
        ]);

        return redirect()->to('/owner?pet=' . $petId)->with('success', 'Pet added! 🐾');
    }
}
