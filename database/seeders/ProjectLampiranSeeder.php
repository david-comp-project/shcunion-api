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
        $namaLampiran = ['Rincian Pengelolaan Kegiatan 1', 'Rincian Pengelolaan Kegiatan 2', 'Rincian Pengelolaan Kegiatan 3', 'Estimasi Kebutuhan Dana', 'dokumen'];

        foreach ($projects as $projectId) {
            $lampiranCount = rand(2, 3); // Buat 2-5 lampiran per project

            for ($i = 0; $i < $lampiranCount; $i++) {
                $documentNumber = rand(1, 3);
                $mimeTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $size = rand(100, 2048);
                
                ProjectLampiran::create([
                    'project_lampiran_id' => Str::uuid(),
                    'project_id' => $projectId,
                    'uploader_id' => $uploaders[array_rand($uploaders)],
                    'nama_lampiran' => $namaLampiran[array_rand($namaLampiran)],

                    'path_lampiran' => "project/project_lampiran/Document Test {$documentNumber}.pdf",
                    'tipe_lampiran' => $mimeTypes[array_rand($mimeTypes)],
                    'size_lampiran' => "{$size} KB",

                    'tag' => $tags[array_rand($tags)],
                    'section' => $sections[array_rand($sections)],
                    'status' => $statuses[array_rand($statuses)],
                ]);
            }
        }
    }
}
