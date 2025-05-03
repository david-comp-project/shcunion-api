<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Project;

class ProjectBeneficialInformationSeeder extends Seeder
{
    public function run()
    {
        $projects = Project::all();

        if ($projects->isEmpty()) {
            $this->command->warn('Tidak ada project ditemukan.');
            return;
        }

        foreach ($projects as $project) {
            $type = fake()->randomElement(['perorangan', 'lembaga']);

            if ($type === 'perorangan') {
                DB::table('project_beneficial_informations')->insert([
                    'project_beneficial_information_id' => Str::uuid(),
                    'project_id' => $project->project_id,
                    'beneficiary_type' => 'perorangan',
                    'beneficiary_name' => fake()->name(),
                    'beneficiary_nik' => fake()->numerify('##########'),
                    'beneficiary_address' => fake()->address(),
                    'beneficiary_phone' => fake()->phoneNumber(),
                    'beneficiary_needs' => fake()->sentence(6),
                    'organization_name' => null,
                    'organization_reg_number' => null,
                    'organization_address' => null,
                    'organization_pic' => null,
                    'organization_phone' => null,
                    'beneficiary_relation' => fake()->randomElement(['diri-sendiri', 'keluarga', 'teman', 'organisasi', 'lainnya']),
                    'beneficiary_relation_other' => fake()->boolean(30) ? 'Relawan lokal' : null,
                    'beneficiary_file_path' => null,
                    'beneficiary_file_name' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('project_beneficial_informations')->insert([
                    'project_beneficial_information_id' => Str::uuid(),
                    'project_id' => $project->project_id,
                    'beneficiary_type' => 'lembaga',
                    'beneficiary_name' => null,
                    'beneficiary_nik' => null,
                    'beneficiary_address' => null,
                    'beneficiary_phone' => null,
                    'beneficiary_needs' => fake()->sentence(6),
                    'organization_name' => fake()->company(),
                    'organization_reg_number' => fake()->numerify('ORG-#####'),
                    'organization_address' => fake()->address(),
                    'organization_pic' => fake()->name(),
                    'organization_phone' => fake()->phoneNumber(),
                    'beneficiary_relation' => fake()->randomElement(['diri-sendiri', 'keluarga', 'teman', 'organisasi', 'lainnya']),
                    'beneficiary_relation_other' => fake()->boolean(30) ? 'Relawan lokal' : null,
                    'beneficiary_file_path' => null,
                    'beneficiary_file_name' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
