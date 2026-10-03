<?php

namespace App\Controllers;

use App\Libraries\MedicationTracker;
use App\Models\MedicationAdherenceModel;
use App\Models\MedicationLogModel;
use App\Models\MedicationModel;
use App\Models\MedicationScheduleModel;
use App\Models\PetModel;

/**
 * Medication Adherence Tracker: the owner's medications, today's doses, and adherence.
 */
class Meds extends BaseController
{
    // GET /meds?pet=ID
    public function index()
    {
        $ownerId = session('user')['id'];
        $pets    = (new PetModel())->withPeople($ownerId);

        if ($pets === []) {
            return redirect()->to('/pets/new')->with('error', 'Add your pet first to track its medications.');
        }

        (new MedicationTracker())->refresh($ownerId);

        // Which pet? (?pet=ID, but only one of THIS owner's pets)
        $pet = $pets[0];
        foreach ($pets as $candidate) {
            if ((int) $candidate['id'] === (int) $this->request->getGet('pet')) {
                $pet = $candidate;
            }
        }
        $petId = (int) $pet['id'];

        // Adherence numbers per medication, from the medication_adherence view
        $adherence = array_column((new MedicationAdherenceModel())->forPet($petId), null, 'medication_id');

        // Today's doses, grouped by medication
        $dosesToday = [];
        foreach ((new MedicationLogModel())->todayForPet($petId) as $dose) {
            $dosesToday[$dose['medication_id']][] = $dose;
        }

        $schedules   = new MedicationScheduleModel();
        $medications = (new MedicationModel())->where('pet_id', $petId)->orderBy('status')->orderBy('name')->findAll();

        foreach ($medications as &$medication) {
            $medication['times']      = array_column($schedules->forMedication((int) $medication['id']), 'dose_time');
            $medication['doses']      = $dosesToday[$medication['id']] ?? [];
            $medication['adherence']  = $adherence[$medication['id']]['adherence_pct'] ?? null;
            $medication['completion'] = $adherence[$medication['id']]['completion_pct'] ?? null;
            $medication['taken']      = (int) ($adherence[$medication['id']]['doses_taken'] ?? 0);
            $medication['missed']     = (int) ($adherence[$medication['id']]['doses_missed'] ?? 0);
        }
        unset($medication);

        return view('meds/index', [
            'title'    => 'Medications',
            'pets'     => $pets,
            'pet'      => $pet,
            'active'   => array_filter($medications, static fn ($m) => $m['status'] === 'active'),
            'finished' => array_filter($medications, static fn ($m) => $m['status'] !== 'active'),
        ]);
    }

    // "Add Medication" form (GET /meds/new?pet=ID)
    public function create()
    {
        $pets = (new PetModel())->forOwner(session('user')['id']);

        if ($pets === []) {
            return redirect()->to('/pets/new')->with('error', 'Add your pet first to track its medications.');
        }

        return view('meds/form', [
            'title'       => 'Add Medication',
            'pets'        => $pets,
            'selectedPet' => (int) $this->request->getGet('pet'),
        ]);
    }

