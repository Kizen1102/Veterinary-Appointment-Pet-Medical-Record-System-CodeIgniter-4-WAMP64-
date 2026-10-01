<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
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
            $events[] = $this->makeEvent(
                $row['event_type'],
                $row['event_date'],
                $row['title'],
                $row['details'],
                $vetNames[$row['vet_id']] ?? null,
                null,
                $row['event_date'] > $today
            );
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
        ];
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
