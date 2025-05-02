<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Traits\HasFileTrait;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\DonationPayment;
use Illuminate\Validation\Rule;
use App\Enums\ProjectStatusEnum;
use App\Models\WithdrawalDonation;
use App\Enums\WithdrawalStatusEnum;
use Illuminate\Support\Facades\Log;
use App\Events\DonationPaymentEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Services\MidtransTransaction;
use App\Enums\DonationTransactionStatusEnum;

class DonationController extends Controller
{
    use HasFileTrait;
    
    protected $midtransService;

    public function __construct(MidtransTransaction $midtransTransaction)
    {
        $this->midtransService = $midtransTransaction;
    }

    public function getDonaturList(Project $project) {
        // if (Auth('api')->user()->user_id !== $project->creator_id) {
        //     return response()->json([
        //         'message' => 'Anda tidak memiliki akses untuk melihat data volunteer',
        //     ], 403);
        // }
        $cacheKey = "project_{$project->project_id}_donation_list";
        
        $donaturs = Cache::remember($cacheKey, now()->addMinutes(3), function () use ($project) {
            $donaturs = $project->projectDonations;

            $donaturs = $donaturs->map(function ($donatur) {
                return [
                    'project_donatur_id' => $donatur->donation_payment_id,
                    'project_title' => $donatur->project->project_title ?? 'Unknown Project',
                    'donatur_name' => $donatur->donatur
                        ? ($donatur->donatur->full_name ?? $donatur->donatur->userFullName ?? 'Anonymous')
                        : 'Anonymous',
                    'donatur_amount' => $donatur->donation_amount,
                    'donatur_avatar' => $donatur->donatur && $donatur->donatur->profile_picture
                        ? asset(Storage::url($donatur->donatur->profile_picture))
                        : null,
                    'donatur_channel_payment' => $donatur->channel_payment,
                    'donatur_channel_name' => $donatur->channel_name,
                    'donatur_status_payment' => $donatur->status,
                    'donatur_date_payment' => $donatur->dateFormat,
                    'donatur_date' => $donatur->created_at
                ];
            })->sortByDesc('donatur_date')->values();

            return $donaturs;
        });
        
        if ($donaturs->isEmpty()) {
            return response()->json([
                'message' => 'Data donatur tidak ditemukan',
                'donaturs' => []
            ], 404);
        }

        return response()->json([
            'message' => 'Data donatur',
            'donaturs' => $donaturs
        ], 200);
    }

    
    public function checkPayment(Request $request) {
        try {
            $user_id = Auth('api')->check() ? Auth('api')->user()->user_id : null;

            $validator = Validator::make($request->all(), [
                'sub_total' => 'required|numeric|min:1000',
                'channel_payment' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            $paymentMethod = PaymentMethod::where('payment_name', $validated['channel_payment'])->first();

            if (!$paymentMethod) {
                return response()->json([
                    'message' => 'Payment method not found'
                ], 400);
            }

            $validated['donation_code'] = 'DON_' . Str::uuid();
            $validated['payment_method_id'] = $paymentMethod->payment_method_id;
            $validated['status'] = 'checkout';
            $validated['donatur_id'] = $user_id;
            $validated['donation_amount'] = $validated['sub_total'] + $paymentMethod->payment_fee;
            $validated['channel_payment'] = $paymentMethod->payment_name;
            $validated['channel_name'] = $paymentMethod->channel_name;

            // Create donation payment
            $donationPayment = DonationPayment::create($validated);


            // Proses transaksi ke Midtrans
            $result = $this->midtransService->processCheckout($donationPayment);

            return response()->json([
                'message' => 'Payment Calculation Successfully Generated',
                'donation_check_out' => $result,
                'qr_code_url' => $result['qr_code_url'] ?? null // Pastikan URL QR Code dikirim
            ], 200);
            


        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error',
                'error' => $e->getMessage()
            ], 400);
        }
    }


    public function handleWebhook(Request $request) {
        $response = $this->midtransService->processNotification($request);

        $donation = $response['donation'];

        //broadcast event ke client
        broadcast(new DonationPaymentEvent($donation))->via('pusher');
    }

