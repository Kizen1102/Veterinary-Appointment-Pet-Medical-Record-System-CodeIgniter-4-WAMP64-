<?php

namespace App\Controllers;

use App\Libraries\VetAssistant;
use App\Models\JournalAiSummaryModel;
use App\Models\JournalEntryModel;
use App\Models\PetModel;
use Config\AI;

/**
 * Pet Symptom & Behavior Journal: one entry per pet per day, plus an AI summary for the vet.
 */
class Journal extends BaseController
{
    /** How far back the history list and the AI summary look. */
    private const DAYS = 14;

    /** Entries can be added or changed for today and the 7 days before. */
    private const EDITABLE_DAYS = 7;

    // GET /journal?pet=ID&date=YYYY-MM-DD
    public function index()
    {
        $pets = (new PetModel())->withPeople(session('user')['id']);

        if ($pets === []) {
            return redirect()->to('/pets/new')->with('error', 'Add your pet first to start its journal.');
        }

        // Which pet? (?pet=ID, but only one of THIS owner's pets)
        $pet = $pets[0];
        foreach ($pets as $candidate) {
            if ((int) $candidate['id'] === (int) $this->request->getGet('pet')) {
                $pet = $candidate;
            }
        }
        $petId = (int) $pet['id'];

        // Which day is in the form? (?date=, today by default, only the editable days)
        $date = (string) $this->request->getGet('date');
        if (! $this->isEditableDate($date)) {
            $date = date('Y-m-d');
        }

        $entries = new JournalEntryModel();
        $summary = (new JournalAiSummaryModel())->latestForPet($petId);

        if ($summary !== null) {
            $summary['notable_changes'] = json_decode((string) $summary['notable_changes'], true) ?: [];
        }

        return view('journal/index', [
            'title'   => 'Health Journal',
            'pets'    => $pets,
            'pet'     => $pet,
            'date'    => $date,
            'entry'   => $entries->where('pet_id', $petId)->where('entry_date', $date)->first(),
            'history' => array_reverse($entries->recent($petId, self::DAYS)), // newest first
            'summary' => $summary,
        ]);
    }

