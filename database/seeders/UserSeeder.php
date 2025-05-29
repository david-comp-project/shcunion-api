<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserStatusEnum;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Testing\Fakes\Fake;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {   
        $faker = Faker::create();
        $jenisKelamin = ['laki-laki', 'perempuan'];
        $userAdmin = [
            [
                'first_name' => 'David',
                'last_name'  => 'Dwi Nugroho',
                'email'      => 'daviddwinugraha2@gmail.com',
                'profile_picture' => 'profile/45a231ea-ffb3-4cd0-b88a-7f2c51c06930_user-default.png',
            ],
            [
                'first_name' => 'Super',
                'last_name'  => 'Admin',
                'email'      => 'superadmin@gmail.com',
                'profile_picture' => 'profile/45a231ea-ffb3-4cd0-b88a-7f2c51c06930_user-default.png',
            ],
        ];

        foreach ($userAdmin as $userData) {
            $account = User::create([
                'first_name'       => $userData['first_name'],
                'last_name'        => $userData['last_name'],
                // Jika Anda ingin menggunakan computed full_name, Anda bisa menggabungkannya
                'full_name'        => $userData['first_name'] . ' ' . $userData['last_name'],
                'jenis_kelamin'    => $jenisKelamin[array_rand($jenisKelamin)],
                'profile_picture'  => $userData['profile_picture'], // Kosongkan atau isi sesuai kebutuhan
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
                'job'              => 'Developer', // Default kosong
                'organization_name' => 'SHCUnion Company', // Nama organisasi default
                'jabatan'          => 'Founder', // Jabatan default
                'status'          => UserStatusEnum::VERIFIED, // Default active
                'total_points' => 100, // Total poin default
            ]);

            $account->assignRole('admin');
        }

        // Buat 10 user dengan role 'verified'
        for ($i = 0; $i < 10; $i++) {
        
            $user = User::create([
                    'first_name'       => $faker->firstName(),
                    'last_name'        => $faker->lastName(),
                    // Jika Anda ingin menggunakan computed full_name, Anda bisa menggabungkannya
                    'full_name'        => $faker->firstName() . ' ' . $faker->lastName(),
                    'jenis_kelamin'    => $jenisKelamin[array_rand($jenisKelamin)],
                    'profile_picture'  => null, // Kosongkan atau isi sesuai kebutuhan
                    'email'            => $faker->unique()->safeEmail(),
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
            $user->assignRole('verified');

        }
    }
}
