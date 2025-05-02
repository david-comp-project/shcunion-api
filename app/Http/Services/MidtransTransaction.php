<?php

namespace App\Http\Services;

use Midtrans\Snap;
use Midtrans\Config;
use Illuminate\Http\Request;
use App\Models\DonationPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MidtransTransaction
{
    /**
     * Create a new class instance.
     */

    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function processCheckout($data) {
        // Process checkout here

        $params = [
            'transaction_details' => [
                'order_id' => $data->donation_payment_id,
                'gross_amount' => $data->donation_amount,
            ],
            'customer_details' => [
                'first_name' => Auth('api')->user()->first_name,
                'last_name' => Auth('api')->user()->last_name,
                'email' => Auth('api')->user()->email,
                'phone' => Auth('api')->user()->phone,
            ],
        ];

        // Tentukan payment_type berdasarkan channel_payment
        switch ($data->channel_payment) {
            case 'bank_transfer':
                $params['payment_type'] = 'bank_transfer';
                $params['bank_transfer'] = [
                    'bank' => $data->channel_name // Bisa diubah berdasarkan pilihan user
                ];
                break;

            case 'qris':
                $params['payment_type'] = 'qris';
                $params['qris'] = [
                    'acquirer' => $data->channel_name // Bisa diubah berdasarkan pilihan user
                ];
                break;

            default:
                throw new \Exception("Invalid Payment Type");
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode(Config::$serverKey),
            'Content-Type' => 'application/json'
        ])->post('https://api.sandbox.midtrans.com/v2/charge', $params);
        
        $responseData = $response->json();
        
        // Cek apakah ada tindakan generate-qr-code di response
        $qrCodeUrl = null;
        if (isset($responseData['actions'])) {
            foreach ($responseData['actions'] as $action) {
                if ($action['name'] === 'generate-qr-code') {
                    $qrCodeUrl = $action['url'];
                    break;
                }
            }
        }
        
        // Tambahkan URL QR code ke response
        $responseData['qr_code_url'] = $qrCodeUrl;
        
        return $responseData;
        


    }

    public function checkTransactionStatus($data)
    {
        $order_id = $data->donation_payment_id; // ✅ Perbaikan tanda koma
    
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode(config('midtrans.server_key')), // ✅ Perbaikan akses konfigurasi
                'Content-Type' => 'application/json'
            ])->get("https://api.sandbox.midtrans.com/v2/{$order_id}/status"); // ✅ Perbaikan template string
    
            return $response->json()['transaction_status'] ?? 'unknown'; // ✅ Pastikan key benar dan tangani null
        } catch (\Exception $e) {
            return 'error'; // ✅ Tangani error jika request gagal
        }
    }
    

    public function processNotification($request)
    {
        $serverKey = config('midtrans.server_key');
        $order_id = $request->order_id ?? null;
        $status_code = $request->status_code ?? null;
        $gross_amount = $request->gross_amount ?? null;
        $signature_key = $request->signature_key ?? null;

        $expected_signature = hash("sha512", $order_id . $status_code . $gross_amount . $serverKey);
        if ($signature_key !== $expected_signature) {
            return response()->json(['message' => 'Invalid signature key'], 403);
        }

        $donation = DonationPayment::where('donation_payment_id', $order_id)->first();
        if (!$donation) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        switch ($request->transaction_status) {
            case 'capture':
            case 'settlement':
                $donation->status = 'paid';
                break;
            case 'pending':
                $donation->status = 'pending';
                break;
            case 'expire':
            case 'cancel':
            case 'deny':
                $donation->status = 'failed';
                break;
            default:
                return response()->json(['message' => 'Unknown transaction status'], 400);
        }

        $donation->save();

        return [
            'message' => 'Notification processed successfully',
            'donation' => $donation
        ];
    }

    public function getSnapToken($data)
    {
        $orderId = $data->donation_payment_id;
        $amount = $data->donation_amount;
    
        // Ambil informasi tambahan
        $user = Auth('api')->user();
            // $project = Project::find($data->project_id); // Pastikan relasi project tersedia
        $project = $data->project; // ✅ Perbaikan relasi project
        $transaction = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'billing_address' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,

                ],
            ],
            'item_details' => [
                [
                    'id' => $project->project_id,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => $project->project_title, // Judul proyek
                    'brand' => 'Donasi Proyek',
                    'category' => 'Donasi',
                    'merchant_name' => 'SHC Union',
                ],
            ]
        ];
    
        try {
            $snapToken = Snap::getSnapToken($transaction);
    
            if (!$snapToken) {
                Log::error('Midtrans Snap Token is null.');
                return response()->json(['message' => 'Failed to get snap token'], 500);
            }
    
            return response()->json([
                'snap_token' => $snapToken,
                'order_id' => $orderId
            ], 200);
        } catch (\Exception $e) {
            Log::error('Midtrans Error: ' . $e->getMessage());
            return response()->json(['message' => 'Midtrans API error'], 500);
        }
    }
    
    

    public function updateStatusSnap(Request $request)
    {
        $donation = DonationPayment::where('donation_payment_id', $request->order_id)->first();
        
        if ($donation) {
            $donation->status = $request->status;
            $donation->save();
        }

        return response()->json(['message' => 'Status updated']);
    }


}
