<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Agenda;
use App\Models\Project;
use App\Models\GroupChat;
use App\Models\ProjectTag;
use App\Models\ReportCase;
use App\Models\SocialMedia;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Models\ProjectShare;
use App\Traits\HasFileTrait;
use Illuminate\Http\Request;
use App\Models\ProjectDetail;
use App\Models\ProjectComment;
use App\Models\ProjectEvaluasi;
use App\Models\ProjectTimeline;
use App\Traits\HasFiltersTrait;
use App\Enums\ProjectStatusEnum;
use App\Models\MessageGroupChat;
use App\Events\NotificationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
use App\Models\ProjectCreatorInformation;
use Illuminate\Support\Facades\Validator;
use App\Models\ProjectBeneficialInformation;


class ProjectController extends Controller
{
    use HasFileTrait, HasFiltersTrait;

    protected $currentMonth;
    protected $lastMonth;

    public function __construct()
    {
        $this->currentMonth = Carbon::now('UTC');
        $this->lastMonth    = Carbon::now('UTC')->subMonth();
    }
    
    public function getProjectsList(Request $request) {
        $filters = $request->only(['status', 'sort', 'category', 'search']);
        $limit = $request->input('limit', '');
        // return $filters;

        $user = Auth::guard('api')->user();

        // $user = User::where('user_id', $user_id)->first();

        $projects = Project::Filter($filters)->with(['projectDonations', 'projectVolunteers'])->where('creator_id', $user->user_id)->get();

        // return $projects[0]->volunteerDetails;

        $projectsList = $projects->map(function ($project) {

            list($target_amount, $progress_amount, $progress_percentage, $_) = $this->getAmount($project);
          

            return [
                'project_id' => $project->project_id,
                'project_title' => $project->project_title,
                'project_description' => substr($project->project_description, 0, 150),
                'project_address' => $project->project_address,
                'project_start_date' => $project->project_start_date,
                'project_end_date' => $project->project_end_date,
                'project_target_amount' => $target_amount,
                // 'project_image' => $project->project_image_path ? asset(Storage::url($project->project_image_path)) : null,
                'project_image' => $this->getUrlFile($project->project_image_path),
                'project_status' => $project->project_status,
                'project_category' => $project->project_category,
                'project_progress_amount' => $progress_amount,
                'project_progress_percentage' => $progress_percentage > 100 ? 100 : $progress_percentage
            ];
        });
        

        // return $projectsDetails;
        
        

        return response()->json([
            'message' => 'Project Succesfully Retrieved',
            'user' => $user,
            'projects' => $projectsList,
            'projects_count' => $projects->count()
        ], 200);
    }

    public function getPublicProjectList(Request $request) {
        $filters = $request->only(['status', 'category', 'search', 'sort']);
        $limit = (int) $request->input('limit', 4);
        $kodeProvinsi = $request->input('kode_provinsi'); // ID Provinsi
        $kodeKabupaten = $request->input('kode_kabupaten');
        $shuffleFilter = $request->boolean('shuffle'); // Ambil input sebagai boolean
        $bulan = $request->input('bulan') ?  $request->input('bulan') : null; // Ambil bulan sekarang
        $tahun = date('Y'); // Ambil tahun sekarang

    
        $projects = Project::Filter($filters)
                        ->with(['projectDonations', 'projectVolunteers', 'projectTags.tag'])
                        ->when(!empty($bulan), function ($query) use ($bulan, $tahun) {
                            $query->whereYear('project_start_date', $tahun)
                                  ->whereYear('project_end_date', $tahun)
                                  ->whereMonth('project_start_date', '<=', $bulan)
                                  ->whereMonth('project_end_date', '>=', $bulan);
                        })
                        ->when($kodeProvinsi, function ($query) use ($kodeProvinsi) {
                            $query->whereHas('desa.kecamatan.kabupaten.provinsi', function ($q) use ($kodeProvinsi) {
                                $q->where('kode_provinsi', $kodeProvinsi);
                            });
                        })
                        ->when($kodeKabupaten, function ($query) use ($kodeKabupaten) {
                            $query->whereHas('desa.kecamatan.kabupaten', function ($q) use ($kodeKabupaten) {
                                $q->where('kode_kabupaten', $kodeKabupaten);
                            });
                        })->paginate($limit); // Ambil lebih banyak data dulu
                    
        $shuffledProjects = $shuffleFilter ? $projects->shuffle()->take($limit) : $projects; // Acak jika perlu
        // Konversi hasil untuk JSON response
        $projectsList = $shuffledProjects->map(function ($project) {
            list($target_amount, $progress_amount, $progress_percentage, $_) = $this->getAmount($project);

            return [
                'project_id' => $project->project_id,
                'project_title' => $project->project_title,
                'project_description' => substr($project->project_description, 0, 150),
                'project_address' => $project->project_address,
                'project_start_date' => $project->project_start_date,
                'project_end_date' => $project->project_end_date,
                'project_target_amount' => $target_amount,
                // 'project_image' => $project->project_image_path ? asset(Storage::url($project->project_image_path)) : null,
                'project_image' => $this->getUrlFile($project->project_image_path),
                'project_status' => $project->project_status,
                'project_category' => $project->project_category,
                'project_progress_amount' => $progress_amount,
                'project_progress_percentage' => $progress_percentage > 100 ? 100 : $progress_percentage,
                'project_provinsi' => optional(optional($project->desa)->kecamatan)->kabupaten
                    ? optional(optional($project->desa)->kecamatan->kabupaten)->provinsi->nama_provinsi 
                    : null,
                'project_tags' => $project->projectTags->map(fn($pt) => [
                    'tag_id' => $pt->tag->tag_id ?? null,
                    'tag_name' => $pt->tag->tag_name ?? null
                ]),
            ];
        });

        // Tambahkan pagination agar URL pagination tetap bisa digunakan
        return response()->json([
            'message' => 'Projects Successfully Retrieved',
            'projects' => $projectsList,
            'limit' => $limit,
            'pagination' => [
                'total' => $projects->total(),
                'per_page' => $limit,
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'next_page_url' => $projects->nextPageUrl(),
                'prev_page_url' => $projects->previousPageUrl(),
            ],
        ], 200);

    }
    

