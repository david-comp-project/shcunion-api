<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\ProjectLampiran;

class ProjectLampiranSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::pluck('project_id')->toArray();
        $uploaders = User::role('admin')->pluck('user_id')->toArray();

        if (empty($projects) || empty($uploaders)) {
            $this->command->warn('Data project atau admin kosong.');
            return;
        }

        $statuses = ['active', 'pending', 'archived'];
        $sections = ['lampiran', 'laporan akhir'];
        $tags = ['dokumen pendukung'];

        foreach ($projects as $projectId) {
            $lampiranCount = rand(2, 3); // Buat 2-5 lampiran per project

            for ($i = 0; $i < $lampiranCount; $i++) {
                ProjectLampiran::create([
                    'project_lampiran_id' => Str::uuid(),
                    'project_id' => $projectId,
                    'uploader_id' => $uploaders[array_rand($uploaders)],
                    'nama_lampiran' => fake()->words(2, true) . '.' . fake()->fileExtension(),
                    'path_lampiran' => 'project/project_lampiran/Document Test ' . fake()->numberBetween(1, 3) . '.pdf',
                    'tipe_lampiran' => fake()->mimeType(),
                    'size_lampiran' => fake()->numberBetween(100, 2048) . ' KB',
                    'tag' => $tags[array_rand($tags)],
                    'section' => $sections[array_rand($sections)],
                    'status' => $statuses[array_rand($statuses)],
                ]);
            }
        }
    }
}
