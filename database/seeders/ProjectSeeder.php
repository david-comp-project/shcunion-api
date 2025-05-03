<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Desa;
use Carbon\Carbon;

class ProjectSeeder extends Seeder
{
    public function run()    {
        $adminUsers = User::role('admin')->get();
        $desaList = Desa::all();

        if ($adminUsers->isEmpty() || $desaList->isEmpty()) {
            $this->command->error('Data admin atau desa tidak ditemukan.');
            return;
        }

        $projects = [
            [
                'title' => 'Program Literasi Anak Desa',
                'description' => 'Kegiatan sukarela untuk meningkatkan minat baca anak-anak di pedesaan.',
                'category' => 'volunteer',
                'status' => 'in_review',
                'image' => 'project/project_image/literasi_anak.jpg',
                'roles' => [
                    ['key' => 'Mentor', 'value' => 5],
                    ['key' => 'Content Creator', 'value' => 2],
                    ['key' => 'Pengarah Lapangan', 'value' => 3],
                ],
                'criteria' => [
                    ['key' => 'Bisa Mengajar', 'value' => 'Wajib', 'role' => 'Mentor'],
                    ['key' => 'Memiliki Device Yang Memamdai', 'value' => 'Opsional', 'role' => 'Content Creator'],
                    ['key' => 'Kemampuan Organisir Kegiatan', 'value' => 'Wajib', 'role' => 'Pengarah Lapangan'],
                    ['key' => 'Memiliki SIM C', 'value' => 'Opsional', 'role' => 'all'],
                ],
            ],
            [
                'title' => 'Pengadaan Buku Sekolah Dasar',
                'description' => 'Pengumpulan dana untuk menyediakan buku pelajaran dan cerita bagi siswa SD.',
                'category' => 'donation',
                'status' => 'proposed',
                'image' => 'project/project_image/buku_anak.jpg',

            ],
            [
                'title' => 'Pelatihan Dasar Komputer untuk Ibu Rumah Tangga',
                'description' => 'Kegiatan pelatihan pengenalan komputer dan internet bagi ibu rumah tangga.',
                'category' => 'volunteer',
                'status' => 'in_active',
                'image' => 'project/project_image/komputer_ibu.jpg',
                'roles' => [
                    ['key' => 'Trainer', 'value' => 3]
                ],
                'criteria' => [
                    ['key' => 'Menguasai Microsoft Office', 'value' => 'Wajib', 'role' => 'Trainer']
                ],
            ],
            [
                'title' => 'Bangun MCK untuk Komunitas',
                'description' => 'Pengumpulan dana pembangunan MCK umum di daerah tertinggal.',
                'category' => 'donation',
                'status' => 'in_review',
                'image' => 'project/project_image/mck.jpg',

            ],
            [
                'title' => 'Kelas Koding untuk Remaja Desa',
                'description' => 'Mengajarkan dasar-dasar pemrograman untuk remaja desa.',
                'category' => 'volunteer',
                'status' => 'proposed',
                'image' => 'project/project_image/koding.jpg',
                'roles' => [
                    ['key' => 'Instruktur', 'value' => 10],
                    ['key' => 'Programmer Senior', 'value' => 2]
                ],
                'criteria' => [
                    ['key' => 'Menguasai HTML dan CSS', 'value' => 'Wajib', 'role' => 'all'],
                    ['key' => 'Memiliki Kemampuan Mengajar', 'value' => 'Wajib', 'role' => 'Instruktur'],
                    ['key' => 'Berasal dari Jurusan IT', 'value' => 'Opsional', 'role' => 'Instruktur'],
                    ['key' => 'Berpengalaman Lebih dari 1 Tahun', 'value' => 'Wajib', 'role' => 'Programmer Senior']



                ],
            ],
        ];

        foreach ($projects as $index => $proj) {
            $isDonation = $proj['category'] === 'donation';

            DB::table('projects')->insert([
                'project_id' => Str::uuid(),
                'project_title' => $proj['title'],
                'project_description' => $proj['description'],
                'project_start_date' => Carbon::now()->addDays($index * 2),
                'project_end_date' => Carbon::now()->addMonths(rand(2, 6))->addDays($index * 5),
                'project_target_amount' => $isDonation ? rand(50_000_000, 200_000_000) : rand(0, 2000),
                'creator_id' => $adminUsers->random()->user_id,
                'project_status' => $proj['status'],
                'project_category' => $proj['category'],
                'project_address' => 'Jl. Proyek Desa No.' . rand(1, 100),
                'project_role' => $proj['category'] === 'volunteer' ? json_encode($proj['roles']) : null,
                'project_criteria' => $proj['category'] === 'volunteer' ? json_encode($proj['criteria']) : null,
                'latitude' => -6.2 + ($index * 0.015),
                'longitude' => 106.8 + ($index * 0.015),
                'kode_desa' => $desaList->random()->kode_desa,
                'project_image_path' => $proj['image'],
                'completed_at' => $proj['status'] === 'completed' ? Carbon::now()->subDays(rand(1, 30)) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // insert creator information
            
        }
    }
}
