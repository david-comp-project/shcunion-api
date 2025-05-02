<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            WilayahSeeder::class,
            TagSeeder::class,
            IconSeeder::class,
            PaymentMethodSeeder::class,
            SocialMediaSeeder::class,
        ]
        );
    }
}
