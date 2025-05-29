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
        $donationProjects = Project::where('project_category', 'donation')
                                   ->pluck('project_id')
                                   ->toArray();
        $users    = User::pluck('user_id')->toArray();
        $methods  = PaymentMethod::pluck('payment_method_id')->toArray();

        if (empty($donationProjects)) {
            $this->command->warn('Tidak ada project dengan kategori donation.');
            return;
        }

        // Data dummy manual
        $statuses     = ['paid', 'pending', 'failed', 'expired'];
        $channels     = ['bank_transfer', 'ewallet', 'qris', 'credit_card'];
        $channelNames = ['BCA', 'Mandiri', 'OVO', 'DANA', 'ShopeePay', 'Gopay', 'VISA', 'MasterCard'];
        $amounts      = [25000, 50000, 100000, 150000, 200000, 500000, 1000000, 2000000, 5000000];
        $domains      = ['example.com', 'mail.com', 'web.id'];
        $streets      = ['Jl. Merdeka No.1', 'Jl. Sudirman No.5', 'Jl. Thamrin No.10', 'Jl. Gatot Subroto No.20'];

        foreach ($donationProjects as $projectId) {
            $project = Project::find($projectId);
            if ($project->project_status === 'in_review') {
                continue;
            }

            $count = rand(50, 110);

            for ($i = 0; $i < $count; $i++) {
                // 70% donatur login, 30% anonymous
                $donaturId = (rand(1, 100) <= 70 && !empty($users))
                             ? $users[array_rand($users)]
                             : null;

                // Random donation amount
                $donationAmount = $amounts[array_rand($amounts)];

                // Generate dummy donor info
                if ($donaturId) {
                    $email    = 'user' . rand(1, 1000) . '@' . $domains[array_rand($domains)];
                    $fullName = 'User' . rand(1, 1000);
                    $address  = $streets[array_rand($streets)];
                    $phone    = '08' . rand(100000000, 999999999);
                } else {
                    $email    = 'anon' . rand(1, 1000) . '@' . $domains[array_rand($domains)];
                    $fullName = 'Anon Donatur';
                    $address  = $streets[array_rand($streets)];
                    $phone    = '08' . rand(100000000, 999999999);
                }

                DonationPayment::create([
                    'donation_payment_id' => Str::uuid(),
                    'donation_code'       => 'DN-' . strtoupper(Str::random(8)),
                    'project_id'          => $projectId,
                    'donatur_id'          => $donaturId,
                    'email'               => $email,
                    'full_name'           => $fullName,
                    'address'             => $address,
                    'phone_number'        => $phone,
                    'donation_amount'     => $donationAmount,
                    'channel_payment'     => $channels[array_rand($channels)],
                    'channel_name'        => $channelNames[array_rand($channelNames)],
                    'payment_method_id'   => (rand(1, 100) <= 80 && !empty($methods))
                                             ? $methods[array_rand($methods)]
                                             : null,
                    'status'              => $statuses[array_rand($statuses)],
                    'transaction_id'      => Str::uuid(),
                    'transaction_time'    => now()
                                             ->subDays(rand(0, 30))
                                             ->addMinutes(rand(0, 1440)),
                ]);
            }
        }
    }
}
