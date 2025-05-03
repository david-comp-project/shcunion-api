<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Project;

class ProjectCreatorInformationSeeder extends Seeder
{
    public function run()
    {
        $projects = Project::all();

        if ($projects->isEmpty()) {
            $this->command->warn('Tidak ada project ditemukan.');
            return;
        }

        foreach ($projects as $project) {
            $creatorType = fake()->randomElement(['perorangan', 'organisasi']);

            DB::table('project_creator_informations')->insert([
                'project_creator_information_id' => Str::uuid(),
                'project_id' => $project->project_id,
                'creator_name' => fake()->name(),
                'creator_email' => fake()->unique()->safeEmail(),
                'creator_phone' => fake()->phoneNumber(),
                'creator_type' => $creatorType,
                'creator_organization_name' => $creatorType === 'perorangan' ? null : fake()->company(),
                'creator_website' => $creatorType === 'perorangan' ? null : fake()->url(),
                'creator_social_media' => json_encode([
                    'instagram' => '@' . fake()->userName(),
                    'twitter' => '@' . fake()->userName()
                ]),
                'creator_identifier' => strtoupper(Str::random(16)),
                'creator_file_path' => null,
                'creator_file_name' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
