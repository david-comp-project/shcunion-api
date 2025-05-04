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

                VolunteerInvolvement::create([
                    'volunteer_involvement_id' => Str::uuid(),
                    'project_id' => $project->project_id,
                    'volunteer_id' => $user?->user_id,
                    'email' => $user?->email ?? fake()->safeEmail(),
                    'full_name' => $user?->full_name ?? fake()->name(),
                    'address' => $user?->address ?? fake()->address(),
                    'phone_number' => $user?->phone_number ?? fake()->phoneNumber(),
                    'criteria_checked' => json_encode($criteria), // <- perbaikan di sini
                    'volunteer_hours' => fake()->randomFloat(2, 0, 100),
                    'involvement_start_date' => now()->subDays(rand(0, 30)),
                    'involvement_end_date' => now()->subDays(rand(0, 30)),
                    'involvement_start_time' => fake()->time('H:i'),
                    'involvement_end_time' => fake()->time('H:i'),
                    'role' => $role,
                    'note' => fake()->sentence(),
                    'status' => fake()->randomElement(['approved', 'pending', 'declined']),
                ]);
            }
        }
    }
    
}
