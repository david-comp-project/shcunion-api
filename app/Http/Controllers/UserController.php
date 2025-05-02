<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\ReportCase;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Traits\HasFileTrait;
use Illuminate\Http\Request;
use App\Enums\UserStatusEnum;
use App\Events\NotificationEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    use HasFileTrait;
    
    protected $currentMonth;
    protected $lastMonth;

    public function __construct()
    {
        $this->currentMonth = Carbon::now('UTC');
        $this->lastMonth    = Carbon::now('UTC')->subMonth();
    }
    

    public function getUserProfile(User $user) {
        // Cache dengan key "user_profile_{id}" selama 10 menit
        $cacheKey = "user_profile_{$user->user_id}";
    
        $userData = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            return [
                ...$user->toArray(),
                'profile_picture' => $user->profile_picture ? asset(Storage::url($user->profile_picture)) : null,
                'profile_cover' => $user->profile_cover ? asset(Storage::url($user->profile_cover)) : null,
                'scan_ktp' => $user->scan_ktp ? asset(Storage::url($user->scan_ktp)) : null,
                'badge' => $user->getBadge()->value,
                'badge_color' => $user->getBadgeColor(),
                'suspended_time' => $user->suspended_date ? (int) Carbon::parse($user->suspended_date)->diffInDays(now()) * -1: null, // Hitung hari tersisa
                
            ];
        });
    
        return response()->json([
            'message' => 'Success',
            'user' => $userData,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray()
        ], 200);
    }

    public function updateUserProfile(Request $request, User $user) {
        if ($user->user_id !== Auth('api')->user()->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'nullable|string',
                'email' => 'nullable|email',
                'address' => 'nullable|string',
                'phone_number' => 'nullable|string',
                'jenis_kelamin' => 'nullable|string|in:laki-laki,perempuan',
                'nik' => 'nullable|string',
                'birth_date' => 'nullable|date',
                'job' => 'nullable|string',
                'organization_name' => 'nullable|string',
                'jabatan' => 'nullable|string',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'profile_cover' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
                'scan_ktp' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
                'social_media' => 'nullable|json',

            ]);



            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            
            $validated = $validator->validated();

            if (isset($validated['social_media'])) {
                $validated['social_media'] = json_decode($validated['social_media'], true);
            }

            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                $fileName = Str::uuid() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('profile', $fileName, 'public');
                $validated['profile_picture'] = $filePath;

                // Log::info("Profile Picture saved at: " . $filePath);
            }

            if ($request->hasFile('profile_cover')) {
                $file = $request->file('profile_cover');
                $fileName = Str::uuid() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('profile', $fileName, 'public');
                $validated['profile_cover'] = $filePath;
                // Log::info("Profile Cover saved at: " . $filePath);
            }

            if ($request->hasFile('scan_ktp')) {
                $file = $request->file('scan_ktp');
                $fileName = Str::uuid() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('profile', $fileName, 'public');
                $validated['scan_ktp'] = $filePath;

                // Log::info("KTP saved at: " . $filePath);
            }

            // if (!$request->has('organization_name')) {
            //     $validated['organization_name'] = null;
            //     $validated['jabatan'] = null;
            // }

            // Log::info("Validated Profile : ", $validated);
            $user->update($validated);

            $user->refresh();

            // Hapus cache setelah update
            $cacheKey = "user_profile_{$user->user_id}";

            Cache::forget($cacheKey);

            $userData = [
                ...$user->toArray(),
                'profile_picture' => $user->profile_picture ? asset(Storage::url($user->profile_picture)) : null,
                'profile_cover' => $user->profile_cover ? asset(Storage::url($user->profile_cover)) : null,
                'scan_ktp' => $user->scan_ktp ? asset(Storage::url($user->scan_ktp)) : null,
            ];
    
            Cache::put($cacheKey, $userData, now()->addMinutes(10));

            return response()->json([
                'message' => 'Success',
                'user' => $user,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function storeUserProfile(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email',
                'password' => 'required|string',
                'address' => 'nullable|string',
                'phone_number' => 'nullable|string',
                'nik' => 'nullable|string',
                'birth_date' => 'nullable|date',
                'job' => 'nullable|string',
                'social_media' => 'nullable|json',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();

            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                $fileName = Str::uuid() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('profile', $fileName, 'public');
                $validated['profile_picture'] = $filePath;
            }

            $user = User::create($validated);

            return response()->json([
                'message' => 'Success',
                'user' => $user,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed',
                'error' => $th->getMessage(),
            ], 500);
        }
    }


    public function getUserList() {
        //check if user login is admin

        $users = User::all();

        $users = $users->map(function ($user) {
            return [
                'user_id' => $user->user_id,
                'user_full_name' => $user->full_name ? $user->full_name : $user->userFullName,
                'user_avatar' => $user->profile_picture ? asset(Storage::url($user->profile_picture)) : null,
                'user_created' => $user->userCreated,
                'user_status' => $user->status,
                'user_project' => $user->projects()->count(),
                'user_contribution' => $user->donations()->count() + $user->volunteerInvolvements()->count(),
            ];
        });


        return response()->json([
            'message' => 'Users berhasil diambil',
            'users' => $users
        ], 200);
    }

    public function userVerify(User $user) {
        // Cek apakah user sudah diverifikasi sebelumnya
        if ($user->status === UserStatusEnum::VERIFIED->value) {
            return response()->json([
                'message' => 'User sudah diverifikasi sebelumnya'
            ], 400); // Bad Request
        }
    
        // Pastikan semua field penting terisi sebelum verifikasi
        $requiredFields = ['full_name','first_name', 'last_name',
         'email', 'phone_number', 'nik', 'birth_date', 'scan_ktp', 
         'profile_picture', 'address', 'social_media', 'email_verified_at'];
    
        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                return response()->json([
                    'message' => 'Verifikasi gagal! Data pengguna belum lengkap.',
                    'missing_field' => $field
                ], 422); // Unprocessable Entity
            }
        }
    
        // Lanjutkan verifikasi
        // $user->status_before = $user->status;
        $user->status = UserStatusEnum::VERIFIED->value;
        $user->user_verified = true;
        $user->status_date = now();

        $user->save();

        $user->syncRoles('verified');

        $user->increment('total_points', 20);

        $cacheKey = "user_profile_{$user->user_id}";

        Cache::forget($cacheKey);

        $notification = Notification::create([
            'notification_title' => 'Verifikasi Akun!',
            'notification_icon' => 'ui uil-user',
            'notification_text' => 'Selamat ' . $user->first_name . ', Akun Kamu Berhasil Diverifikasi',
            'notification_url' => env("FRONTEND_URL") . "/profile/setting",
            'target_id' => $user->user_id
        ]);

        broadcast(new NotificationEvent($user->user_id, $notification))->via('pusher');

    
        return response()->json([
            'message' => 'User berhasil diverifikasi'
        ], 200); // OK
    }
    

    public function userReport(Request $request, User $sender) {
        try {
            $user = Auth::guard('api')->user();
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }
    
            $validator = Validator::make($request->all(), [
                'reported_case' => 'required|string',
                'reported_comment' => 'nullable|string',
                'reported_segment' => 'required|string',
                'reported_image' => [
                    'nullable', 
                    'file', 
                    'mimes:jpeg,png,jpg,gif,webp', 
                    'max:2048'
                ],
                'project_id' => 'nullable|uuid'
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }
    
            $validated = $validator->validated();
            $validated['reporter_id'] = $user->user_id;
            $validated['reported_id'] = $sender->user_id;

            // if ($request->has('project_id')) {}
    
            // Handle Upload Gambar
            if ($request->hasFile('reported_image')) {
                $file = $request->file('reported_image');
                $validated['reported_path_file'] = $this->getPathFile($file, 'uploads/report/images', 'public');
            }
    
            // Simpan laporan
            $reportCase = ReportCase::create($validated);
    
            // Update status user yang dilaporkan
            // $sender->status_before = $sender->status;
            if ($sender->status !== UserStatusEnum::REPORTED->value) {
                $sender->decrement('total_points', 10);
            }

            $sender->status = UserStatusEnum::REPORTED->value;
            $user->status_date = now();
            
            $sender->save();

            $sender->syncRoles('reported');

            $cacheKey = "user_profile_{$sender->user_id}";

            Cache::forget($cacheKey);
    
            return response()->json([
                'message' => 'Laporan berhasil dikirim',
                'report' => $reportCase
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error dalam menyimpan laporan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function suspendedUser(Request $request, User $user) {
        $newStatus = $request->status;
        $suspendedReason = $request->suspended_reason; // Ambil alasan suspend
        $suspendDuration = (int) $request->suspend_duration; // Ambil durasi suspend dalam hari
        $oldStatus = $user->status;
    
        // Jika status berubah menjadi "suspended"
        if ($newStatus == UserStatusEnum::SUSPENDED->value) {
            // Log::info('status', [$newStatus, UserStatusEnum::SUSPENDED]);
    
            // Tentukan durasi default jika tidak diinput
            if (!$suspendDuration || $suspendDuration <= 0) {
                // Log::info('suspended duration : ', [$suspendDuration]);
                $suspendDuration = 7; // Default suspend 7 hari jika tidak diisi
            }
    
            // Hitung tanggal kapan user bisa kembali aktif
            $suspendedUntil = Carbon::now()->addDays($suspendDuration);

            // Log::info('suspended duration : ', [$suspendedUntil]);

    
            // Simpan status, alasan suspend, dan tanggal suspend hingga user aktif kembali
            $user->status = UserStatusEnum::SUSPENDED->value;
            $user->suspended_reason = $suspendedReason;
            $user->suspended_date = $suspendedUntil; // Tanggal kapan user bisa aktif kembali
        } else {
            // Log::info('kenapa disini ? ', [$newStatus == UserStatusEnum::SUSPENDED]);
            // Log::info('status', [$newStatus, UserStatusEnum::SUSPENDED->value]);

            // Jika status berubah dari suspended ke status lain, hapus data suspensi
            // $user->status_before = $user->status;
            $user->status = $newStatus;
            $user->status_date = now();
            $user->suspended_reason = null;
            $user->suspended_date = null;
        }

        $notification = Notification::create([
            'notification_title' => 'Penangguhan Akun!',
            'notification_icon' => 'ui uil-user',
            'notification_text' => "Akun anda telah dilakukan penangguhan selama  { $suspendedUntil} hari",
            'notification_url' => env("FRONTEND_URL") . "/profile/setting",
            'target_id' => $user->user_id
        ]);
        
        $user->syncRoles('suspended');

        $user->save();

        $cacheKey = "user_profile_{$user->user_id}";

        Cache::forget($cacheKey);

        return response()->json([
            'message' => 'Status user berhasil diperbarui.',
            'user' => $user
        ], 200);
    }

    public function getReportedCase(User $user) {
        // dd($user->reportedCases);
        $reportedCases = $user->reportedCases;
        
        $reportedCases = $reportedCases->map(function ($report) {
            return [
                'reported_case_id' => $report->report_case_id,
                'reporter_case_id' => $report->reporter && $report->reporter->user_id ? $report->reporter->user_id : null,
                'reporter_case_name' => $report->reporter && $report->reporter->full_name ? $report->reporter->full_name : null,
                'reported_comment' => $report->reported_comment,
                'reported_case' => $report->reported_case,
                'reported_segment' => $report->reported_segment,
                // 'reported_image' => $this->getUrlFile($report->reported_path_file),
                'reported_date' => $report->dateFormat,
                'reported_check' => $report->checked,
                'reported_created' => $report->created_at
            ];
        })->sortByDesc('reported_created')->values();

        if (!$reportedCases) {
            return response()->json([
                'message' => 'Report Case Tidak Ditemukan',
                'report_cases' => []
            ], 404);
        } 

        return response()->json([
            'message' => 'Detail Report Berhasil Diambil',
            'report_cases' => $reportedCases
        ], 200);

    }

    public function getReportDetail(User $user, ReportCase $reportCase) {
        // $reportDetail = ReportCase::where

            $reportCaseDetail =  [
                'reported_case_id' => $reportCase->report_case_id,
                'reporter_case_id' => $reportCase->reporter->user_id,
                'reporter_case_name' => $reportCase->reporter->full_name,
                'reported_comment' => $reportCase->reported_comment,
                'reported_case' => $reportCase->reported_case,
                'reported_segment' => $reportCase->reported_segment,
                'reported_image' => $this->getUrlFile($reportCase->reported_path_file),
                'reported_date' => $reportCase->dateFormat,
                'project_id' => $reportCase->project?->project_id,
                'project_title' => $reportCase->project?->project_title
            ];
        

            if (!$reportCaseDetail) {
                return response()->json([
                    'message' => 'Detail Report Tidak Ditemukan',
                    'report_detail' => []
                ], 404);
            } 

            return response()->json([
                'message' => 'Detail Report Berhasil Diambil',
                'report_detail' => $reportCaseDetail
            ], 200);
    }

    public function updateReportCase(Request $request, User $user) {
        try {
            $validator = Validator::make($request->all(), [
                '*.report_case_id' => 'required|uuid',
                '*.checked' => 'required|boolean',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
    
            $validated = $validator->validated();
            
            $reportCaseUpdateList = [];
            foreach ($validated as $data) {
                $reportCaseUpdate = $user->reportedCases()->where('report_case_id', $data['report_case_id'])->first();
               
                if ($reportCaseUpdate) {
                    $reportCaseUpdate->update([
                        'checked' => $data['checked'],
                    ]);
                }

                // dd($projectEvaluation);

                $reportCaseUpdateList [] = $reportCaseUpdate;
            }
    
            return response()->json(['message' => 'Report updated successfully', 'report_cases' => $reportCaseUpdateList], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteUser (User $user) {
        //Check if admin is login

        try {
            if ($user->profile_picture ) {
                $this->deleteFile($user->profile_picture);
            }

            if ($user->scan_ktp ) {
                $this->deleteFile($user->scan_ktp);
            }

            if ($user->profile_cover ) {
                $this->deleteFile($user->profile_cover);
            }

            $user->delete();

            return response()->json([
                'message' => 'Project berhasil dihapus'
            ], 200); // Gunakan 200 OK atau 204 No Content jika tanpa pesan
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus project',
                'error' => $e->getMessage()
            ], 500); // Internal Server Error jika terjadi kesalahan
        }

    }

    public function getAllUsers(Request $request) {
        $querySort = $request->input('query_sort', []);
        $limit = $request->input('limit', 10);
        $search = $request->input('search', '');
    
        // Query awal tanpa sorting total_project dan total_contribution
        $query = User::with(['donations', 'volunteerInvolvements'])
            ->when($search, function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%$search%")
                      ->orWhere('last_name', 'LIKE', "%$search%");
            });
    
        // Terapkan Sorting HANYA untuk kolom yang ada di database
        if (!empty($querySort)) {
            foreach ($querySort as $column) {
                if (!in_array($column, ['total_project', 'total_contribution'])) { // Hindari sorting kolom yang dihitung manual
                    $query->orderBy($column, 'asc');
                }
            }
        }
    
        // Lakukan Pagination tanpa sorting total_project
        $accounts = $query->paginate($limit);
    
        // Ubah ke Collection agar bisa diproses lebih lanjut
        $accountList = collect($accounts->items())->map(function ($user) {
            $total_contribution = $user->donations()->count() + $user->volunteerInvolvements()->count();
            $total_project = $user->projects()->count();
    
            return [
                'user_id' => $user->user_id,
                'user_full_name' => $user->full_name ? $user->full_name : $user->userFullName,
                'user_avatar' => $user->profile_picture ? asset(Storage::url($user->profile_picture)) : null,
                'user_created' => $user->userCreated,
                'user_status' => $user->status,
                'user_project' => $total_project,
                'user_contribution' => $total_contribution,
            ];
        });
    
        // 🔥 Lakukan Sorting di Collection, bukan di Query
        if (in_array('total_project', $querySort)) {
            $accountList = $accountList->sortByDesc('user_project')->values();
        }
    
        if (in_array('total_contribution', $querySort)) {
            $accountList = $accountList->sortByDesc('user_contribution')->values();
        }
    
        return response()->json([
            'message' => 'Projects Successfully Retrieved',
            'accounts' => $accountList,
            'accounts_count' => $accounts->total(),
            'current_page' => $accounts->currentPage(),
            'last_page' => $accounts->lastPage(),
            'per_page' => $accounts->perPage(),
            'has_more_pages' => $accounts->hasMorePages(),
        ], 200);
    }
    
    

    public function getUserStatistic() {
        $user_id = Auth('api')->check() ? Auth('api')->user()->user_id : null;
    
        // Hitung jumlah berdasarkan status langsung dengan count()
        $totalAccounts = User::count();
        $activeAccounts = User::where('status', UserStatusEnum::ACTIVE->value)->count();
        $verifiedAccounts = User::where('status', UserStatusEnum::VERIFIED->value)->count();
        $suspendedAcounts = User::where('status', UserStatusEnum::SUSPENDED->value)->count();
        $reportedAccounts = User::where('status', UserStatusEnum::REPORTED->value)->count();
    
        // Ambil statistik sebelumnya untuk perbandingan
        list($currentAccountCount, $lastAccountCount) = $this->getAmountStatistic(User::query(), 'count', null);
        list($currentActiveCount, $lastActiveCount) = $this->getAmountStatistic(User::where('status', UserStatusEnum::ACTIVE->value), 'count', null);
        list($currentReportedCount, $lastReportedCount) = $this->getAmountStatistic(User::where('status', UserStatusEnum::REPORTED->value), 'count', null);
        list($lastVerifiedCount, $lastVerifiedCount) = $this->getAmountStatistic(User::where('status', UserStatusEnum::VERIFIED->value), 'count', null);
        list($currentSuspendedCount, $lastSuspendedCount) = $this->getAmountStatistic(User::where('status', UserStatusEnum::SUSPENDED->value), 'count', null);
    
        // Hitung persentase perubahan
        $accountPercentage = $this->getPercentage($lastAccountCount, $currentAccountCount);
        $activePercentage = $this->getPercentage($lastActiveCount, $currentActiveCount);
        $reportedPercentage = $this->getPercentage($lastReportedCount, $currentReportedCount);
        $verifiedPercentage = $this->getPercentage($lastVerifiedCount, $lastVerifiedCount);
        $suspendedPercentage = $this->getPercentage($lastSuspendedCount, $currentSuspendedCount);
    
        // Data statistik
        $statistics = [
            [
                "statistic_id" => Str::uuid(),
                "statistic_name" => "Total Akun",
                "statistic_number" => $totalAccounts,
                "statistic_percentage" => $accountPercentage,
                "statistic_status" => $accountPercentage > 0 ? 'up' : 'down',
                "statistic_icon" => "uil uil-users-alt",
            ],
            [
                "statistic_id" => Str::uuid(),
                "statistic_name" => "Active",
                "statistic_number" => $activeAccounts,
                "statistic_percentage" => $activePercentage,
                "statistic_status" => $activePercentage > 0 ? 'up' : 'down',
                "statistic_icon" => "uil uil-user-plus",
            ],
            [
                "statistic_id" => Str::uuid(),
                "statistic_name" => "Active - Reported",
                "statistic_number" => $reportedAccounts,
                "statistic_percentage" => $reportedPercentage,
                "statistic_status" => $reportedPercentage > 0 ? 'up' : 'down',
                "statistic_icon" => "uil uil-user-exclamation",
            ],
            [
                "statistic_id" => Str::uuid(),
                "statistic_name" => "Verified",
                "statistic_number" => $verifiedAccounts,
                "statistic_percentage" => $verifiedPercentage,
                "statistic_status" => $verifiedPercentage > 0 ? 'up' : 'down',
                "statistic_icon" => "uil uil-user-check",
            ],
            [
                "statistic_id" => Str::uuid(),
                "statistic_name" => "Suspended",
                "statistic_number" => $suspendedAcounts,
                "statistic_percentage" => $suspendedPercentage,
                "statistic_status" => $suspendedPercentage > 0 ? 'up' : 'down',
                "statistic_icon" => "uil uil-user-times",
            ],
        ];
    
        return response()->json([
            "success" => true,
            "message" => "Statistics retrieved successfully",
            "project_statistic" => $statistics,
        ]);
    }
    

    public function getProgressPercentageMonth($donation_percentage, $volunteer_percentage) {
        return (($donation_percentage) + ($volunteer_percentage)) / 2;
    }

    public function getPercentage($last_amount, $current_amount) {
        // Hitung percentage dengan pengecekan pembagi (lastDonationCount atau lastVolunteerCount) agar tidak terjadi division by zero
        if ($last_amount == 0) {
            // Jika bulan lalu tidak ada data, misal: jika data bulan ini juga 0, maka 0%, jika ada, bisa dianggap 100% kenaikan.
            $percentage = ($current_amount > 0) ? 100 : 0;
        } else {
            $percentage = (($current_amount - $last_amount) / $last_amount) * 100;
        }

        return $percentage;
    }

    public function getAmountStatistic($model, String $type, $field) {


        if ($type === 'sum') {        
            $currentAmount = $model
                ->whereMonth('created_at', $this->currentMonth->month)
                ->whereYear('created_at', $this->currentMonth->year)
                ->sum($field);



            $lastAmount = $model
                ->whereMonth('created_at', $this->lastMonth->month)
                ->whereYear('created_at', $this->lastMonth->year)
                ->sum($field);
                
        } elseif ($type === 'count') {
            $currentAmount = $model
                ->whereMonth('created_at', $this->currentMonth->month)
                ->whereYear('created_at', $this->currentMonth->year)
                ->count();

            $lastAmount = $model
                ->whereMonth('created_at', $this->lastMonth->month)
                ->whereYear('created_at', $this->lastMonth->year)
                ->count();
        }


        return [$currentAmount, $lastAmount];
    }
    
    public function getNotificationUserList(User $user) {
        $userAuth = auth('api')->user();
    
        if ($user->user_id != $userAuth->user_id) {
            return response()->json([
                'message' => 'Anda Tidak Punya Akses ke notifikasi ini'
            ], 403);
        }
        
        // Ambil daftar grup yang diikuti user
        $userGroupId = $user->groupChats()->pluck('group_chats.group_chat_id')->toArray();

        // Ambil notifikasi user atau grupnya
        $notifications = Notification::where(function ($query) use ($user, $userGroupId) {
            $query->where('target_id', $user->user_id)
                  ->orWhereIn('target_id', $userGroupId);
            })
            ->orderByRaw('checked ASC') // Notifikasi yang belum diklik di atas
            ->orderBy('created_at', 'desc') // Notifikasi terbaru tetap di atas
            ->orderBy('updated_at', 'desc') // Jika ada pembaruan, tetap terurut dengan baik
            ->get();
    

        // Hitung jumlah notifikasi yang belum dibaca
        $notificationCount = Notification::where(function ($query) use ($user, $userGroupId) {
                $query->where('target_id', $user->user_id)
                ->orWhereIn('target_id', $userGroupId);
            })
            ->where('checked', false) // Hanya yang belum dicek
            ->count(); // Hitung jumlahnya

    
        // Periksa apakah user memiliki notifikasi
        if ($notifications->isEmpty()) {
            return response()->json([
                'message' => 'User does not have any notifications',
                'notifications' => []
            ], 404);
        }

        $notifications->transform(function ($notification) {
            $notification->formatted_time = app(Notification::class)->formatted_date;

            return $notification;
        });
    
        return response()->json([
            'message' => 'Notification Successfully Retrieved',
            'notifications' => $notifications,
            'notification_count' =>$notificationCount
        ], 200);
    }

    public function updateNotification(User $user, Notification $notification) {
        // Pastikan notifikasi hanya bisa diupdate oleh pemiliknya
        if ($notification->target_id !== $user->user_id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk memperbarui notifikasi ini'
            ], 403);
        }
    
        $notification->checked = true;
        $notification->save();
    
        return response()->json([
            'message' => 'Notifikasi berhasil diperbarui'
        ], 201);
    }
    
    
    
}