    public function getProjectDetailId(Project $project) {
        $cacheKey = "project_detail_{$project->project_id}";

        $user = Auth::guard('api')->user()->user_id == $project->creator_id ? null : Auth::guard('api')->user();

        // Cek apakah data sudah ada di cache
        $projectDetail = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($project, $user) { // <-- Tambahkan $user di sini
            $project = Project::with(['projectDonations', 'projectVolunteers', 'projectWithdrawal'])
                                ->where('project_id', $project->project_id)
                                ->get();

            return $project->map(function ($p) use ($user) { // <-- Pastikan juga ada $user di dalam closure ini
                list($target_amount, $progress_amount, $progress_percentage, $progress_donatur) = $this->getAmount($p);
                $start_date = date_create($p->project_start_date);
                $end_date = date_create($p->project_end_date);

                return [
                    'project_id' => $p->project_id,
                    'project_title' => $p->project_title,
                    'project_description' => $p->project_description,
                    'project_start_date' => $p->startDateFormat,
                    'project_end_date' => $p->endDateFormat,
                    'project_start_date_full' => $p->startDateFormatFull,
                    'project_end_date_full' => $p->endDateFormatFull,
                    'project_target_amount' => $target_amount,
                    'project_donatur_amount' => $progress_donatur,
                    'project_diff_day' => date_diff($end_date, $start_date)->days,
                    'project_status' => $p->project_status,
                    'project_image' => $this->getUrlFile($p->project_image_path),
                    'project_creator_name' => $p->user->userFullName,
                    'project_progress_amount' => $progress_amount,
                    'project_progress_percentage' => $progress_percentage > 100 ? 100 : $progress_percentage,
                    'project_tags' => $p->projectTags->map(fn($pt) => [
                        'tag_id' => $pt->tag->tag_id ?? null,
                        'tag_name' => $pt->tag->tag_name ?? null
                    ]),
                    'project_address' => $p->project_address,
                    'project_kode_desa' => $p->kode_desa,
                    'project_kode_kecamatan' => $p->desa->kecamatan->kode_kecamatan,
                    'project_kode_kabupaten' => $p->desa->kecamatan->kabupaten->kode_kabupaten,
                    'project_kode_provinsi' => $p->desa->kecamatan->kabupaten->provinsi->kode_provinsi,
                    'project_latitude' => $p->latitude,
                    'project_longitude' => $p->longitude,
                    'project_category' => $p->project_category,
                    'project_criteria' => $p->project_criteria,
                    'project_role' => $p->project_role,
                    'user_organization_name'=> $p->user->organization_name,
                    'user_badge' => $p->user->badge,
                    'withdrawal_status' => $p->projectWithdrawal?->status_penarikan, 
                    'withdrawal_bukti_transfer' => $this->getUrlFile($p->projectWithdrawal?->bukti_transfer),
                    'user_avatar' => $this->getUrlFile($p->user?->profile_picture),
                    'user_participation' => $p->projectVolunteers()->where('volunteer_id', $user?->user_id)->exists()
                ];
            });
        });

