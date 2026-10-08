<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\PetModel;

/**
 * Pet Owners add, edit and archive their pets.
 */
class Pets extends BaseController
{
    /** Photos: optional, real images only (JPG, PNG, WEBP), max 2 MB. */
    private const PHOTO_RULES = 'is_image[photo]|mime_in[photo,image/jpg,image/jpeg,image/png,image/webp]'
        . '|ext_in[photo,jpg,jpeg,png,webp]|max_size[photo,2048]';

    /** Folder inside public/ where pet photos are saved. */
    private const PHOTO_FOLDER = 'uploads/pets';

    // "Add Pet" form (GET /pets/new)
    public function create()
    {
        return view('pets/form', ['title' => 'Add Pet', 'pet' => null]);
    }

    // Saves the new pet (POST /pets)
    public function store()
    {
        $rules          = $this->petRules();
        $rules['photo'] = ['label' => 'Pet photo', 'rules' => self::PHOTO_RULES];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->birthDateIsInFuture()) {
            return redirect()->back()->withInput()->with('error', 'The birth date cannot be in the future.');
        }

        $data               = $this->petData();
        $data['owner_id']   = session('user')['id']; // always the logged-in owner
        $data['photo_path'] = $this->savePhoto();    // null when no photo was attached

        $petId = (new PetModel())->insert($data);

        return redirect()->to('/owner?pet=' . $petId)->with('success', 'Pet added! 🐾');
    }

    // "Edit Pet" form (GET /pets/<id>/edit)
    public function edit(int $id)
    {
        $pet = $this->findOwnPet($id);

        if (! $pet) {
            return redirect()->to('/owner')->with('error', 'Pet not found.');
        }

        return view('pets/form', ['title' => 'Edit ' . $pet['name'], 'pet' => $pet]);
    }

    // Saves the changes (POST /pets/<id>)
    public function update(int $id)
    {
        $pet = $this->findOwnPet($id);

        if (! $pet) {
            return redirect()->to('/owner')->with('error', 'Pet not found.');
        }

        if (! $this->validate($this->petRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->birthDateIsInFuture()) {
            return redirect()->back()->withInput()->with('error', 'The birth date cannot be in the future.');
        }

        (new PetModel())->update($id, $this->petData());

        return redirect()->to('/owner?pet=' . $id)->with('success', 'Changes saved! ✏️');
    }

    // Archives a pet (POST /pets/<id>/archive). Its records are kept, it just leaves the dashboard.
    public function archive(int $id)
    {
        $pet = $this->findOwnPet($id);

        if (! $pet) {
            return redirect()->to('/owner')->with('error', 'Pet not found.');
        }

        // Cancel the pet's upcoming visits, so the clinic does not wait for them
        (new AppointmentModel())
            ->where('pet_id', $id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('scheduled_at >', date('Y-m-d H:i:s'))
            ->set(['status' => 'cancelled', 'cancellation_reason' => 'Pet archived by the owner'])
            ->update();

        // Soft delete: fills in deleted_at instead of removing the row
        (new PetModel())->delete($id);

        return redirect()->to('/owner')->with('success', $pet['name'] . ' was archived.');
    }

    // Replaces the photo of one of the owner's pets (POST /pets/<id>/photo)
    public function updatePhoto(int $id)
    {
        $pet = $this->findOwnPet($id);

        if (! $pet) {
            return redirect()->to('/owner')->with('error', 'Pet not found.');
        }

        if (! $this->validate(['photo' => ['label' => 'Pet photo', 'rules' => 'uploaded[photo]|' . self::PHOTO_RULES]])) {
            return redirect()->to('/owner?pet=' . $id)->with('errors', $this->validator->getErrors());
        }

        $newPath = $this->savePhoto();
        (new PetModel())->update($id, ['photo_path' => $newPath]);
        $this->deletePhoto($pet['photo_path']); // remove the old picture from the disk

        return redirect()->to('/owner?pet=' . $id)->with('success', 'Photo updated! 📷');
    }

    /** One of the logged-in owner's pets, or null (also null for other owners' pets). */
    private function findOwnPet(int $id): ?array
    {
        return (new PetModel())->where('owner_id', session('user')['id'])->find($id);
    }

    /** Form rules shared by "Add Pet" and "Edit Pet". */
    private function petRules(): array
    {
        return [
            'name'       => ['label' => 'Pet name', 'rules' => 'required|max_length[80]'],
            'species'    => ['label' => 'Species', 'rules' => 'required|in_list[' . implode(',', array_keys(PetModel::SPECIES)) . ']'],
            'breed'      => ['label' => 'Breed', 'rules' => 'permit_empty|max_length[80]'],
            'sex'        => ['label' => 'Sex', 'rules' => 'required|in_list[male,female,unknown]'],
            'birth_date' => ['label' => 'Birth date', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'weight_kg'  => ['label' => 'Weight', 'rules' => 'permit_empty|decimal|greater_than[0]|less_than[200]'],
        ];
    }

    private function birthDateIsInFuture(): bool
    {
        $birthDate = $this->request->getPost('birth_date');

        return $birthDate && $birthDate > date('Y-m-d');
    }

    /** The pet fields from the form, cleaned up (empty text becomes null). */
    private function petData(): array
    {
        return [
            'name'           => trim($this->request->getPost('name')),
            'species'        => $this->request->getPost('species'),
            'breed'          => trim((string) $this->request->getPost('breed')) ?: null,
            'sex'            => $this->request->getPost('sex'),
            'is_neutered'    => $this->request->getPost('is_neutered') ? 1 : 0,
            'birth_date'     => $this->request->getPost('birth_date') ?: null,
            'weight_kg'      => $this->request->getPost('weight_kg') ?: null,
            'color_markings' => trim((string) $this->request->getPost('color_markings')) ?: null,
            'allergies'      => trim((string) $this->request->getPost('allergies')) ?: null,
        ];
    }

    /**
     * Moves the uploaded "photo" into public/uploads/pets with a random name.
     * Returns the path to save in the database, or null when no photo was sent.
     */
    private function savePhoto(): ?string
    {
        $file = $this->request->getFile('photo');

        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }

        // Random name, so nobody can guess or overwrite another pet's photo
        $newName = $file->getRandomName();
        $file->move(FCPATH . self::PHOTO_FOLDER, $newName);

        return self::PHOTO_FOLDER . '/' . $newName;
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && is_file(FCPATH . $path)) {
            unlink(FCPATH . $path);
        }
    }
}
