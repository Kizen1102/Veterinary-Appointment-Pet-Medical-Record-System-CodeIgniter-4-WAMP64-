<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\NotificationModel;

/**
 * Veterinarians manage the clinic's appointments: confirm, decline, complete, no-show.
 */
class VetAppointments extends BaseController
{
    /** Filter tabs on the appointments page. */
    public const TABS = [
        'today'    => 'Today',
        'pending'  => 'Requests',
        'upcoming' => 'Upcoming',
        'past'     => 'Past',
    ];

    // GET /vet/appointments?tab=today
    public function index()
    {
        $tab = $this->request->getGet('tab');
        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'today';
        }

        return view('vet/appointments', [
            'title'        => 'Appointments',
            'tab'          => $tab,
            'appointments' => (new AppointmentModel())->forVetTab(session('user')['id'], $tab),
        ]);
    }

    // POST /vet/appointments/<id>/status   (action = confirm | decline | complete | no_show)
    public function updateStatus(int $id)
    {
        $vetId        = session('user')['id'];
        $appointments = new AppointmentModel();
        $appointment  = $appointments->visibleToVet($vetId)->where('appointments.id', $id)->first();

        if (! $appointment) {
            return redirect()->back()->with('error', 'Appointment not found.');
        }

        $action   = $this->request->getPost('action');
        $isFuture = $appointment['scheduled_at'] > date('Y-m-d H:i:s');
        $when     = date('M j, g:i A', strtotime($appointment['scheduled_at']));

        switch ($action) {
            // Accept a request. A request without a vet is assigned to the vet who confirms it.
            case 'confirm':
                if ($appointment['status'] !== 'pending' || ! $isFuture) {
                    return redirect()->back()->with('error', 'Only upcoming requests can be confirmed.');
                }
                if ($appointment['vet_id'] === null && $appointments->hasConflict($vetId, $appointment['scheduled_at'], (int) $appointment['duration_minutes'])) {
                    return redirect()->back()->with('error', 'You already have another appointment at ' . $when . '.');
                }
                $appointments->update($id, ['status' => 'confirmed', 'vet_id' => $appointment['vet_id'] ?? $vetId]);
                $this->notifyOwner($appointment, 'Appointment confirmed', 'Your visit for ' . $appointment['pet_name'] . ' on ' . $when . ' is confirmed.');
                $message = 'Appointment confirmed.';
                break;

            // Turn down a request or cancel a confirmed visit, with an optional reason for the owner
            case 'decline':
                if (! in_array($appointment['status'], ['pending', 'confirmed'], true) || ! $isFuture) {
                    return redirect()->back()->with('error', 'This appointment can no longer be declined.');
                }
                $reason = trim((string) $this->request->getPost('reason')) ?: 'Declined by the clinic';
                $appointments->update($id, ['status' => 'cancelled', 'cancellation_reason' => mb_substr($reason, 0, 255)]);
                $this->notifyOwner($appointment, 'Appointment not available', 'Your visit for ' . $appointment['pet_name'] . ' on ' . $when . ' was cancelled: ' . $reason . '. Please book another time.');
                $message = 'Appointment declined. The owner was notified.';
                break;

            // The visit happened (the vet must be the assigned vet)
            case 'complete':
                if ($appointment['status'] !== 'confirmed' || (int) $appointment['vet_id'] !== (int) $vetId) {
                    return redirect()->back()->with('error', 'Only your confirmed appointments can be completed.');
                }
                $appointments->update($id, ['status' => 'completed']);
                $message = 'Visit marked as completed.';
                break;

            // The owner did not come
            case 'no_show':
                if ($appointment['status'] !== 'confirmed' || (int) $appointment['vet_id'] !== (int) $vetId || $isFuture) {
                    return redirect()->back()->with('error', 'Only your past confirmed appointments can be marked as no-show.');
                }
                $appointments->update($id, ['status' => 'no_show']);
                $message = 'Marked as no-show.';
                break;

            default:
                return redirect()->back()->with('error', 'Unknown action.');
        }

        return redirect()->back()->with('success', $message);
    }

    /** Puts a message in the owner's notification bell. */
    private function notifyOwner(array $appointment, string $title, string $message): void
    {
        (new NotificationModel())->insert([
            'user_id'       => $appointment['owner_id'],
            'pet_id'        => $appointment['pet_id'],
            'type'          => 'appointment_update',
            'title'         => $title,
            'message'       => $message,
            'link_url'      => 'appointments',
            'related_table' => 'appointments',
            'related_id'    => $appointment['id'],
        ]);
    }
}
