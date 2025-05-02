<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Project;
use App\Models\Notification;
use App\Traits\HasFileTrait;
use Illuminate\Http\Request;
use App\Models\ProjectEvaluasi;
use Illuminate\Validation\Rule;
use App\Events\NotificationEvent;
use Illuminate\Support\Facades\Log;
use App\Enums\ProjectEvaluationEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ProjectEvaluationController extends Controller
{
    use HasFileTrait;

    public function getProjectEvaluation(Project $project) {
        $cacheKey = "project_{$project->project_id}_evaluation";

        $evaluationList = Cache::remember($cacheKey, now()->addMinutes(3), function ()  use ($project) {
            $evaluations = $project->projectEvaluasis;

            $evaluationList = $evaluations->map(function ($evaluation) {
                return [
                    'project_evaluation_id' => $evaluation->project_evaluasi_id,
                    'project_evaluator_id' => $evaluation->evaluator_id,
                    'project_evaluator_avatar' => $this->getUrlFile($evaluation->evaluator->profile_picture),
                       
                    'project_evaluator_name' => $evaluation->evaluator->first_name ?? 'Anonymous', // Jika evaluator null, beri default
                    'project_evaluation_comment' => $evaluation->task_comment,
                    'project_evaluation_tag_component' => $evaluation->tag_component,
                    'project_evaluation_status' => $evaluation->status,
                    'project_evaluation_send_time' => $evaluation->getHour,
                    'project_evaluation_check' => $evaluation->checked
                ];
            });

            return $evaluationList;
        });

        return response()->json([
            'message' => 'Succes',
            'project_id' => $project->project_id,
            'project_evaluation' => $evaluationList,
        ]);

    }

    public function updateProjectEvaluation(Request $request, Project $project, User $user) {
        $cacheKey = "project_{$project->project_id}_evaluation";

        try {
            $validator = Validator::make($request->all(), [
                '*.project_evaluation_id' => 'required|uuid',
                '*.project_evaluation_check' => 'required|boolean',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
    
            $validated = $validator->validated();
            
            $projectEvaluasi = [];
            foreach ($validated as $data) {
                $projectEvaluation = $project->projectEvaluasis->where('project_evaluasi_id', $data['project_evaluation_id'])->first();
               
                if ($projectEvaluation) {
                    $projectEvaluation->update([
                        'checked' => $data['project_evaluation_check'],
                    ]);
                }

                // $projectEvaluation->refresh();

                // dd($projectEvaluation);

                $projectEvaluasi [] = $projectEvaluation;
            }
            

            Cache::forget($cacheKey);

            $evaluations = $project->projectEvaluasis;

            $evaluationList = $evaluations->map(function ($evaluation) {
                return [
                    'project_evaluation_id' => $evaluation->project_evaluasi_id,
                    'project_evaluator_id' => $evaluation->evaluator_id,
                    'project_evaluator_avatar' => $evaluation->evaluator && $evaluation->evaluator->profile_picture
                        ? $this->getUrlFile($evaluation->evaluator->profile_picture)
                        : null, // Jika `profile_picture` null, set avatar null
                    'project_evaluator_name' => $evaluation->evaluator->fullName ?? 'Anonymous', // Jika evaluator null, beri default
                    'project_evaluation_comment' => $evaluation->task_comment,
                    'project_evaluation_tag_component' => $evaluation->tag_component,
                    'project_evaluation_status' => $evaluation->status,
                    'project_evaluation_send_time' => $evaluation->getHour,
                    'project_evaluation_check' => $evaluation->checked
                ];
            });

            Cache::put($cacheKey, $evaluationList);
    
            return response()->json(['message' => 'Evaluations updated successfully', 'project_evaluasi' => $projectEvaluasi], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeProjectEvaluation(Request $request, Project $project) {
        $cacheKey = "project_{$project->project_id}_evaluation";

        try {
            $user_id = Auth('api')->check() ? Auth('api')->user()->user_id : null;

            $validator = Validator::make($request->all(), [
                'task_comment' => 'required|string',
                'tag_component' => 'required|string',

            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);

            }

            $validated = $validator->validated();

            $validated['status'] = ProjectEvaluationEnum::IN_REVIEW->value;
            $validated['checked'] = false;
            $validated['evaluator_id'] = $user_id;
            $validated['project_id'] = $project->project_id;


            $projectEvaluasi = ProjectEvaluasi::create($validated);

            Cache::forget($cacheKey);

            $evaluations = $project->projectEvaluasis;

            $evaluationList = $evaluations->map(function ($evaluation) {
                return [
                    'project_evaluation_id' => $evaluation->project_evaluasi_id,
                    'project_evaluator_id' => $evaluation->evaluator_id,
                    'project_evaluator_avatar' => $evaluation->evaluator && $evaluation->evaluator->profile_picture
                        ? $this->getUrlFile($evaluation->evaluator->profile_picture)
                        : null, // Jika `profile_picture` null, set avatar null
                    'project_evaluator_name' => $evaluation->evaluator->fullName ?? 'Anonymous', // Jika evaluator null, beri default
                    'project_evaluation_comment' => $evaluation->task_comment,
                    'project_evaluation_tag_component' => $evaluation->tag_component,
                    'project_evaluation_status' => $evaluation->status,
                    'project_evaluation_send_time' => $evaluation->getHour,
                    'project_evaluation_check' => $evaluation->checked
                ];
            });

            Cache::put($cacheKey, $evaluationList);

            $notification = Notification::create([
                'notification_title' => 'Review Baru dari Admin!',
                'notification_icon' => 'ui uil-task',
                'notification_text' => 'Admin telah memberikan review untuk project ' . $project->project_title . ' Buka project untuk melihat komentar dan tindak lanjut yang diperlukan ',
                'notification_url' => env("FRONTEND_URL") . "/project/{$project->project_id}",
                'target_id' => $project->creator_id
            ]);

        broadcast(new NotificationEvent($project->creator_id, $notification))->via('pusher');

            return response()->json([
                'message' => 'Evaluasi Berhasil Disimpan',
                'project_evaluasi' => $projectEvaluasi
            ], 201);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);

        }
    }

    public function updateStatusProjectEvaluation(Request $request, ProjectEvaluasi $projectEvaluation) { 
        

        // Validasi input status
        $validated = $request->validate([
            'status' => ['required', Rule::in(ProjectEvaluationEnum::values())],
        ]);
    
        $oldStatus = $projectEvaluation->status;
        $projectEvaluation->status = $validated['status'];
        $projectEvaluation->save();

        $project = Project::find($projectEvaluation->project_id);

        $cacheKey = "project_{$project->project_id}_evaluation";

        Cache::forget($cacheKey);

        $evaluations = $project->projectEvaluasis;

        $evaluationList = $evaluations->map(function ($evaluation) {
            return [
                'project_evaluation_id' => $evaluation->project_evaluasi_id,
                'project_evaluator_id' => $evaluation->evaluator_id,
                'project_evaluator_avatar' => $evaluation->evaluator && $evaluation->evaluator->profile_picture
                    ? $this->getUrlFile($evaluation->evaluator->profile_picture)
                    : null, // Jika `profile_picture` null, set avatar null
                'project_evaluator_name' => $evaluation->evaluator->fullName ?? 'Anonymous', // Jika evaluator null, beri default
                'project_evaluation_comment' => $evaluation->task_comment,
                'project_evaluation_tag_component' => $evaluation->tag_component,
                'project_evaluation_status' => $evaluation->status,
                'project_evaluation_send_time' => $evaluation->getHour,
                'project_evaluation_check' => $evaluation->checked
            ];
        });

        Cache::put($cacheKey, $evaluationList);
    
        return response()->json([
            'message' => 'Status evaluasi berhasil diperbarui',
            'old_status' => $oldStatus,
            'new_status' => $projectEvaluation->status,
        ], 200);
    }
    
    public function deleteProjectEvaluation(ProjectEvaluasi $projectEvaluation) {
        // Pastikan user adalah admin sebelum menghapus
        // if (!auth()->user()->isAdmin()) {
        //     return response()->json([
        //         'message' => 'Anda tidak memiliki izin untuk menghapus evaluasi'
        //     ], 403);
        // }

        $project = Project::with(['projectEvaluasis'])->find($projectEvaluation->project_id);

        // Log::info('project eval : ', [$project]);

        $cacheKey = "project_{$project->project_id}_evaluation";

        Cache::forget($cacheKey);

        $evaluations = $project->projectEvaluasis;

        $evaluationList = $evaluations->map(function ($evaluation) {
            return [
                'project_evaluation_id' => $evaluation->project_evaluasi_id,
                'project_evaluator_id' => $evaluation->evaluator_id,
                'project_evaluator_avatar' => $evaluation->evaluator && $evaluation->evaluator->profile_picture
                    ? $this->getUrlFile($evaluation->evaluator->profile_picture)
                    : null, // Jika `profile_picture` null, set avatar null
                'project_evaluator_name' => $evaluation->evaluator->fullName ?? 'Anonymous', // Jika evaluator null, beri default
                'project_evaluation_comment' => $evaluation->task_comment,
                'project_evaluation_tag_component' => $evaluation->tag_component,
                'project_evaluation_status' => $evaluation->status,
                'project_evaluation_send_time' => $evaluation->getHour,
                'project_evaluation_check' => $evaluation->checked
            ];
        });

        Cache::put($cacheKey, $evaluationList);
    
        $projectEvaluation->delete();
    
        return response()->json([
            'message' => 'Evaluasi berhasil dihapus'
        ], 200);
    }
    
    
}
