<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Project;
use App\Traits\HasFileTrait;
use Illuminate\Http\Request;
use App\Models\ProjectLampiran;
use Illuminate\Support\Facades\Validator;

class ProjectLampiranController extends Controller
{

    use HasFileTrait;


    public function getProjectLampiran(Project $project) {

        $user = Auth('api')->user();

        $lampirans = $project->projectLampirans;

        $lampiranList = $lampirans->map(function ($lampiran) use ($user) {
            return [
                'project_lampiran_id' => $lampiran->project_lampiran_id,
                'project_lampiran_name' => $lampiran->nama_lampiran,
                // 'project_lampiran_url' => Storage::url($lampiran->path_lampiran),
                'project_lampiran_url' => $this->getUrlFile($lampiran->path_lampiran),
                'project_lampiran_type' => $lampiran->tipe_lampiran,
                'project_lampiran_size' => $lampiran->size_lampiran,
                'project_lampiran_tag'  => $lampiran->tag,
                'project_lampiran_section' => $lampiran->section,
                'project_lampiran_new'  => Carbon::parse($lampiran->created_at)->diffInDays(Carbon::now()) < 1,
                'project_uploader_admin' => $lampiran->uploader->first_name === $user->first_name ?? false
            ];
        });

        return response()->json([
            'message' => 'Success',
            'project_id' => $project->project_id,
            'project_lampiran' => $lampiranList
        ]);
    }

    public function storeProjectLampiran(Request $request, Project $project)
    {
        $user = Auth('api')->user();

        try {
            $validator = Validator::make($request->all(), [
                'project_lampiran'          => 'required|array', // Pastikan ini array
                'project_lampiran.*.file'     => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // Validasi setiap file
                'project_lampiran.*.section'  => 'required|string',
                'project_lampiran.*.tag'      => 'required|string'
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors()
                ], 400);
            }
    
            $lampiranData = [];
            // Ambil semua file dan tag menggunakan wildcard
            $files = $request->file('project_lampiran.*.file');
            $tags  = $request->input('project_lampiran.*.tag');
            $sections = $request->input('project_lampiran.*.section');
    
            // Pastikan $files dan $tags merupakan array dengan index yang sama
            foreach ($files as $index => $file) {
                $tag = $tags[$index] ?? null; // Ambil tag berdasarkan index]
                $section =$sections[$index] ?? null;
    
                $fileName =  strtolower($file->getClientOriginalName());
                // $path = $file->storeAs('project_lampiran', $fileName, 'public');

                
                $path = $this->getPathFile($file, 'project/project_lampiran');

                // Simpan data lampiran ke database
                $lampiran = ProjectLampiran::create([
                    'project_id'      => $project->project_id,
                    'nama_lampiran'   => $fileName,
                    'path_lampiran'   => $path,
                    'tipe_lampiran'   => $file->getClientMimeType(),
                    'size_lampiran'   => $file->getSize(),
                    'uploader_id'     => $user->user_id,
                    // Jika Anda ingin menyimpan tag, pastikan kolomnya ada di tabel
                    'tag'             => $tag,
                    'section'         => $section,
                    'status'          => 'active'
                ]);
                $lampiranData[] = $lampiran;
            }
    
            return response()->json([
                'message'           => 'Lampiran berhasil disimpan',
                'project_lampiran'  => $lampiranData
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan lampiran',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    


    public function deleteProjectLampiran(Project $project, ProjectLampiran $projectLampiran){
        // Cek apakah lampiran milik project yang benar
        // return $projectLampiran;
        if ($projectLampiran->project_id !== $project->project_id) {
            return response()->json([
                'message' => 'Lampiran tidak terkait dengan proyek ini'
            ], 403);
        }
    
        try {
            // Hapus file dari storage

            $this->deleteFile($projectLampiran->path_lampiran);
    
            // Hapus record dari database
            $projectLampiran->delete();
    
            return response()->json([
                'message' => 'Project Lampiran berhasil dihapus',
                'lampirans' => ProjectLampiran::all()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus lampiran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProjectLampiran(Request $request, Project $project, ProjectLampiran $projectLampiran) {
        // dd($projectLampiran);

        if ($projectLampiran->project_id !== $project->project_id) {
            return response()->json([
                'message' => 'Lampiran tidak terkait dengan proyek ini'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'project_lampiran' => 'required|file|mimes:pdf|max:10*1024'
            ]);

            $this->deleteFile($projectLampiran->path_lampiran);

            $file = $request->file('project_lampiran');
            $fileName =  strtolower($file->getClientOriginalName());
            $filePath = $file->storeAs('project_lampiran', $fileName, 'public');

            
            $projectLampiran->update([  
                'nama_lampiran' => $fileName,
                'path_lampiran' => $filePath,
                'tipe_lampiran' => $file->getClientMimeType(),
                'size_lampiran' => $file->getSize(),
                'status' => 'edited'
            ]);
            

            return response()->json([
                'message' => 'Project Lampiran berhasil diperbarui',
                'project_lampiran'    => $projectLampiran
            ], 200);


        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengupdate lampiran',
                'error'   => $e->getMessage()
            ], 500);
        }

    }

    public function getAllLampiran() {
        $lampiran = ProjectLampiran::all();

        return response()->json([
            'lampiran' => $lampiran
        ]);
    }
}
