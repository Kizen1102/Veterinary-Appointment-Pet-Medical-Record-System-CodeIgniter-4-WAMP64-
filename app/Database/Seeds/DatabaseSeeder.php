<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Demo data. Run with: php spark db:seed DatabaseSeeder
 * All demo accounts use the password: password123
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now  = date('Y-m-d H:i:s');
        $hash = password_hash('password123', PASSWORD_DEFAULT);

        $users = [
            ['name' => 'Clinic Admin', 'email' => 'admin@vetclinic.test', 'role' => 'admin', 'phone' => '09170000001'],
            ['name' => 'Dr. Maria Santos', 'email' => 'vet@vetclinic.test', 'role' => 'vet', 'phone' => '09170000002'],
            ['name' => 'Dr. Jose Reyes', 'email' => 'vet2@vetclinic.test', 'role' => 'vet', 'phone' => '09170000003'],
            ['name' => 'Front Desk', 'email' => 'staff@vetclinic.test', 'role' => 'staff', 'phone' => '09170000004'],
            ['name' => 'Juan Dela Cruz', 'email' => 'owner@vetclinic.test', 'role' => 'owner', 'phone' => '09170000005', 'address' => 'Quezon City'],
        ];

        foreach ($users as $user) {
            $this->db->table('users')->insert($user + [
                'password_hash' => $hash,
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        $ownerId = $this->db->table('users')->where('email', 'owner@vetclinic.test')->get()->getRow()->id;
        $vetId   = $this->db->table('users')->where('email', 'vet@vetclinic.test')->get()->getRow()->id;

        $this->db->table('pets')->insertBatch([
            ['owner_id' => $ownerId, 'name' => 'Bantay', 'species' => 'Dog', 'breed' => 'Aspin', 'sex' => 'Male', 'birth_date' => '2021-03-14', 'weight_kg' => 12.5, 'color' => 'Brown', 'allergies' => null, 'created_at' => $now, 'updated_at' => $now],
            ['owner_id' => $ownerId, 'name' => 'Muning', 'species' => 'Cat', 'breed' => 'Puspin', 'sex' => 'Female', 'birth_date' => '2022-07-02', 'weight_kg' => 3.8, 'color' => 'Orange', 'allergies' => 'Penicillin', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $petId = $this->db->table('pets')->where('name', 'Bantay')->get()->getRow()->id;

        $this->db->table('appointments')->insert([
            'pet_id'           => $petId,
            'owner_id'         => $ownerId,
            'vet_id'           => $vetId,
            'appointment_date' => date('Y-m-d', strtotime('+1 day')),
            'appointment_time' => '09:00:00',
            'duration_minutes' => 30,
            'reason'           => 'Annual check-up and booster vaccine',
            'status'           => 'confirmed',
            'triage_level'     => 'low',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $this->db->table('medical_records')->insert([
            'pet_id'        => $petId,
            'vet_id'        => $vetId,
            'visit_date'    => date('Y-m-d', strtotime('-30 days')),
            'weight_kg'     => 12.1,
            'temperature_c' => 38.6,
            'symptoms'      => 'Scratching ears, head shaking',
            'diagnosis'     => 'Otitis externa (mild)',
            'treatment'     => 'Ear cleaning, topical otic drops',
            'prescription'  => 'Otic drops 2x daily for 7 days',
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $this->db->table('vaccinations')->insert([
            'pet_id'        => $petId,
            'vet_id'        => $vetId,
            'vaccine_name'  => 'Anti-Rabies',
            'date_given'    => date('Y-m-d', strtotime('-11 months')),
            'next_due_date' => date('Y-m-d', strtotime('+1 month')),
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }
}
