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
                'status' => 'in_progress',
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
                'status' => 'in_progress',
                'image' => 'project/project_image/buku_anak.jpg',

            ],
            [
                'title' => 'Pelatihan Dasar Komputer untuk Ibu Rumah Tangga',
                'description' => 'Kegiatan pelatihan pengenalan komputer dan internet bagi ibu rumah tangga.',
                'category' => 'volunteer',
                'status' => 'in_progress',
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
                'status' => 'in_progress',
                'image' => 'project/project_image/mck.jpg',

            ],
            [
                'title' => 'Kelas Koding untuk Remaja Desa',
                'description' => 'Mengajarkan dasar-dasar pemrograman untuk remaja desa.',
                'category' => 'volunteer',
                'status' => 'in_progress',
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
            [
                'title' => 'Bantu Renovasi Sekolah Rusak',
                'description' => 'Penggalangan dana untuk renovasi sekolah dasar yang rusak akibat bencana alam.',
                'category' => 'donation',
                'status' => 'in_progress',
                'image' => 'project/project_image/renovasi_sekolah.jpeg',
            ],
            [
                'title' => 'Sumbangan Seragam Sekolah',
                'description' => 'Membantu anak-anak dari keluarga kurang mampu mendapatkan seragam sekolah baru.',
                'category' => 'donation',
                'status' => 'in_review',
                'image' => 'project/project_image/seragam.jpg',
            ],
            [
                'title' => 'Paket Nutrisi untuk Balita',
                'description' => 'Donasi untuk menyediakan paket nutrisi dan vitamin bagi balita di daerah rawan gizi.',
                'category' => 'donation',
                'status' => 'in_progress',
                'image' => 'project/project_image/nutrisi_balita.jpg',
            ],
            [
                'title' => 'Dukung UMKM Lokal',
                'description' => 'Pengumpulan dana untuk membantu UMKM lokal bangkit pasca pandemi.',
                'category' => 'donation',
                'status' => 'in_progress',
                'image' => 'project/project_image/dukungan_umkm.jpg',
            ],
            [
                'title' => 'Bantuan Alat Kesehatan untuk Puskesmas',
                'description' => 'Donasi untuk pengadaan alat kesehatan dasar di puskesmas terpencil.',
                'category' => 'donation',
                'status' => 'in_progress',
                'image' => 'project/project_image/alat_kesehatan.jpg',
            ],
        
            // Volunteer Projects
            [
                'title' => 'Pelatihan Bahasa Inggris Anak Desa',
                'description' => 'Mengajarkan dasar-dasar bahasa Inggris untuk anak-anak di pedesaan.',
                'category' => 'volunteer',
                'status' => 'in_progress',
                'image' => 'project/project_image/english_desa.jpg',
                'roles' => [
                    ['key' => 'Pengajar Bahasa', 'value' => 4],
                ],
                'criteria' => [
                    ['key' => 'Menguasai Bahasa Inggris Dasar', 'value' => 'Wajib', 'role' => 'Pengajar Bahasa'],
                ],
            ],
            [
                'title' => 'Pembersihan Pantai & Edukasi Sampah',
                'description' => 'Aksi bersih pantai dan edukasi warga sekitar tentang pentingnya menjaga lingkungan.',
                'category' => 'volunteer',
                'status' => 'in_progress',
                'image' => 'project/project_image/bersih_pantai.png',
                'roles' => [
                    ['key' => 'Relawan Lapangan', 'value' => 20],
                    ['key' => 'Fasilitator Edukasi', 'value' => 3],
                ],
                'criteria' => [
                    ['key' => 'Suka Aktivitas Fisik', 'value' => 'Wajib', 'role' => 'Relawan Lapangan'],
                    ['key' => 'Komunikatif dan Sabar', 'value' => 'Wajib', 'role' => 'Fasilitator Edukasi'],
                ],
            ],
            [
                'title' => 'Kampanye Cegah Pernikahan Dini',
                'description' => 'Membentuk relawan untuk edukasi bahaya pernikahan dini di desa-desa.',
                'category' => 'volunteer',
                'status' => 'in_progress',
                'image' => 'project/project_image/kampanye_cegah.jpg',
                'roles' => [
                    ['key' => 'Fasilitator Diskusi', 'value' => 5],
                ],
                'criteria' => [
                    ['key' => 'Berpengalaman sebagai Penyuluh', 'value' => 'Opsional', 'role' => 'Fasilitator Diskusi'],
                ],
            ],
            [
                'title' => 'Pelatihan Kewirausahaan Remaja',
                'description' => 'Melatih remaja desa dengan keterampilan kewirausahaan dan bisnis dasar.',
                'category' => 'volunteer',
                'status' => 'in_progress',
                'image' => 'project/project_image/kewirausahaan.jpg',
                'roles' => [
                    ['key' => 'Trainer Bisnis', 'value' => 4],
                    ['key' => 'Mentor Keuangan', 'value' => 2],
                ],
                'criteria' => [
                    ['key' => 'Punya Usaha Sendiri', 'value' => 'Wajib', 'role' => 'Trainer Bisnis'],
                    ['key' => 'Menguasai Pengelolaan Keuangan Dasar', 'value' => 'Wajib', 'role' => 'Mentor Keuangan'],
                ],
            ],
            [
                'title' => 'Edukasi Kesehatan Gigi untuk Anak',
                'description' => 'Kegiatan edukasi kebersihan dan kesehatan gigi untuk siswa SD.',
                'category' => 'volunteer',
                'status' => 'in_progress',
                'image' => 'project/project_image/kesehatan_gigi.jpg',
                'roles' => [
                    ['key' => 'Edukator Gigi', 'value' => 5],
                ],
                'criteria' => [
                    ['key' => 'Latar Belakang Kesehatan', 'value' => 'Opsional', 'role' => 'Edukator Gigi'],
                ],
            ],


            //tambahan 5 donasi
            [
                'title' => 'Bantu Biaya Operasi Anak Penderita Jantung Bawaan',
                'description' => 'Penggalangan dana untuk anak-anak penderita jantung bawaan yang membutuhkan tindakan medis segera.',
                'category' => 'donation',
                'status' => 'in_progress',
                'image' => 'project/project_image/operasi_anak.webp',
            ],
            [
                'title' => 'Sumbangan Pakaian Layak Pakai Musim Dingin',
                'description' => 'Donasi pakaian hangat untuk masyarakat di dataran tinggi menjelang musim dingin.',
                'category' => 'donation',
                'status' => 'in_progress',
                'image' => 'project/project_image/pakaian_dingin.jpg',
            ],
            [
                'title' => 'Bantuan Modal UMKM Wanita',
                'description' => 'Dukungan dana untuk perempuan pelaku UMKM agar dapat mengembangkan usahanya.',
                'category' => 'donation',
                'status' => 'in_progress',
                'image' => 'project/project_image/umkm_wanita.jpeg',
            ],
            [
                'title' => 'Donasi Alat Tulis untuk Siswa Kurang Mampu',
                'description' => 'Mengumpulkan dana untuk membeli alat tulis bagi siswa di daerah terpencil.',
                'category' => 'donation',
                'status' => 'in_active',
                'image' => 'project/project_image/alat_tulis.jpg',
            ],
            [
                'title' => 'Pengadaan Air Bersih di Wilayah Kering',
                'description' => 'Pengumpulan dana untuk membuat sumur dan sarana air bersih di desa kekeringan.',
                'category' => 'donation',
                'status' => 'in_progress',
                'image' => 'project/project_image/air_bersih.jpeg',
            ],

            //Tambahan 5 volunteer
            [
                'title' => 'Relawan Pengajar Bahasa Inggris di Pulau Terpencil',
                'description' => 'Mengajak pengajar sukarela untuk mengajar Bahasa Inggris dasar.',
                'category' => 'volunteer',
                'status' => 'in_progress',
                'image' => 'project/project_image/relawan_bahasa.png',
                'roles' => [
                    ['key' => 'Pengajar', 'value' => 4],
                ],
                'criteria' => [
                    ['key' => 'TOEFL Minimal 450', 'value' => 'Wajib', 'role' => 'Pengajar'],
                    ['key' => 'Bersedia Tinggal di Pulau', 'value' => 'Wajib', 'role' => 'all'],
                ],
            ],
            [
                'title' => 'Pendamping Lansia di Panti Jompo',
                'description' => 'Kegiatan menemani, membantu aktivitas, dan berbagi cerita dengan lansia.',
                'category' => 'volunteer',
                'status' => 'in_active',
                'image' => 'project/project_image/panti_jompo.jpg',
                'roles' => [
                    ['key' => 'Pendamping', 'value' => 6],
                ],
                'criteria' => [
                    ['key' => 'Sabar dan Komunikatif', 'value' => 'Wajib', 'role' => 'Pendamping'],
                ],
            ],
            [
                'title' => 'Pelatihan Fotografi untuk Remaja Kreatif',
                'description' => 'Mengajar dasar fotografi untuk remaja dari keluarga prasejahtera.',
                'category' => 'volunteer',
                'status' => 'in_progress',
                'image' => 'project/project_image/fotografi.jpg',
                'roles' => [
                    ['key' => 'Fotografer', 'value' => 2],
                ],
                'criteria' => [
                    ['key' => 'Punya Kamera Sendiri', 'value' => 'Opsional', 'role' => 'Fotografer'],
                ],
            ],
            [
                'title' => 'Pembersihan Sungai dan Edukasi Lingkungan',
                'description' => 'Aksi sukarela membersihkan sungai dan mengedukasi warga sekitar tentang lingkungan.',
                'category' => 'volunteer',
                'status' => 'in_progress',
                'image' => 'project/project_image/bersih_sungai.webp',
                'roles' => [
                    ['key' => 'Koordinator Lapangan', 'value' => 1],
                    ['key' => 'Relawan Lapangan', 'value' => 10],
                ],
                'criteria' => [
                    ['key' => 'Tahan Aktivitas Fisik', 'value' => 'Wajib', 'role' => 'Relawan Lapangan'],
                    ['key' => 'Punya Sepatu Boots', 'value' => 'Opsional', 'role' => 'all'],
                ],
            ],
            [
                'title' => 'Mentoring Karier untuk Remaja Sekolah',
                'description' => 'Relawan sebagai mentor karier bagi remaja SMA di daerah 3T.',
                'category' => 'volunteer',
                'status' => 'in_progress',
                'image' => 'project/project_image/mentoring.jpg',
                'roles' => [
                    ['key' => 'Mentor', 'value' => 5],
                ],
                'criteria' => [
                    ['key' => 'Pengalaman di Dunia Kerja > 2 Tahun', 'value' => 'Wajib', 'role' => 'Mentor'],
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
