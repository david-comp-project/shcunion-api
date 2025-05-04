<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\DonationPayment;
use App\Models\PaymentMethod;

class ProjectDonationSeeder extends Seeder
{
    public function run(): void
    {
        $donationProjects = Project::where('project_category', 'donation')->pluck('project_id')->toArray();
        $users = User::pluck('user_id')->toArray();
        $methods = PaymentMethod::pluck('payment_method_id')->toArray();

        if (empty($donationProjects)) {
            $this->command->warn('Tidak ada project dengan kategori donation.');
            return;
        }

        $statuses = ['paid', 'pending', 'failed', 'expired'];
        $channels = ['bank_transfer', 'ewallet', 'qris', 'credit_card'];
        $channelNames = ['BCA', 'Mandiri', 'OVO', 'DANA', 'ShopeePay', 'Gopay', 'VISA', 'MasterCard'];

        foreach ($donationProjects as $projectId) {
            $count = rand(50, 100); // Setiap project dapat 3-10 donasi

            for ($i = 0; $i < $count; $i++) {
                $donaturId = fake()->boolean(70) ? $users[array_rand($users)] : null; // 70% donatur login, 30% anonymous
                $donationAmount = fake()->randomElement([25000, 50000, 100000, 150000, 200000, 500000, 1000000, 2000000, 5000000]);

                DonationPayment::create([
                    'donation_payment_id' => Str::uuid(),
                    'donation_code' => 'DN-' . strtoupper(Str::random(8)),
                    'project_id' => $projectId,
                    'donatur_id' => $donaturId,
                    'email' => fake()->safeEmail(),
                    'full_name' => fake()->name(),
                    'address' => fake()->address(),
                    'phone_number' => fake()->phoneNumber(),
                    'donation_amount' => $donationAmount,
                    'channel_payment' => $channels[array_rand($channels)],
                    'channel_name' => $channelNames[array_rand($channelNames)],
                    'payment_method_id' => fake()->boolean(80) ? $methods[array_rand($methods)] : null,
                    'status' => $statuses[array_rand($statuses)],
                    'transaction_id' => Str::uuid(),
                    'transaction_time' => now()->subDays(rand(0, 30))->addMinutes(rand(0, 1440)),
                ]);
            }
        }
    }
}
