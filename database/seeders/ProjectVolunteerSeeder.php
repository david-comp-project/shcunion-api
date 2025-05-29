<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\VolunteerInvolvement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProjectVolunteerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::where('project_category', 'volunteer')->get();
        $users = User::all();
    
        foreach ($projects as $project) {
            $count = rand(100, 200); // Setiap project dapat 5-20 volunteer
    
            for ($i = 0; $i < $count; $i++) {
                $user = $users->isNotEmpty() ? $users->random() : null;
    
                $roles = json_decode($project->project_role, true);
                $role = is_array($roles) && !empty($roles)
                    ? $roles[array_rand($roles)]['key'] ?? 'Relawan'
                    : 'Relawan';
                
                $criteria = json_decode($project->project_criteria, true);
                //update criteria, by adding fiel fulffiled to the criteria
                if (is_array($criteria) && !empty($criteria)) {
                    foreach ($criteria as $key => $value) {
                        $criteria[$key]['fulfilled'] = true;
                    }
                }

                $emailList = ['relawan1@example.com', 'relawan2@example.com', 'relawan3@example.com'];
                $nameList = ['Budi Santoso', 'Siti Aminah', 'Agus Prasetyo'];
                $addressList = ['Jl. Merdeka No.1', 'Jl. Sudirman No.10', 'Jl. Diponegoro No.5'];
                $phoneList = ['081234567890', '082233445566', '083344556677'];
                $statuses = ['approved', 'pending', 'declined'];
                $notes = ['Bersedia datang lebih awal', 'Perlu transportasi', 'Sudah pengalaman sebelumnya'];

                $email = $user?->email ?? $emailList[array_rand($emailList)];
                $fullName = $user?->full_name ?? $nameList[array_rand($nameList)];
                $address = $user?->address ?? $addressList[array_rand($addressList)];
                $phone = $user?->phone_number ?? $phoneList[array_rand($phoneList)];
                $volunteerHours = rand(1, 100) + (rand(0, 99) / 100); // float 1.00 - 100.99
                $startTime = sprintf('%02d:%02d', rand(6, 10), rand(0, 59)); // Jam 6:00 - 10:59
                $endTime = sprintf('%02d:%02d', rand(11, 18), rand(0, 59));  // Jam 11:00 - 18:59
                $note = $notes[array_rand($notes)];
                $status = $statuses[array_rand($statuses)];


       VolunteerInvolvement::create([
            'volunteer_involvement_id' => Str::uuid(),
            'project_id' => $project->project_id,
            'volunteer_id' => $user?->user_id,
            'email' => $email,
            'full_name' => $fullName,
            'address' => $address,
            'phone_number' => $phone,
            'criteria_checked' => json_encode($criteria),
            'volunteer_hours' => $volunteerHours,
            'involvement_start_date' => now()->subDays(rand(0, 30)),
            'involvement_end_date' => now()->subDays(rand(0, 30)),
            'involvement_start_time' => $startTime,
            'involvement_end_time' => $endTime,
            'role' => $role,
            'note' => $note,
            'status' => $status,
        ]);

            }
        }
    }
    
}
