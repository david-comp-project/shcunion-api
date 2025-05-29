<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Project;

class ProjectCreatorInformationSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();

        if ($projects->isEmpty()) {
            $this->command->warn('Tidak ada project ditemukan.');
            return;
        }

        // Contoh data dummy manual
        $names = ['Andi Prasetyo', 'Budi Santoso', 'Citra Dewi', 'Dewi Lestari'];
        $emails = ['andi@example.com', 'budi@example.com', 'citra@example.com', 'dewi@example.com'];
        $phones = ['081234567890', '082345678901', '083456789012', '084567890123'];
        $types  = ['perorangan', 'organisasi'];
        $orgs   = ['PT. Sukses Selalu', 'CV. Maju Terus', 'Yayasan Aksi', 'Komunitas Koding'];

        foreach ($projects as $project) {
            // Pilih random data dari array
            $creatorType = $types[array_rand($types)];
            $idx = array_rand($names);

            DB::table('project_creator_informations')->insert([
                'project_creator_information_id'   => Str::uuid(),
                'project_id'                       => $project->project_id,
                'creator_name'                     => $names[$idx],
                'creator_email'                    => $emails[$idx],
                'creator_phone'                    => $phones[$idx],
                'creator_type'                     => $creatorType,
                'creator_organization_name'        => $creatorType === 'organisasi' ? $orgs[array_rand($orgs)] : null,
                'creator_website'                  => $creatorType === 'organisasi'
                                                        ? 'https://www.' . Str::slug($orgs[array_rand($orgs)], '') . '.com'
                                                        : null,
                'creator_social_media'             => json_encode([
                    'instagram' => '@' . Str::lower(Str::random(6)),
                    'twitter'   => '@' . Str::lower(Str::random(6)),
                ]),
                'creator_identifier'               => strtoupper(Str::random(16)),
                'creator_file_path'                => null,
                'creator_file_name'                => null,
                'created_at'                       => now(),
                'updated_at'                       => now(),
            ]);
        }
    }
}
