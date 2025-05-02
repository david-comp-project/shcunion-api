<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProjectTimeline;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectTimelineDetail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ProjectTimelineController extends Controller
{
    public function getProjectTimeline(Project $project) {
        $cacheKey = "project_{$project->project_id}_timeline";

        $projectTimelines = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($project) {
            $projectTimelines = ProjectTimeline::with(['projectTimelineDetails.icon'])->where('project_id', $project->project_id)->get();

            return  $projectTimelines->map(function ($projectTimeline) {
                return [
                    'project_timeline_id' => $projectTimeline->project_timeline_id,
                    'project_timeline_date' => $projectTimeline->dateFormat,
                    'project_timeline_date_full' => $projectTimeline->dateFormatFull,
                    'project_timeline_details' => $projectTimeline->projectTimelineDetails()->orderBy('time', 'ASC')
                                                                  ->get()->map(function ($p) use ($projectTimeline) {
                    return [
                            'project_timeline_detail_id' => $p->project_timeline_detail_id,
                            'project_timeline_date_full' => $projectTimeline->dateFormatFull,
                            'description' => ucfirst($p->description),
                            'time' => $p->timeFormat,
                            'icon_id' => $p->icon->icon_id,
                            'icon' => $p->icon->icon,
                            'icon_name' => $p->icon->icon_name,
                            'icon_background' => $p->icon->icon_background
                        ];
                    })
                ];
            })->sortBy('project_timeline_date_full')->values();
        });
        

        return response()->json(['project_timeline' => $projectTimelines], 200);
    }

    public function updateProjectTimeline(Request $request, Project $project){
        $cacheKey = "project_{$project->project_id}_timeline";

        try {
            // Validasi Input
            $validator = Validator::make($request->all(), [
                'project_timeline_id' => 'nullable|uuid', // Bisa NULL atau UUID
                'project_timeline_detail_id' => 'nullable|uuid',
                'timeline_date' => 'nullable|date',
                'description' => 'required|string',
                'icon_id' => 'required|uuid'
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'error' => $validator->errors()
                ], 400);
            }
    
            $validated = $validator->validated();
            
            DB::beginTransaction();
            try {
                if (!empty($validated['project_timeline_id'])) {
                    $projectTimeline = $project->projectTimelines()
                        ->where('project_timeline_id', $validated['project_timeline_id'])
                        ->first();
    
                    if (!$projectTimeline) {
                        return response()->json([
                            'message' => 'Project Timeline not found'
                        ], 404);
                    }
    
                    // Update Project Timeline
                    $projectTimeline->update([
                        'timeline_date' => $validated['timeline_date']
                    ]);
    
                    // Update Project Timeline Detail
                    $projectTimelineDetail = $projectTimeline->projectTimelineDetails()
                        ->where('project_timeline_detail_id', $validated['project_timeline_detail_id'])
                        ->first();

                    if ($projectTimelineDetail) {
                        $projectTimelineDetail->update([
                            'description' => $validated['description'],
                            'icon_id' => $validated['icon_id']
                        ]);
                    }
                    
                    DB::commit();

                    Cache::forget($cacheKey);

                    return response()->json([
                        'message' => 'Timeline successfully updated',
                        'project_timeline' => $projectTimeline,
                        'project_timeline_detail' => $projectTimelineDetail
                    ], 200);
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error Occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function storeProjectTimeline(Request $request, Project $project) {
        $cacheKey = "project_{$project->project_id}_timeline";

        try {
            // Validasi Input
            $validator = Validator::make($request->all(), [
                '*.timeline_date' => 'required|date',
                '*.timeline_detail' => 'required|array|min:1',
                '*.timeline_detail.*.description' => 'required|string',
                '*.timeline_detail.*.time' => 'required|date_format:H:i', // Pastikan format waktu "HH:mm"
                '*.timeline_detail.*.icon_id' => 'required|uuid',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 400);
            }
    
            $validated = $validator->validated();
    
            // Transaksi Database
            DB::beginTransaction();
            try {
                $storedTimelines = [];
    
                foreach ($validated as $timelineData) {
                    // Simpan ke ProjectTimeline
                    $projectTimeline = ProjectTimeline::create([
                        'project_id' => $project->project_id,
                        'timeline_date' => $timelineData['timeline_date'],
                    ]);
    
                    // Siapkan array untuk insert bulk ke ProjectTimelineDetail
                    $details = [];
                    foreach ($timelineData['timeline_detail'] as $detail) {
                        $details[] = [
                            'project_timeline_detail_id' => Str::uuid(),
                            'project_timeline_id' => $projectTimeline->project_timeline_id,
                            'description' => $detail['description'],
                            'time' => $detail['time'],
                            'icon_id' => $detail['icon_id'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
    
                    // Insert bulk ke ProjectTimelineDetail
                    ProjectTimelineDetail::insert($details);
    
                    $storedTimelines[] = [
                        'timeline' => $projectTimeline,
                        'timeline_details' => $details
                    ];
                }
    
                // Commit transaksi jika semua berhasil
                DB::commit();

                Cache::forget($cacheKey);
    
                return response()->json([
                    'message' => 'All timelines successfully stored',
                    'data' => $storedTimelines
                ], 201);
    
            } catch (\Exception $e) {
                // Rollback jika ada kesalahan
                DB::rollBack();
                throw $e;
            }
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeProjectTimelineDetail (Request $request, Project $project, ProjectTimeline $projectTimeline) {
        // return $request->all();
        $cacheKey = "project_{$project->project_id}_timeline";

        try {
            $validator = Validator::make($request->all(), [
                'description' => 'required|string',
                '*.timeline_detail.*.time' => 'required|date_format:H:i', // Pastikan format waktu "HH:mm"
                'icon_id' => 'required|uuid',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            $validated['project_timeline_id'] = $projectTimeline->project_timeline_id;
            $validated['project_timeline_detail_id'] = Str::uuid();

            $projectTimeline->projectTimelineDetails()->insert($validated);

            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'All timelines successfully stored',
                // 'data' => $storedTimelines
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteProjectTimeline(Project $project, ProjectTimeline $projectTimeline)
    {
        try {
            return DB::transaction(function () use ($project, $projectTimeline) {
                // Pastikan project_timeline milik project terkait
                if ($projectTimeline->project_id !== $project->project_id) {
                    return response()->json([
                        'message' => 'Unauthorized access to project timeline'
                    ], 403);
                }
    
                // Hapus semua detail terkait
                $projectTimeline->projectTimelineDetails()->delete();
    
                // Hapus timeline utama
                $projectTimeline->delete();
    
                return response()->json([
                    'message' => 'Project timeline deleted successfully'
                ], 200);
            });
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error Occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    


    public function deleteProjectTimelineDetail(Project $project, ProjectTimeline $projectTimeline, ProjectTimelineDetail $projectTimelineDetail)
    {
        try {
            return DB::transaction(function () use ($projectTimeline, $projectTimelineDetail) {
                // Pastikan detail terkait dengan timeline
                if ($projectTimelineDetail->project_timeline_id !== $projectTimeline->project_timeline_id) {
                    return response()->json([
                        'message' => 'Unauthorized access to project timeline detail'
                    ], 403);
                }
    
                // Hapus detail timeline
                $projectTimelineDetail->delete();
    
                // Opsional: Hapus timeline jika tidak ada detail tersisa
                if (!$projectTimeline->projectTimelineDetails()->exists()) {
                    $projectTimeline->delete();
                }
    
                return response()->json([
                    'message' => 'Project timeline detail deleted successfully'
                ], 200);
            });
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error Occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}