        return response()->json(["project_details" => $projectDetail], 200);

    }
   

    public function getAmount(Project $p) {
        if ($p->project_category == 'donation') {
            $target_amount = $p->project_target_amount;
            $progress_amount = round($p->projectDonations->sum('donation_amount'),2);
            $progress_donatur = $p->projectDonations->count();
            $progress_percentage = round(($progress_amount / $target_amount) * 100);
        } else {
            $target_amount = $p->project_target_amount;
            $progress_amount = $p->projectVolunteers->count();
            $progress_donatur = $p->projectVolunteers->count();
            $progress_percentage = round(($progress_amount / $target_amount) * 100);
        }  


        return [$target_amount, $progress_amount, $progress_percentage, $progress_donatur];
    }

    public function updateProjectDetail(Request $request, Project $project) {
        $cacheKey = "project_detail_{$project->project_id}";
        
        $projectId = $project->project_id;
        try {
            if ($request->has('project_criteria') && is_string($request->project_criteria)) {
                $request->merge([
                    'project_criteria' => json_decode($request->project_criteria, true)
                ]);
            }

            if ($request->has('project_role') && is_string($request->project_role)) {
                $request->merge([
                    'project_role' => json_decode($request->project_role, true)
                ]);
            }
            // Validasi data
            $validator = Validator::make($request->all(), [
                'project_title' => 'nullable|string',
                'project_description' => 'nullable|string',
                'project_start_date' => 'nullable|date_format:Y-m-d H:i:s',
                'project_end_date' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:project_start_date',
                'project_tags' => 'nullable|array',
                'project_tags.*.tagId' => 'nullable|string',
                'project_tags.*.tagName' => 'nullable|string',
                'project_image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
                'project_criteria' => 'nullable|array',
                'project_criteria.*.key' => 'required|string',
                'project_criteria.*.value' => 'required|string',
                'project_criteria.*.role' => 'required|string',
                'project_role' => 'nullable|array',
                'project_role.*.key' => 'required|string',
                'project_role.*.value' => 'required|string',
                
                
            ]);

            // dd($request->input('project_tags'));
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 400);
            }
    
            $validated = $validator->validated();

    
            // Jika ada gambar yang diupload
            if ($request->hasFile('project_image')) {
                $image = $request->file('project_image');
                $imagePath = $this->getPathFile($image, 'project/project_image');
                // $imageName =date('dmY') . '_' . $image->getClientOriginalName();
                // $imagePath = $image->storeAs('project_images', $imageName, 'public');
                $validated['project_image_path'] = $imagePath;
                
            }

            // Konversi array ke JSON sebelum disimpan ke database
            if (isset($validated['project_criteria']) && is_array($validated['project_criteria'])) {
                $validated['project_criteria'] = json_encode($validated['project_criteria']);
            }
            
            
            // Konversi array ke JSON sebelum disimpan ke database
            if (isset($validated['project_role']) && is_array($validated['project_role'])) {
                $validated['project_role'] = json_encode($validated['project_role']);
            }
            // Update data project
            $project->update($validated);
    
            // Hapus project_tags lama lalu tambahkan yang baru
            if ($request->input('project_tags') !== null) {
                $project->projectTags()->delete();
                foreach ($validated['project_tags'] as $tag) {
                    ProjectTag::create([
                        'project_id' => $project->project_id,
                        'tag_id' => $tag['tagId'],
                    ]);
                }
            }

 
            // $project = $project->map(function ($p) {
            //     list($target_amount, $progress_amount, $progress_percentage, $progress_donatur) = $this->getAmount($p);
            //     $start_date = date_create($p->start_date);
            //     $end_date = date_create($p->end_date);

            //     return [
            //         'project_id' => $p->project_id,
            //         'project_title' => $p->project_title,
            //         'project_description' => $p->project_description,
            //         'project_start_date' => $p->startDateFormat,
            //         'project_end_date' => $p->endDateFormat,
            //         'project_start_date_full' => $p->startDateFormatFull,
            //         'project_end_date_full' => $p->endDateFormatFull,
            //         'project_target_amount' => $target_amount,
            //         'project_donatur_amount' => $progress_donatur,
            //         'project_diff_day' => date_diff($end_date, $start_date)->days,
            //         'project_status' => $p->status,
            //         'project_image' => null,
            //         'project_creator_name' => $p->user->fullName,
            //         'project_progress_amount' => $progress_amount,
            //         'project_progress_percentage' => $progress_percentage,
            //         'project_tags' => $p->projectTags->map(fn($pt) => [
            //             'tag_id' => $pt->tag->tag_id ?? null,
            //             'tag_name' => $pt->tag->tag_name ?? null
            //         ]),
            //         'project_address' => $p->project_addres,
            //         'project_kode_desa' => $p->kode_desa,
            //         'project_latitude' => $p->latitude,
            //         'project_longitude' => $p->longitude,
            //         'project_category' => $p->project_category
                    
            //     ];
            // });

            Cache::forget($cacheKey);
    
            return response()->json([
                'message' => 'Project Detail Updated',
                'project_id' => $project->project_id,
                'project_detail' => $project
            ]);
    
        } catch(Exception $e) {
            return response()->json([
                'message' => 'Error',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    public function updateStatusProject (Request $request, Project $project) {
        //Cek apakah project adalah milik user 
        $newStatus = $request->only('status');
        $oldStatus = $project->project_status;


        $project->project_status = $newStatus['status'];

        $project->save();

        $notification = Notification::create([
            'notification_title' => 'Status Project Diperbarui!',
            'notification_icon' => 'ui uil-briefcase-alt',
            'notification_text' => 'Status project ' . $project->project_title . ' telah diperbarui menjadi ' . $project->project_status . ' Silakan tinjau perubahan status pada halaman project untuk informasi lebih lanjut.',
            'notification_url' => "/dashboard/project/{$project->project_id}",
            'target_id' => $project->creator_id
        ]);

        $cacheKey = "project_detail_{$project->project_id}";
        Cache::forget($cacheKey);

        broadcast(new NotificationEvent($project->creator_id, $notification))->via('pusher');

        return response()->json([
            'message' => 'Status In active berhasil diupdate',
            'project' => $project
        ], 200);
    }

    public function storeProjectDetail(Request $request)
    {

        $userAuth = Auth('api')->user();
        try {
            // Konversi JSON string ke array jika perlu
            if ($request->has('project_criteria') && is_string($request->project_criteria)) {
                $request->merge([
                    'project_criteria' => json_decode($request->project_criteria, true)
                ]);
            }

            if ($request->has('project_role') && is_string($request->project_role)) {
                $request->merge([
                    'project_role' => json_decode($request->project_role, true)
                ]);
            }
    
            $validator = Validator::make($request->all(), [
                'project_title' => 'required',
                'project_description' => 'required',
                'project_start_date' => 'required',
                'project_end_date' => 'required',
                'project_target_amount' => 'required',
                'project_category' => 'required',
                'project_address' => 'required',
                'project_tags' => 'required|array',
                'project_tags.*.tagId' => 'nullable|string',
                'project_criteria' => 'nullable|array',
                'project_criteria.*.key' => 'required|string',
                'project_criteria.*.value' => 'required|string',
                'project_criteria.*.role' => 'required|string',
                'project_role' => 'nullable|array',
                'project_role.*.key' => 'required|string',
                'project_role.*.value' => 'required|string',
                'latitude' => 'required',
                'longitude' => 'required',
                'kode_desa' => 'required',
                'project_image' => 'required|image|mimes:jpg,jpeg,png|max:4096',
                'project_group_chat' => 'required|string',
                'project_group_avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validasi Error',
                    'error' => $validator->errors()
                ], 400);
            }
    
            $validated = $validator->validated();
    
            // Konversi array ke JSON sebelum disimpan ke database
            if (isset($validated['project_criteria']) && is_array($validated['project_criteria'])) {
                $validated['project_criteria'] = json_encode($validated['project_criteria']);
            }

                // Konversi array ke JSON sebelum disimpan ke database
            if (isset($validated['project_role']) && is_array($validated['project_role'])) {
                $validated['project_role'] = json_encode($validated['project_role']);
            }
    
            if ($request->hasFile('project_image')) {
                $image = $request->file('project_image');
                $imagePath = $this->getPathFile($image, 'project/project_image');
                $validated['project_image_path'] = $imagePath;
            }

            $groupAvatarPath = null;
            if ($request->hasFile('project_group_avatar')) {
                $image = $request->file('project_group_avatar');
                $groupAvatarPath = $this->getPathFile($image, 'project/group_avatar');
            }
    
            $validated['creator_id'] = $userAuth->user_id;
            $validated['project_status'] = ProjectStatusEnum::PROPOSED->value;
            
            $user = User::where('user_id', $userAuth->user_id)->first();
            $project = Project::create($validated);
            $groupChat = GroupChat::create([
                'group_chat_name' => $request->project_group_chat,
                'initiator_user_id' => $user->user_id,
                'project_id' => $project->project_id,
                'group_avatar' => $groupAvatarPath
            ]);

       
            // Log::info('Group Chat ID String:', [(string) $groupChat->group_chat_id]);
            $user->groupChats()->attach((string) $groupChat->group_chat_id);

            $user->increment('total_points', 50);

            $messageGroupChat = MessageGroupChat::create([
                'sender_id' => $user->user_id,
                'group_chat_id' => $groupChat->group_chat_id,
                'group_chat_text' => 'Selamat Datang Semua Di Group Chat ' . $groupChat->group_chat_name,
                'media_path' => null
            ]);

            Agenda::create([
                'tanggal_agenda' => $validated['project_start_date'],
                'description' => "{$project->project_title} Dimulai",
                'is_completed' => false,
                'category' => 'campaign',
                'waktu_agenda' => '00:00',
                'user_id' =>  $userAuth->user_id
            ]);

            Agenda::create([
                'tanggal_agenda' => $validated['project_end_date'],
                'description' => "{$project->project_title} Selesai",
                'is_completed' => false,
                'category' => 'campaign',
                'waktu_agenda' => '00:00',
                'user_id' => $userAuth->user_id

            ]);
            
            // Hapus project_tags lama lalu tambahkan yang baru
            if ($request->input('project_tags') !== null) {
                foreach ($validated['project_tags'] as $tag) {
                    ProjectTag::create([
                        'project_id' => $project->project_id,
                        'tag_id' => $tag['tagId'],
                    ]);
                }
            }
    
            return response()->json([
                'message' => 'Project Berhasil Dibuat',
                'project_id' => $project->project_id
            ], 201);
    
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error Dalam Menyimpan',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    

    public function deleteProjectId(Project $project) {
        try {
            // Simpan informasi sebelum project dihapus
            $projectTitle = $project->project_title;
            $creatorId = $project->creator_id;
    
            // Hapus file jika ada lampiran
            if ($project->projectLampirans()->exists()) {
                $this->deleteFile($project->projectLampirans);
            }
    
            // Hapus project
            $project->delete();
    
            // Kirim notifikasi setelah project berhasil dihapus
            $notification = Notification::create([
                'notification_title' => 'Project Dihapus!',
                'notification_icon' => 'ui uil-briefcase-alt',
                'notification_text' => 'Project "' . $projectTitle . '" telah dihapus oleh pengguna ID ' . $creatorId . '.',
                'notification_url' => null,
                'target_id' => $creatorId
            ]);

            broadcast(new NotificationEvent($project->creator_id, $notification))->via('pusher');

    
            return response()->json([
                'message' => 'Project berhasil dihapus'
            ], 200); // Gunakan 204 jika tidak ada pesan yang dikembalikan
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus project',
                'error' => $e->getMessage()
            ], 500); // Gunakan 500 jika terjadi error
        }
    }
    

    public function storeProjectCreator(Request $request, Project $project) {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'creator_name'               => 'required|string|max:255',
                'creator_email'              => 'required|email|max:255',
                'creator_phone'              => 'required|string|max:20',
                'creator_type'               => 'required|string|max:100',
                'creator_organization_name'  => 'nullable|string|max:255',
                'creator_website'            => 'nullable|string|max:255',
                'creator_social_media'       => 'nullable|json', //masih salah
                'creator_identifier'         => 'nullable|string|max:100',
                'creator_file'               => 'nullable|file|mimes:pdf|max:10240',
            ]);
    
            // Jika validasi gagal
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
    
            // Default file path = null
            $filePath = null;
            $fileName = null;
    
            // Simpan file jika ada
            if ($request->hasFile('creator_file')) {
                $file = $request->file('creator_file');
                $fileName = strtolower($file->getClientOriginalName());
                $filePath = $this->getPathFile($file, 'project/creator_document');

            }
    
            // Simpan data dengan create()
            $creator_information = ProjectCreatorInformation::create([
                'project_id'               => $project->project_id,
                'creator_name'             => $request->creator_name,
                'creator_email'            => $request->creator_email,
                'creator_phone'            => $request->creator_phone,
                'creator_type'             => $request->creator_type,
                'creator_organization_name'=> $request->creator_organization_name,
                'creator_social_media'      => $request->creator_social_media,
                'creator_website'          => $request->creator_website,
                'creator_identifier'       => $request->creator_identifier,
                'creator_file_path'        => $filePath,
                'creator_file_name'        => $fileName,
            ]);
    
            return response()->json(['message' => 'Project creator information stored successfully', 'data' => $creator_information], 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function storeProjectBeneficial(Request $request, Project $project) {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'beneficiary_type'            => 'required|string|max:50',
                'beneficiary_name'            => 'nullable|string|max:255',
                'beneficiary_nik'             => 'nullable|string|max:20|regex:/^\d{16}$/', // Harus 16 digit angka
                'beneficiary_address'         => 'nullable|string',
                'beneficiary_phone'           => 'nullable|string|max:20|regex:/^\+?\d+$/', // Hanya angka dan tanda "+"
                'beneficiary_needs'           => 'nullable|string',
                'organization_name'           => 'nullable|string|max:255',
                'organization_reg_number'     => 'nullable|string|max:50',
                'organization_address'        => 'nullable|string',
                'organization_pic'            => 'nullable|string|max:255',
                'organization_phone'          => 'nullable|string|max:20|regex:/^\+?\d+$/',
                'beneficiary_relation'        => 'nullable|string|max:100',
                'beneficiary_relation_other'  => 'nullable|string|max:255',
                'beneficiary_file'            => 'nullable|file|mimes:pdf|max:10240',
            ]);
    
            // Jika validasi gagal
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
    
            // Default file path = null
            $filePath = null;
            $fileName = null;
    
            // Simpan file jika ada
            if ($request->hasFile('beneficiary_file')) {
                $file = $request->file('beneficiary_file');
                $fileName = strtolower($file->getClientOriginalName());
                $filePath = $this->getPathFile($file,'beneficiary_document');
            }
    
            // Simpan data dengan create()
            $beneficial_information = ProjectBeneficialInformation::create([
                'project_id'                  => $project->project_id,
                'beneficiary_type'            => $request->beneficiary_type,
                'beneficiary_name'            => $request->beneficiary_name,
                'beneficiary_nik'             => $request->beneficiary_nik,
                'beneficiary_address'         => $request->beneficiary_address,
                'beneficiary_phone'           => $request->beneficiary_phone,
                'beneficiary_needs'           => $request->beneficiary_needs,
                'organization_name'           => $request->organization_name,
                'organization_reg_number'     => $request->organization_reg_number,
                'organization_address'        => $request->organization_address,
                'organization_pic'            => $request->organization_pic,
                'organization_phone'          => $request->organization_phone,
                'beneficiary_relation'        => $request->beneficiary_relation,
                'beneficiary_relation_other'  => $request->beneficiary_relation_other,
                'beneficiary_file_path'       => $filePath,
                'beneficiary_file_name'       => $fileName,
            ]);
    
            return response()->json(['message' => 'Beneficial information stored successfully', 'data' => $beneficial_information], 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function getCommentProjectId(Project $project) {
        // ✅ Ambil komentar dengan sorting & include replies
        $comments = ProjectComment::with('replies.user') // Ambil balasan & user
            ->where('project_id', $project->project_id)
            ->whereNull('project_comment_parent_id') // Hanya ambil komentar utama
            ->orderBy('created_at', 'asc') // Urutkan berdasarkan waktu
            ->get();
    
        if ($comments->isEmpty()) {
            return response()->json([
                'message' => 'No comments found for this project.',
                'project_id' => $project->project_id,
                'comments' => []
            ], 404);
        }

        $comments = $comments->map(function ($comment) {
            return [
                'project_comment_id' => $comment->project_comment_id,
                'user_name' => $comment->user ? $comment->user->userFullName : 'Guest',
                'user_avatar' => $comment->user && $comment->user->profile_picture 
                    ? $this->getUrlFile($comment->user->profile_picture)
                    : $this->getUrlFile('profile/user-default.png'), // ✅ Gunakan avatar default jika tidak ada
                'user_badge' => $comment->user ? $comment->user->getBadge()->value : 'Guest',
                'user_badge_color' => $comment->user->getBadgeColor(),
                'project_comment_parent_id' => $comment->project_comment_parent_id,
                'comment' => $comment->comment,
                'send_date' => $comment->dateFormat,
                'send_time' => $comment->timeFormat
            ];
        });
        
    
        return response()->json([
            'message' => 'Project comments successfully retrieved.',
            'project_id' => $project->project_id,
            'comments' => $comments
        ], 200);
    }

    public function getPublicProjectDetailId(Project $project) {
        $user =  Auth('api')->user();
        $cacheKey = "project_detail_{$project->project_id}";


        $project = Project::with(['projectDonations', 'projectVolunteers'])
                            ->where('project_id',$project->project_id)
                            ->get();
                            
        // $projectDetail = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($project, $user) {
            $projectDetail = $project->map(function ($p) use ($user) {
                list($target_amount, $progress_amount, $progress_percentage, $progress_donatur) = $this->getAmount($p);
                $start_date = date_create($p->project_start_date);
                $end_date = date_create($p->project_end_date);
    
                return [
                    'project_id' => $p->project_id,
                    'project_title' => $p->project_title,
                    'project_description' => $p->project_description,
                    'project_start_date' => $p->startDateFormat,
                    'project_end_date' => $p->endDateFormat,
                    'project_start_date_full' => $p->startDateFormatFull,
                    'project_end_date_full' => $p->endDateFormatFull,
                    'project_target_amount' => $target_amount,
                    'project_donatur_amount' => $progress_donatur,
                    'project_diff_day' => date_diff($end_date, $start_date)->days,
                    'project_status' => $p->project_status,
                    // 'project_image' => $p->project_image_path ? asset(Storage::url($p->project_image_path)) : null,
                    'project_image' => $this->getUrlFile($p->project_image_path),
                    'project_creator_name' => $p->user->userFullName,
                    'project_progress_amount' => $progress_amount,
                    'project_progress_percentage' => $progress_percentage > 100 ? 100 : $progress_percentage,
                    'project_tags' => $p->projectTags->map(fn($pt) => [
                        'tag_id' => $pt->tag->tag_id ?? null,
                        'tag_name' => $pt->tag->tag_name ?? null
                    ]),
                    'project_address' => $p->project_address,
                    'project_kode_desa' => $p->kode_desa,
                    'project_latitude' => $p->latitude,
                    'project_longitude' => $p->longitude,
                    'project_category' => $p->project_category,
                    'project_criteria' =>$p->project_criteria,
                    // 'project_role' => $p->project_role,
                    'project_creator_id' => $p->user->user_id,
                    'user_organization_name'=> $p->user->organization_name,
                    'user_jabatan' => $p->user->jabatan,
                    'user_badge' => $p->user->getBadge()->value,
                    'user_badge_color' => $p->user->getBadgeColor(),
                    'user_avatar' => $this->getUrlFile($p->user->profile_picture),
                    'user_participation' => $p->projectVolunteers()->where('volunteer_id', $user->user_id)->exists(),
                    'project_roles' => collect(json_decode($p->project_role, true))->map(function ($roleData) use ($p) {
                        $role = $roleData['key'];
                        $jumlah = $roleData['value'];
                        $terisi = $p->projectVolunteers->where('role', $role)->count();

                        return [
                            'role' => $role,
                            'jumlah' => $jumlah,
                            'sisa' => max(0, $jumlah - $terisi),
                        ];
                    })->values(),

                ];
            });

            // return $projectDetail;
        // });
        


        return response()->json(["project_details" => $projectDetail], 200);
        

        // return response()->json([
        //     'message' => 'User does not belong to Project'
        // ]);
    }
    
    public function storeProjectComment(Request $request, Project $project) {
        
        try {

            $user_id = Auth('api')->check() ? Auth('api')->user()->user_id : null;
    
            $validator = Validator::make($request->all(), [
                'comment' => 'required|string',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validasi Error',
                    'error' => $validator->errors()
                ], 400);
            }
    
            $validated = $validator->validated();
    
            $validated['user_id'] = $user_id;
            $validated['project_id'] = $project->project_id;
    
            $comment = ProjectComment::create($validated);
    
            return response()->json([
                'message' => 'Project Berhasil Dibuat',
                'project_id' => $project->project_id,
                'comment' => $comment
            ], 201);
    
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error Dalam Menyimpan Comment',
                'error' => $e->getMessage()
            ], 400);
        }
    }

   

    public function getAllProjects(Request $request) {
        $querySort = $request->input('query_sort', []);
        $limit = $request->input('limit', 10);
        $search = $request->input('search', '');
    
        // Query awal
        $query =  Project::with(['projectDonations', 'projectVolunteers', 'projectWithdrawal'])
        ->when($search, function ($query) use ($search) {
            $query->where('project_title', 'LIKE', "%$search%")
                  ->orWhere('project_description', 'LIKE', "%$search%");
        });
    
        // Terapkan Sorting berdasarkan query_sort
        if (!empty($querySort)) {
            foreach ($querySort as $column) {
                if ($column !== 'project_progress_percentage') { // 🚀 Hindari Sorting dari Kolom PHP
                    $query->orderBy($column, 'asc');
                }
            }
        }

    
        // Lakukan Pagination setelah sorting diterapkan
        $projects = $query->paginate($limit);
    
        // Pastikan kita mengubah items() menjadi Collection agar bisa di-map()
        $projectsList = collect($projects->items())->map(function ($project) {
            list($target_amount, $progress_amount, $progress_percentage, $_) = $this->getAmount($project);
    
            return [
                'project_id' => $project->project_id,
                'project_title' => $project->project_title,
                'project_date_created' => $project->createdDate,
                'project_target_amount' => $target_amount,
                'project_status' => $project->project_status,
                'project_category' => $project->project_category,
                'project_progress_percentage' => $progress_percentage > 100 ? 100 : $progress_percentage,
                'withdrawal_status' => $project->projectWithdrawal && $project->projectWithdrawal->status_penarikan ? $project->projectWithdrawal->status_penarikan : null
            ];
        });

        // Lakukan sorting berdasarkan `project_progress_percentage`
            if (in_array('project_progress_percentage', $querySort)) {
                $projectsList = $projectsList->sortByDesc('project_progress_percentage')->values();
            }
    
        return response()->json([
            'message' => 'Projects Successfully Retrieved',
            'projects' => $projectsList,
            'projects_count' => $projects->total(),
            'current_page' => $projects->currentPage(),
            'last_page' => $projects->lastPage(),
            'per_page' => $projects->perPage(),
            'has_more_pages' => $projects->hasMorePages(),
        ], 200);
    }
    

    public function getStatistic() {
        $user_id = Auth('api')->check() ? Auth('api')->user()->user_id : null;
    
        // Hitung jumlah berdasarkan status langsung dengan count()
        $totalProjects = Project::count();
        $completedProjects = Project::where('project_status', ProjectStatusEnum::COMPLETED->value)->count();
        $inProgressProjects = Project::where('project_status', ProjectStatusEnum::IN_PROGRESS->value)->count();
        $needReviewProjects = Project::where('project_status', ProjectStatusEnum::IN_REVIEW->value)->count();
        $inActiveProjects = Project::where('project_status', ProjectStatusEnum::INACTIVE->value)->count();
    
        // Ambil statistik sebelumnya untuk perbandingan
        list($currentProjectCount, $lastProjectCount) = $this->getAmountStatistic(Project::query(), 'count', null);
        list($currentCompletedCount, $lastCompletedCount) = $this->getAmountStatistic(Project::where('project_status', ProjectStatusEnum::COMPLETED->value), 'count', null);
        list($currentInProgressCount, $lastInProgressCount) = $this->getAmountStatistic(Project::where('project_status', ProjectStatusEnum::IN_PROGRESS->value), 'count', null);
        list($currentNeedReviewCount, $lastNeedReviewCount) = $this->getAmountStatistic(Project::where('project_status', ProjectStatusEnum::IN_REVIEW->value), 'count', null);
        list($currentInActiveCount, $lastInActiveCount) = $this->getAmountStatistic(Project::where('project_status', ProjectStatusEnum::INACTIVE->value), 'count', null);
    
        // Hitung persentase perubahan
        $projectPercentage = $this->getPercentage($lastProjectCount, $currentProjectCount);
        $completedPercentage = $this->getPercentage($lastCompletedCount, $currentCompletedCount);
        $inProgressPercentage = $this->getPercentage($lastInProgressCount, $currentInProgressCount);
        $needReviewPercentage = $this->getPercentage($lastNeedReviewCount, $currentNeedReviewCount);
        $inActivePercentage = $this->getPercentage($lastInActiveCount, $currentInActiveCount);
    
        // Data statistik
        $statistics = [
            [
                "statistic_id" => Str::uuid(),
                "statistic_name" => "Total Project",
                "statistic_number" => $totalProjects,
                "statistic_percentage" => $projectPercentage,
                "statistic_status" => $projectPercentage > 0 ? 'up' : 'down',
                "statistic_icon" => "uil uil-plus-circle",
            ],
            [
                "statistic_id" => Str::uuid(),
                "statistic_name" => "Completed",
                "statistic_number" => $completedProjects,
                "statistic_percentage" => $completedPercentage,
                "statistic_status" => $completedPercentage > 0 ? 'up' : 'down',
                "statistic_icon" => "uil uil-check-circle",
            ],
            [
                "statistic_id" => Str::uuid(),
                "statistic_name" => "In Progress",
                "statistic_number" => $inProgressProjects,
                "statistic_percentage" => $inProgressPercentage,
                "statistic_status" => $inProgressPercentage > 0 ? 'up' : 'down',
                "statistic_icon" => "uil uil-spinner-alt",
            ],
            [
                "statistic_id" => Str::uuid(),
                "statistic_name" => "Need Review",
                "statistic_number" => $needReviewProjects,
                "statistic_percentage" => $needReviewPercentage,
                "statistic_status" => $needReviewPercentage > 0 ? 'up' : 'down',
                "statistic_icon" => "uil uil-edit-alt",
            ],
            [
                "statistic_id" => Str::uuid(),
                "statistic_name" => "In Active",
                "statistic_number" => $inActiveProjects,
                "statistic_percentage" => $inActivePercentage,
                "statistic_status" => $inActivePercentage > 0 ? 'up' : 'down',
                "statistic_icon" => "uil uil-lock-slash",
            ],
        ];
    
        return response()->json([
            "success" => true,
            "message" => "Statistics retrieved successfully",
            "project_statistic" => $statistics,
        ]);
    }
    
    public function getProjectCreateDetail(Project $project)
{
    $projectDetail = Project::with(['projectCreatorInformation', 'projectBeneficialInformation', 'projectTags.tag'])
        ->where('project_id', $project->project_id)
        ->get()
        ->map(function ($proj) {
            // Pastikan relasi ada sebelum mengakses properti
            $proj->project_image_path = $this->getUrlFile($proj->project_image_path);
            if ($proj->projectCreatorInformation) {
                $proj->projectCreatorInformation->creator_file_path = $this->getUrlFile($proj->projectCreatorInformation->creator_file_path);
            }

            if ($proj->projectBeneficialInformation) {
                $proj->projectBeneficialInformation->beneficiary_file_path = $this->getUrlFile($proj->projectBeneficialInformation->beneficiary_file_path);
            }

            return $proj;
        });

    return response()->json([
        'message' => 'Berhasil Mengambil',
        'project_detail' => $projectDetail], 200);
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

    public function projectSocialMediaShare(Project $project, User $user, $socialMediaName)
    {


        $user_id = $user->user_id ?? null;
        $socialMedia = SocialMedia::where('social_media_name', strtolower($socialMediaName))->first();
    
        if (!$socialMedia) {
            return response()->json(['message' => 'Social media not found'], 404);
        }
    
        // Generate URL share berdasarkan platform
        $shareUrl = config('app.frontend_url'). "/projects/" . $project->project_id; // ✅ Menggunakan frontend URL
        $socialMediaUrl = $this->generateShareUrl($socialMediaName, $shareUrl, $project->project_title);
    
        if (!$socialMediaUrl) {
            return response()->json(['message' => 'Unsupported social media platform'], 400);
        }
    
        // Simpan ke database
        ProjectShare::create([
            'project_id' => $project->project_id,
            'user_id' => $user_id,
            'social_media_id' => $socialMedia->social_media_id,
            'url' => $socialMediaUrl
        ]);
    
        return response()->json([
            'message' => 'Project Social Media Share Tracked',
            'share_url' => $socialMediaUrl
        ], 200);
    }
    

    private function generateShareUrl($platform, $url, $text)
    {
        switch (strtolower($platform)) {
            case 'facebook':
                return "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($url);
            case 'twitter':
                return "https://twitter.com/intent/tweet?url=" . urlencode($url) . "&text=" . urlencode($text);
            case 'linkedin':
                return "https://www.linkedin.com/shareArticle?mini=true&url=" . urlencode($url);
            case 'whatsapp':
                return "https://wa.me/?text=" . urlencode($text . ' ' . $url);
            case 'telegram':
                return "https://t.me/share/url?url=" . urlencode($url) . "&text=" . urlencode($text);
            default:
                return $url; // Jika tidak ada di daftar, kembalikan URL biasa
        }
    }
    

}
