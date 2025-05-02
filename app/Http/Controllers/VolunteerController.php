<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Project;
use App\Models\Notification;
use App\Traits\HasFileTrait;
use Illuminate\Http\Request;
use App\Enums\ProjectStatusEnum;
use App\Models\VolunteerInvolvement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class VolunteerController extends Controller
{

    use HasFileTrait;

    
    public function storeVolunteer(Request $request, Project $project)
    {
        $user_id = Auth('api')->check() ? Auth('api')->user()->user_id : 'Guest';
        $project_id = $project->project_id;

        try {
            if ($request->has('criteria_checked') && is_string($request->criteria_checked)) {
                $request->merge([
                    'criteria_checked' => json_decode($request->criteria_checked, true)
                ]);
            }

            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'full_name' => 'required|string',
                'address' => 'required|string',
                'phone_number' => 'required|string',
                'criteria_checked' => 'required|array',
                'criteria_checked.*.key' => 'required|string',
                'criteria_checked.*.value' => 'required|string',
                'criteria_checked.*.role' => 'required|string',
                'criteria_checked.*.checked' => 'required|bool',
                'involvement_start_date' => 'nullable|date',
                'involvement_end_date' => 'nullable|date|after_or_equal:involvement_start_date',
                'involvement_start_time' => 'nullable|date_format:H:i',
                'involvement_end_time' => 'nullable|date_format:H:i|after:involvement_start_time',
                'role' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            // Konversi array ke JSON sebelum disimpan ke database
            if (isset($validated['criteria_checked']) && is_array($validated['criteria_checked'])) {
                $validated['criteria_checked'] = json_encode($validated['criteria_checked']);
            }

            // Hitung volunteer hours
            $validated['volunteer_id'] = $user_id;
            $validated['project_id'] = $project_id;
            $validated['volunteer_hours'] = $this->getVolunteerHours($validated);
            $validated['status'] = 'need review';

            $volunteerInvolvement = VolunteerInvolvement::create($validated);

            if ($project) {
                $totalVolunteer = $project->projectVolunteers->count();

                if ($totalVolunteer > $project->project_target_amount) {
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

            $user = User::where('user_id', $user_id)->first();
            $groupChat = $project->groupChat;

            $user->groupChats()->attach((string)$groupChat->group_chat_id);
            $cacheKey = "user_{$user->user_id}_group_list";

            $user->increment('total_points', 50);

            Cache::forget($cacheKey);

            //Add group_chat

            return response()->json([
                'message' => 'Volunteer Berhasil Menyimpan, Tunggu Review',
                'volunteer' => $volunteerInvolvement
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error Dalam Proses Volunteer',
                'error' => $th->getMessage()
            ], 400);
        }
    }

    public function updateStatusVolunteer(Request $request, Project $project, VolunteerInvolvement $volunteerInvolvement)
    {
        try {
            $validated = $request->validate([
                'note' => 'nullable|string',
                'status' => 'required|string'
            ]);

            // $status = $volunteerInvolvement->status;

            $volunteerInvolvement->update(['status' => $validated['status'], 'note' => $validated['note'] ?? null]);

            $cacheKey = "project_{$project->project_id}_donatur_list";

            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Status Berhasil Diupdate',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error Update Status Volunteer',
                'error' => $th->getMessage()
            ], 400);
        }
    }

    public function getVolunteers(Project $project)
    {
        $cacheKey = "project_{$project->project_id}_donatur_list";
        // if (Auth('api')->user()->user_id !== $project->creator_id) {
        //     return response()->json([
        //         'message' => 'Anda tidak memiliki akses untuk melihat data volunteer',
        //     ], 403);
        // }

        $volunteers = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($project) {
            $volunteers = $project->projectVolunteers;

            return $volunteers->map(function ($volunteer) {
                return [
                    'project_volunteer_id' => $volunteer->volunteer_involvement_id,
                    'project_title' => $volunteer->project->project_title,
                    'volunteer_name' => $volunteer->volunteer
                    ? ($volunteer->volunteer->full_name ?? $volunteer->volunteer->userFullName ?? 'Anonymous')
                    : 'Anonymous',
                    'volunteer_email' => $volunteer->email,
                    'volunteer_phone_number' => $volunteer->phone_number,
                    'volunteer_address' => $volunteer->address,
                    // 'volunteer_avatar' => $volunteer->volunteer->profile_picture ? asset(Storage::url($volunteer->volunteer->profile_picture)) : null,
                    'volunteer_avatar' => $volunteer->volunteer && $volunteer->volunteer->profile_picture ? $this->getUrlFile($volunteer->volunteer->profile_picture) : null,
                    'volunteer_date' => $volunteer->dateFormat,
                    'involvement_start_date' => $volunteer->involvement_start_date,
                    'involvement_end_date' => $volunteer->involvement_end_date,
                    'involvement_start_time' => $volunteer->involvement_start_time,
                    'involvement_end_time' => $volunteer->involvement_end_time,
                    'volunteer_role' => $volunteer->role,
                    'volunteer_status' => $volunteer->status,
                    'volunteer_criteria_checked' => $volunteer->criteria_checked,
                    'volunteer_created_at' => $volunteer->created_at,
                ];
            })->sortByDesc('volunteer_created_at')->values();
        });
        

        if ($volunteers->isEmpty()) {
            return response()->json([
                'message' => 'Data Volunteer Kosong',
            ], 404);
        }

        return response()->json([
            'message' => 'Data Volunteer',
            'volunteers' => $volunteers
        ], 200);
    }

    public function getVolunteerHours($validatedData)
    {
        if (!isset($validatedData['involvement_start_date'], $validatedData['involvement_end_date'], $validatedData['involvement_start_time'], $validatedData['involvement_end_time'])) {
            return 0;
        }

        try {
            $startDate = Carbon::parse($validatedData['involvement_start_date']);
            $endDate = Carbon::parse($validatedData['involvement_end_date']);
            $startTime = Carbon::parse($validatedData['involvement_start_time']);
            $endTime = Carbon::parse($validatedData['involvement_end_time']);

            // Selisih hari + (selisih jam kerja setiap hari)
            $daysDiff = $startDate->diffInDays($endDate) + 1;
            $hoursPerDay = $startTime->diffInHours($endTime);

            return $daysDiff * $hoursPerDay;
        } catch (\Throwable $th) {
            return 0; // Jika gagal, kembalikan 0 jam
        }
    }
}