    // Saves the medication and its dose times (POST /meds)
    public function store()
    {
        $rules = [
            'pet_id'       => ['label' => 'Pet', 'rules' => 'required|is_natural_no_zero'],
            'name'         => ['label' => 'Medicine name', 'rules' => 'required|max_length[120]'],
            'dosage'       => ['label' => 'Dosage', 'rules' => 'required|max_length[60]'],
            'form'         => ['label' => 'Form', 'rules' => 'required|in_list[' . implode(',', MedicationModel::FORMS) . ']'],
            'instructions' => ['label' => 'Instructions', 'rules' => 'permit_empty|max_length[255]'],
            'start_date'   => ['label' => 'Start date', 'rules' => 'required|valid_date[Y-m-d]'],
            'end_date'     => ['label' => 'End date', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'times'        => ['label' => 'Dose times', 'rules' => 'required'],
            'times.*'      => ['label' => 'Dose time', 'rules' => 'permit_empty|regex_match[/^([01]\d|2[0-3]):[0-5]\d$/]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // The pet must belong to the logged-in owner
        $pet = (new PetModel())->where('owner_id', session('user')['id'])->find($this->request->getPost('pet_id'));
        if (! $pet) {
            return redirect()->back()->withInput()->with('error', 'Please choose one of your pets.');
        }

        $startDate = $this->request->getPost('start_date');
        $endDate   = $this->request->getPost('end_date') ?: null;
        if ($endDate !== null && $endDate < $startDate) {
            return redirect()->back()->withInput()->with('error', 'The end date cannot be before the start date.');
        }

        // Dose times: drop the empty boxes and duplicates, then sort (08:00, 20:00)
        $times = array_unique(array_filter((array) $this->request->getPost('times')));
        sort($times);
        if ($times === []) {
            return redirect()->back()->withInput()->with('error', 'Please enter at least one dose time.');
        }

        $medicationId = (new MedicationModel())->insert([
            'pet_id'       => $pet['id'],
            'name'         => trim($this->request->getPost('name')),
            'dosage'       => trim($this->request->getPost('dosage')),
            'form'         => $this->request->getPost('form'),
            'instructions' => trim((string) $this->request->getPost('instructions')) ?: null,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'status'       => 'active',
        ]);

        $schedules = new MedicationScheduleModel();
        foreach ($times as $time) {
            $schedules->insert(['medication_id' => $medicationId, 'dose_time' => $time . ':00']);
        }

        return redirect()->to('/meds?pet=' . $pet['id'])->with('success', 'Medication added! 💊 Doses will appear at their times.');
    }

    // Owner gave the dose (POST /meds/doses/<id>/take)
    public function take(int $logId)
    {
        $dose = $this->findOwnDose($logId);

        // Late doses (already "missed") can still be marked as taken
        if (! $dose || ! in_array($dose['status'], ['pending', 'missed'], true)) {
            return redirect()->back()->with('error', 'This dose cannot be changed.');
        }

        (new MedicationLogModel())->markTaken($logId, session('user')['id']);

        return redirect()->back()->with('success', $dose['name'] . ' marked as given ✓');
    }

    // Owner skipped the dose on purpose, e.g. the vet said so (POST /meds/doses/<id>/skip)
    public function skip(int $logId)
    {
        $dose = $this->findOwnDose($logId);

        if (! $dose || $dose['status'] !== 'pending') {
            return redirect()->back()->with('error', 'This dose cannot be changed.');
        }

        (new MedicationLogModel())->update($logId, ['status' => 'skipped', 'logged_by' => session('user')['id']]);

        return redirect()->back()->with('success', $dose['name'] . ' dose skipped.');
    }

    // Stops a medication early (POST /meds/<id>/stop)
    public function stop(int $id)
    {
        $medications = new MedicationModel();
        $medication  = $medications
            ->select('medications.*')
            ->join('pets', 'pets.id = medications.pet_id')
            ->where('pets.owner_id', session('user')['id'])
            ->find($id);

        if (! $medication || $medication['status'] !== 'active') {
            return redirect()->back()->with('error', 'This medication cannot be stopped.');
        }

        $medications->update($id, ['status' => 'discontinued', 'discontinued_reason' => 'Stopped by the owner']);

        // Today's remaining doses are no longer needed
        (new MedicationLogModel())
            ->where('medication_id', $id)
            ->where('status', 'pending')
            ->delete();

        return redirect()->to('/meds?pet=' . $medication['pet_id'])->with('success', $medication['name'] . ' stopped.');
    }

    /** A dose of one of the logged-in owner's pets (with the medication name), or null. */
    private function findOwnDose(int $logId): ?array
    {
        return (new MedicationLogModel())
            ->select('medication_logs.*, medications.name')
            ->join('medications', 'medications.id = medication_logs.medication_id')
            ->join('pets', 'pets.id = medications.pet_id')
            ->where('pets.owner_id', session('user')['id'])
            ->find($logId);
    }
}
