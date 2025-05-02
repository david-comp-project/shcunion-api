<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisKelamin = ['laki-laki', 'perempuan'];
        $users = [
            [
                'first_name' => 'David',
                'last_name'  => 'Dwi Nugroho',
                'email'      => 'daviddwinugraha2@gmail.com',
            ],
            [
                'first_name' => 'Super',
                'last_name'  => 'Admin',
                'email'      => 'superadmin@gmail.com',
            ],
        ];

        foreach ($users as $userData) {
            $account = User::create([
                'first_name'       => $userData['first_name'],
                'last_name'        => $userData['last_name'],
                // Jika Anda ingin menggunakan computed full_name, Anda bisa menggabungkannya
                'full_name'        => $userData['first_name'] . ' ' . $userData['last_name'],
                'jenis_kelamin'    => $jenisKelamin[array_rand($jenisKelamin)],
                'profile_picture'  => null, // Kosongkan atau isi sesuai kebutuhan
                'email'            => $userData['email'],
                'password'         => bcrypt('password123'),
                'email_verified_at'=> now(),

                // Kolom tambahan pada tabel users
                'address'          => '', // Berikan nilai default (misalnya, kosong)
                'social_media'     => json_encode([
                    'facebook' => '',
                    'twitter'  => '',
                    'instagram'=> '',
                    'linkedin' => '',
                    'github'   => '',
                    'medium'   => '',
                ]), // Mengisi dengan JSON kosong
                'phone_number'     => '', // Default kosong
                'nik'              => '', // Default kosong
                'birth_date'       => null, // Nilai null jika belum diketahui
                'job'              => '', // Default kosong
                'status'          => 'verified', // Default active
            ]);

            $account->assignRole('admin');
        }
    }
}
