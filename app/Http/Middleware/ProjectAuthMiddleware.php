<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ProjectAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ambil project dari parameter route (cek apakah model binding aktif)
        $projectModel = $request->project;

        // Log::info('project : ', [$projectModel]);

        // Jika yang dikembalikan adalah objek, ambil ID-nya
        if ($projectModel instanceof Project) {
            $projectId = $projectModel->project_id;
        } else {
            $projectId = $request->route('project'); // Jika hanya berupa string
        }

        // Log::info('Project ID dari route:', [$projectId]);

        // Cari project di database berdasarkan project_id
        $project = Project::where('project_id', $projectId)->first();
        // Log::info('Data project:', [$project]);

        // Cek apakah project ditemukan
        if (!$project) {
            return response()->json(['message' => 'Project Not Found'], 404);
        }

        // Ambil user yang sedang login
        $userAuth = Auth('api')->user();
        if (!$userAuth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ambil role user
        $roles = $userAuth->getRoleNames()->toArray();

        // Log::info('User Does not Belong to Project ? ', [$userAuth->user_id !== $project->creator_id && !in_array('admin', $roles)]);

        // Cek apakah user adalah pemilik proyek atau memiliki role "admin"
        if ($userAuth->user_id !== $project->creator_id && !in_array('admin', $roles)) {
            abort(403, 'User Does not Belong to This Project Bro');
        }

        return $next($request);
    }
}