    public function checkStatusPayment(DonationPayment $donationPayment) {
        $status = $this->midtransService->checkTransactionStatus($donationPayment);

        return response()->json([
            'message' => 'Transaction status',
            'status' => $status
        ], 200);
    }

    public function handleSnapCallback(Request $request, Project $project, DonationPayment $donationPayment) {
        $user = Auth('api')->user();
        $data = $request->all();
    
        // Debugging: Cek apakah data diterima dengan benar
        if (empty($data)) {
            return response()->json(['error' => 'No data received'], 400);
        }
    
        if (!isset($data['transaction_status']) || !isset($data['payment_type'])) {
            return response()->json(['error' => 'Missing required fields'], 400);
        }
    
        // Update hanya jika model ditemukan
        if ($donationPayment) {
            $donationPayment->update([
                'status' => $data['transaction_status'],
                'channel_payment' => $data['payment_type'],
                'channel_name' => $data['bank'] ?? $data['payment_type'],
                'phone_number' => $data['phone_number'],
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'transaction_id' => $data['transaction_id'],
                'transaction_time' => $data['transaction_time'],
            ]);
        }
        
        $project = Project::with('projectDonations')->find($donationPayment->project_id); // Sesuaikan dengan ID project yang dicari

        if ($project) {
            $totalDonation = $project->projectDonations->sum('donation_amount');

            if ($totalDonation > $project->project_target_amount) {
                $project->project_status = ProjectStatusEnum::COMPLETED->value;
                $project->completed_at = now()->addDays(7);
                $project->save();

                Notification::create([
                    'notification_title' => 'Status Project Selesai!',
                    'notification_icon' => 'ui uil-project',
                    'notification_text' => 'Status project ' . $project->project_title . ' telah diperbarui menjadi ' . $project->project_status . '. Silakan tinjau perubahan status pada halaman project untuk informasi lebih lanjut.',
                    'notification_url' => "/dashboard/project/{$project->project_id}",
                    'target_id' => $project->creator_id
                ]);
            }
        }

        $user = User::where('user_id', $user->user_id)->first();
        $projectGroupChat = Project::where('project_id', $donationPayment->project_id)->first();

        $user->increment('total_points', 50);
    
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }
    
        $groupChat = $projectGroupChat->groupChat;
    
        if (!$groupChat) {
            return response()->json(['error' => 'Group chat not found'], 404);
        }
    
        // Pastikan group_chat_id adalah string
        $groupChatId = (string) $groupChat->group_chat_id;
    
        // Log ID Group Chat
        // Log::info('Group Chat ID Donation:', [$groupChatId]);
    
        // Cek apakah user sudah terdaftar dalam grup dengan tabel eksplisit
        if (!$user->groupChats()->where('users_group_chats.group_chat_id', $groupChatId)->exists()) {
            $user->groupChats()->attach($groupChatId);
            $cacheKey = "user_{$user->user_id}_group_list";

            Cache::forget($cacheKey);
            
            // Log::info('User Masuk ? ');
        }
    
