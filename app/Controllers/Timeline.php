<?php

namespace App\Controllers;

use App\Libraries\VetAssistant;
use App\Models\AppointmentModel;
use App\Models\MedicalRecordModel;
use App\Models\PetHealthTimelineModel;
use App\Models\PetModel;
use App\Models\UserModel;

/**
 * Digital Pet Health Timeline: every visit, vaccine, medication and appointment of a pet in one list.
 */
class Timeline extends BaseController
{
    // GET /timeline?pet=ID&type=visits
    public function index()
    {
        $user = session('user');
        $pets = (new PetModel())->withPeople($user['id']);

        if ($pets === []) {
            return redirect()->to('/pets/new')->with('error', 'Add your pet first to see its health timeline.');
        }

        // Which pet? (?pet=ID, but only one of THIS owner's pets)
        $pet = $pets[0];
        foreach ($pets as $candidate) {
            if ((int) $candidate['id'] === (int) $this->request->getGet('pet')) {
                $pet = $candidate;
            }
        }

        // Which filter tab? (?type=visits). Anything unknown means "all".
        $group = $this->request->getGet('type');
        if (! array_key_exists($group, PetHealthTimelineModel::GROUPS)) {
            $group = 'all';
        }

        $events = $this->eventsFor((int) $pet['id']);

        if ($group !== 'all') {
            $events = array_filter($events, static fn ($event) => $event['group'] === $group);
        }

        // Group the events by month: ['October 2026' => [event, event], 'September 2026' => [event]]
        $months = [];
        foreach ($events as $event) {
            $months[date('F Y', strtotime($event['date']))][] = $event;
        }

        return view('timeline/index', [
            'title'  => 'Health Timeline',
            'pets'   => $pets,
            'pet'    => $pet,
            'group'  => $group,
            'months' => $months,
        ]);
    }

    /**
     * All events of one pet, newest first. Each event is an array with:
     * date, type, title, details, vet, group, label, icon, isFuture, status.
     */
    private function eventsFor(int $petId): array
    {
        $today  = date('Y-m-d');
        $events = [];

        // 1. Medical records, vaccinations, medications and follow-ups (from the database view)
        $rows     = (new PetHealthTimelineModel())->forPet($petId);
        $vetNames = $this->vetNames(array_column($rows, 'vet_id'));

        foreach ($rows as $row) {
            $event = $this->makeEvent(
                $row['event_type'],
                $row['event_date'],
                $row['title'],
                $row['details'],
                $vetNames[$row['vet_id']] ?? null,
                null,
                $row['event_date'] > $today
            );

            // Step 14: visits written by the vet can be opened and explained
            if ($row['source_table'] === 'medical_records' && $row['event_type'] !== 'follow_up_due') {
                $event['recordId'] = (int) $row['source_id'];
            }

            $events[] = $event;
        }

        // 2. Clinic appointments (cancelled and no-show ones are left out)
        $appointments = (new AppointmentModel())->detailed()
            ->where('appointments.pet_id', $petId)
            ->whereNotIn('appointments.status', ['cancelled', 'no_show'])
            ->findAll();

        foreach ($appointments as $appointment) {
            $events[] = $this->makeEvent(
                'appointment',
                $appointment['scheduled_at'],
                AppointmentModel::TYPE_LABELS[$appointment['appointment_type']] . ' · ' . date('g:i A', strtotime($appointment['scheduled_at'])),
                $appointment['reason'],
                $appointment['vet_name'],
                $appointment['status'],
                $appointment['scheduled_at'] > date('Y-m-d H:i:s')
            );
        }

        // Newest first
        usort($events, static fn ($a, $b) => strcmp($b['date'], $a['date']));

        return $events;
    }

    private function makeEvent(string $type, string $date, string $title, ?string $details, ?string $vet, ?string $status, bool $isFuture): array
    {
        $info = PetHealthTimelineModel::EVENT_TYPES[$type] ?? PetHealthTimelineModel::EVENT_TYPES['other'];

        return [
            'date'     => $date,
            'type'     => $type,
            'title'    => $title,
            'details'  => $details,
            'vet'      => $vet,
            'status'   => $status,
            'isFuture' => $isFuture,
            'group'    => $info['group'],
            'label'    => $info['label'],
            'icon'     => $info['icon'],
            'recordId' => null,
        ];
    }

    // Step 14: one visit record in full (GET /timeline/records/<id>)
    public function record(int $id)
    {
        $record = $this->findOwnRecord($id);
        if (! $record) {
            return redirect()->to('/timeline')->with('error', 'Record not found.');
        }

        return view('timeline/record', ['title' => 'Visit Details', 'record' => $record]);
    }

    // Step 14: "Explain in simple words" — AI explanation of the visit, saved on the record (POST /timeline/records/<id>/explain)
    public function explain(int $id)
    {
        $record = $this->findOwnRecord($id);
        if (! $record) {
            return redirect()->to('/timeline')->with('error', 'Record not found.');
        }

        $pet    = (new PetModel())->find($record['pet_id']);
        $result = (new VetAssistant())->summarizeRecord($record, $pet);

        (new MedicalRecordModel())->update($id, [
            'owner_summary'    => $result['text'],
            'owner_summary_at' => date('Y-m-d H:i:s'),
        ]);

        $message = $result['source'] === 'ai'
            ? 'Here is the visit in simple words. ✨'
            : 'The AI is offline, so the built-in explainer was used.';

        return redirect()->to('/timeline/records/' . $id . '#explain')->with('success', $message);
    }

    /** A medical record of one of the signed-in owner's pets (with pet and vet names), or null. */
    private function findOwnRecord(int $id): ?array
    {
        return (new MedicalRecordModel())
            ->select('medical_records.*, pets.name AS pet_name, pets.species, users.full_name AS vet_name')
            ->join('pets', 'pets.id = medical_records.pet_id')
            ->join('users', 'users.id = medical_records.vet_id', 'left')
            ->where('pets.owner_id', session('user')['id'])
            ->where('pets.deleted_at', null)
            ->find($id);
    }

    /** [vet id => full name] for the given ids (ids can repeat or be null). */
    private function vetNames(array $ids): array
    {
        $ids = array_filter(array_unique($ids));

        if ($ids === []) {
            return [];
        }

        $vets = (new UserModel())->withDeleted()->select('id, full_name')->whereIn('id', $ids)->findAll();

        return array_column($vets, 'full_name', 'id');
    }
}
