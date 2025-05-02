<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $paymentMethods = [
            [
                'payment_method_id' => Str::uuid(),
                'payment_code' => 'GOPAY',
                'payment_type' => 'e-wallet',
                'payment_name' => 'GoPay',
                'icon' => 'gopay.png',
                'fee' => 2000.00,
                'payment_description' => 'Pembayaran menggunakan GoPay',
            ],
            [
                'payment_method_id' => Str::uuid(),
                'payment_code' => 'OVO',
                'payment_type' => 'e-wallet',
                'payment_name' => 'OVO',
                'icon' => 'ovo.png',
                'fee' => 2500.00,
                'payment_description' => 'Pembayaran menggunakan OVO',
            ],
            [
                'payment_method_id' => Str::uuid(),
                'payment_code' => 'DANA',
                'payment_type' => 'e-wallet',
                'payment_name' => 'DANA',
                'icon' => 'dana.png',
                'fee' => 2000.00,
                'payment_description' => 'Pembayaran menggunakan DANA',
            ],
            [
                'payment_method_id' => Str::uuid(),
                'payment_code' => 'SHOPEEPAY',
                'payment_type' => 'e-wallet',
                'payment_name' => 'ShopeePay',
                'icon' => 'shopeepay.png',
                'fee' => 1500.00,
                'payment_description' => 'Pembayaran menggunakan ShopeePay',
            ],
            [
                'payment_method_id' => Str::uuid(),
                'payment_code' => 'BCA_TRANSFER',
                'payment_type' => 'transfer bank',
                'payment_name' => 'BCA Transfer',
                'icon' => 'bca.png',
                'fee' => 5000.00,
                'payment_description' => 'Pembayaran melalui transfer bank BCA',
            ],
            [
                'payment_method_id' => Str::uuid(),
                'payment_code' => 'MANDIRI_TRANSFER',
                'payment_type' => 'transfer bank',
                'payment_name' => 'Mandiri Transfer',
                'icon' => 'mandiri.png',
                'fee' => 5000.00,
                'payment_description' => 'Pembayaran melalui transfer bank Mandiri',
            ],
            [
                'payment_method_id' => Str::uuid(),
                'payment_code' => 'BNI_TRANSFER',
                'payment_type' => 'transfer bank',
                'payment_name' => 'BNI Transfer',
                'icon' => 'bni.png',
                'fee' => 5000.00,
                'payment_description' => 'Pembayaran melalui transfer bank BNI',
            ],
            [
                'payment_method_id' => Str::uuid(),
                'payment_code' => 'BRI_TRANSFER',
                'payment_type' => 'transfer bank',
                'payment_name' => 'BRI Transfer',
                'icon' => 'bri.png',
                'fee' => 5000.00,
                'payment_description' => 'Pembayaran melalui transfer bank BRI',
            ],
            [
                'payment_method_id' => Str::uuid(),
                'payment_code' => 'VISA_CREDIT',
                'payment_type' => 'credit card',
                'payment_name' => 'Visa Credit Card',
                'icon' => 'visa.png',
                'fee' => 10000.00,
                'payment_description' => 'Pembayaran menggunakan kartu kredit Visa',
            ],
            [
                'payment_method_id' => Str::uuid(),
                'payment_code' => 'MASTERCARD_CREDIT',
                'payment_type' => 'credit card',
                'payment_name' => 'Mastercard Credit Card',
                'icon' => 'mastercard.png',
                'fee' => 10000.00,
                'payment_description' => 'Pembayaran menggunakan kartu kredit Mastercard',
            ],
        ];

        DB::table('payment_methods')->insert($paymentMethods);
    }
}