        return response()->json(['message' => 'Payment updated successfully'], 201);
    }
    

    
    public function getSnapMidtrans(Request $request, Project $project) {
        $validated = $request->validate([
            'donation_amount' => 'required|numeric|min:10000',
        ]);
    
        $user = Auth('api')->check() ? Auth('api')->user() : null;
    
        $donation = DonationPayment::create([
            'donation_code' => 'DON_' . Str::uuid(),
            'project_id' => $project->project_id,
            'donatur_id' => $user->user_id,
            'email' => $user->email,
            'address' => $user->address,
            'full_name' => $user->full_name,
            'phone_number' => $user->phone_number,
            'donation_amount' => $validated['donation_amount'],
        ]);
    
        return $this->midtransService->getSnapToken($donation);
    }
    

    public function updateStatusSnap(Request $request) {
        $this->midtransService->updateStatusSnap($request);
    }

    

    public function transactionDonation(Request $request) {

        $validated = $request->validated();

        $paymentMethod = PaymentMethod::where('payment_method_id', $validated['payment_method_id'])->first();

        $donation = DonationPayment::create([
            'donation_code' => 'DON_' . Str::uuid(),
            'project_id' => $validated['project_id'],
            'donatur_id' => $validated['donatur_id'] ? $validated['donatur_id'] : 'anonim',
            'donation_amount' => $validated['donation_amount'],
            'channel_payment' => $paymentMethod->payment_name,
            'payment_method_id' => $paymentMethod->payment_method_id,
            'status' => DonationTransactionStatusEnum::PENDING->value
        ]);

        if ($donation) {
            return response()->json([
                'message' => 'Succes',
                'donation' => $donation
            ], 200);
        }
    }

    public function getWithdrawalDonation(Project $project) {
        //check admin
        $withdrawalDonation = $project->projectWithdrawal;
        $withdrawalDonation['project_title'] = $project->project_title;
        $withdrawalDonation['project_category'] = $project->project_category;
        $withdrawalDonation['project_status'] = $project->project_status;
        $withdrawalDonation['project_target_amount'] = $project->project_target_amount;
        // $withdrawalDonation['project_progress_amount'] = $project->projectDonations()->sum('donation_amount');
        $withdrawalDonation['scan_rekening'] = $this->getUrlFile($withdrawalDonation->scan_rekening);
        $withdrawalDonation['bukti_transfer'] = $this->getUrlFile($withdrawalDonation->bukti_transfer);

        if (!$withdrawalDonation) {
            return response()->json([
                'message' => 'Data Withdrawal Tidak Ditemukan',
                'withdrawal_detail' => []
            ], 404);
        }

        return response()->json([
            'message' => 'Data Withdrawal Tidak Ditemukan',
            'withdrawal_detail' => $withdrawalDonation
        ], 200);
    }

    public function storeWithdrawalDonation(Request $request, Project $project) {
        $user = Auth('api')->user();
  
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string',
                'email' => 'required|email',
                'address' => 'required|string',
                'phone_number' => 'required|string',
                'nama_penerima' =>  'required|string',
                'channel_bank' =>  'required|string',
                'nomor_rekening' =>  'required|string',
                'jumlah_penarikan' => 'required|numeric|min:0',
                'scan_rekening' => 'required|image|mimes:jpg,jpeg,png|max:4096',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            if ($request->hasFile('scan_rekening')) {
                $file = $request->file('scan_rekening');
                $imagePath = $this->getPathFile($file, 'withdrawal');

                $validated['scan_rekening'] = $imagePath;
            }

            $validated['project_id'] = $project->project_id;
            $validated['user_id'] = $user->user_id;
            $validated['status_penarikan'] = WithdrawalStatusEnum::PROPOSED->value;


            $withdrawalDonation = WithdrawalDonation::create($validated);

            

            return response()->json([
                'message' => 'Project Detail Updated',
                'project_id' => $project->project_id,
                'withdrawal_detail' => $withdrawalDonation
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error',
                'error' => $th->getMessage()
            ], 400);
        }
    }

    public function updateWithdrawalDonationStatus(Request $request, Project $project, WithdrawalDonation $withdrawalDonation) {
        
        $imagePath = $withdrawalDonation->bukti_transfer; // Default pakai yang lama jika tidak ada file baru
    
        if ($request->hasFile('bukti_transfer')) {
            $validated = $request->validate([
                'bukti_transfer' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
                'status' => ['required', Rule::in(WithdrawalStatusEnum::values())],           
            ]);
    
            $file = $request->file('bukti_transfer');
            $newStatus = $request->validated['status'];

    
            // Simpan file ke folder 'withdrawal' di disk 'public'
            $imagePath = $this->getPathFile($file, 'withdrawal', 'public');
        }
    
        // Update data withdrawal
        $withdrawalDonation->bukti_transfer = $imagePath;
        $withdrawalDonation->status_penarikan = $newStatus;
    
        $withdrawalDonation->save();
    
        return response()->json([
            'message' => 'Status Withdrawal Donasi Berhasil Diubah',
            'withdrawal_detail' => [
                'id' => $withdrawalDonation->id,
                'status_penarikan' => $withdrawalDonation->status_penarikan,
                'bukti_transfer_url' => $this->getUrlFile($withdrawalDonation->bukti_transfer, 'public')
            ]
        ], 201);
    }
    
}