    // Saves the entry of one day: creates it, or updates it if that day was already logged (POST /journal)
    public function save()
    {
        $rules = [
            'pet_id'         => ['label' => 'Pet', 'rules' => 'required|is_natural_no_zero'],
            'entry_date'     => ['label' => 'Date', 'rules' => 'required|valid_date[Y-m-d]'],
            'appetite_score' => ['label' => 'Appetite', 'rules' => 'required|in_list[1,2,3,4,5]'],
            'activity_score' => ['label' => 'Activity', 'rules' => 'required|in_list[1,2,3,4,5]'],
            'mood'           => ['label' => 'Mood', 'rules' => 'required|in_list[' . implode(',', JournalEntryModel::MOODS) . ']'],
            'sleep_hours'    => ['label' => 'Sleep hours', 'rules' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[24]'],
            'sleep_quality'  => ['label' => 'Sleep quality', 'rules' => 'permit_empty|in_list[1,2,3,4,5]'],
            'water_intake'   => ['label' => 'Water intake', 'rules' => 'required|in_list[' . implode(',', JournalEntryModel::WATER_INTAKE) . ']'],
            'bowel_movement' => ['label' => 'Stool', 'rules' => 'required|in_list[' . implode(',', JournalEntryModel::BOWEL_MOVEMENT) . ']'],
            'weight_kg'      => ['label' => 'Weight', 'rules' => 'permit_empty|decimal|greater_than[0]|less_than[200]'],
            'symptoms'       => ['label' => 'Symptoms', 'rules' => 'permit_empty|max_length[1000]'],
            'behavior_notes' => ['label' => 'Behavior notes', 'rules' => 'permit_empty|max_length[1000]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $pet = (new PetModel())->where('owner_id', session('user')['id'])->find($this->request->getPost('pet_id'));
        if (! $pet) {
            return redirect()->back()->withInput()->with('error', 'Please choose one of your pets.');
        }

        $date = $this->request->getPost('entry_date');
        if (! $this->isEditableDate($date)) {
            return redirect()->back()->withInput()->with('error', 'You can only log today or the past ' . self::EDITABLE_DAYS . ' days.');
        }

        $data = [
            'pet_id'         => $pet['id'],
            'logged_by'      => session('user')['id'],
            'entry_date'     => $date,
            'appetite_score' => $this->request->getPost('appetite_score'),
            'activity_score' => $this->request->getPost('activity_score'),
            'mood'           => $this->request->getPost('mood'),
            'sleep_hours'    => $this->request->getPost('sleep_hours') ?: null,
            'sleep_quality'  => $this->request->getPost('sleep_quality') ?: null,
            'water_intake'   => $this->request->getPost('water_intake'),
            'bowel_movement' => $this->request->getPost('bowel_movement'),
            'vomited'        => $this->request->getPost('vomited') ? 1 : 0,
            'weight_kg'      => $this->request->getPost('weight_kg') ?: null,
            'symptoms'       => trim((string) $this->request->getPost('symptoms')) ?: null,
            'behavior_notes' => trim((string) $this->request->getPost('behavior_notes')) ?: null,
        ];

        // One entry per pet per day: update the day's entry if it exists, otherwise create it
        $entries  = new JournalEntryModel();
        $existing = $entries->where('pet_id', $pet['id'])->where('entry_date', $date)->first();

        if ($existing) {
            $entries->update($existing['id'], $data);
            $message = 'Journal updated for ' . date('M j', strtotime($date)) . '.';
        } else {
            $entries->insert($data);
            $message = 'Journal saved! 📝 Thank you for logging ' . $pet['name'] . '\'s day.';
        }

        return redirect()->to('/journal?pet=' . $pet['id'])->with('success', $message);
    }

    // Deletes one entry (POST /journal/<id>/delete)
    public function delete(int $id)
    {
        $entries = new JournalEntryModel();
        $entry   = $entries
            ->select('journal_entries.*')
            ->join('pets', 'pets.id = journal_entries.pet_id')
            ->where('pets.owner_id', session('user')['id'])
            ->find($id);

        if (! $entry) {
            return redirect()->to('/journal')->with('error', 'Entry not found.');
        }

        $entries->delete($id);

        return redirect()->to('/journal?pet=' . $entry['pet_id'])
            ->with('success', 'Entry for ' . date('M j', strtotime($entry['entry_date'])) . ' deleted.');
    }

    // Creates a new AI summary of the last 14 days for the vet (POST /journal/summary)
    public function summarize()
    {
        $pet = (new PetModel())->where('owner_id', session('user')['id'])->find($this->request->getPost('pet_id'));
        if (! $pet) {
            return redirect()->to('/journal')->with('error', 'Pet not found.');
        }

        $entries = (new JournalEntryModel())->recent((int) $pet['id'], self::DAYS);
        if (count($entries) < 2) {
            return redirect()->to('/journal?pet=' . $pet['id'])
                ->with('error', 'Log at least 2 days first, so there is something to compare.');
        }

        $result = (new VetAssistant())->summarizeJournal($entries, $pet);

        (new JournalAiSummaryModel())->insert([
            'pet_id'          => $pet['id'],
            'requested_by'    => session('user')['id'],
            'period_start'    => $entries[0]['entry_date'],
            'period_end'      => $entries[count($entries) - 1]['entry_date'],
            'entries_count'   => count($entries),
            'summary'         => $result['summary'],
            'notable_changes' => json_encode($result['notable_changes']),
            'concern_level'   => $result['concern_level'],
            'ai_model'        => $result['source'] === 'ai' ? config(AI::class)->model : 'rules',
        ]);

        return redirect()->to('/journal?pet=' . $pet['id'] . '#summary')
            ->with('success', 'Summary ready. Your vet will see it at the next visit.');
    }

    /** True for today and the past EDITABLE_DAYS days (in Y-m-d format). */
    private function isEditableDate(string $date): bool
    {
        $isRealDate = date('Y-m-d', strtotime($date)) === $date;

        return $isRealDate
            && $date <= date('Y-m-d')
            && $date >= date('Y-m-d', strtotime('-' . self::EDITABLE_DAYS . ' days'));
    }
}
