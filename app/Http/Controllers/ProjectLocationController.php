<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Project;
use App\Models\Provinsi;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ProjectLocationController extends Controller
{
    public function getProjectLocation(Project $project) {
        $cacheKey = "project_{$project->project_id}_location";

        // Pastikan project sudah memuat relasi untuk menghindari N+1 Query Problem

        $location = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($project) {
            $project->load(['desa.kecamatan.kabupaten.provinsi']);

            $location = [
                'project_location_address' => $project->project_address,
                'project_location_desa' => [
                    'kode_desa' => optional($project->desa)->kode_desa,
                    'nama_desa' => optional($project->desa)->nama_desa
                ],
                'project_location_kecamatan' => [
                    'kode_kecamatan' => optional($project->desa->kecamatan)->kode_kecamatan,
                    'nama_kecamatan' => optional($project->desa->kecamatan)->nama_kecamatan
                ],
                'project_location_kabupaten' => [
                    'kode_kabupaten' => optional($project->desa->kecamatan->kabupaten)->kode_kabupaten,
                    'nama_kabupaten' => optional($project->desa->kecamatan->kabupaten)->nama_kabupaten
                ],
                'project_location_provinsi' => [
                    'kode_provinsi' => optional($project->desa->kecamatan->kabupaten->provinsi)->kode_provinsi,
                    'nama_provinsi' => optional($project->desa->kecamatan->kabupaten->provinsi)->nama_provinsi
                ],
                'project_location_point' => [
                    'latitude' => $project->latitude,
                    'longitude' => $project->longitude  
                ]
            ];

            return $location;
        });
        

        return response()->json([
            'message' => 'Success',
            'project_id' => $project->project_id,
            'project_location' => $location
        ]);
    }

    public function getProvinsi() {
        $provinsiList = Provinsi::all();

        return response()->json([
            'message' => 'Data Provinsi Berhasil Diambil',
            'provinsi' => $provinsiList
        ], 200);
    }

    public function getKabupatenByProvinsi(Provinsi $provinsi) {
        $kabupatenList =$provinsi->kabupatens;

        // dd($provinsi);

        return response()->json([
            'message' => 'Data Kabupaten Berhasil Diambil',
            'kabupaten' => $kabupatenList
        ], 200);
    }

    public function getKecamatanByKabupaten(Kabupaten $kabupaten) {
        $kecamatanList = $kabupaten->kecamatans;

        return response()->json([
            'message' => 'Data kecamatan Berhasil Diambil',
            'kecamatan' => $kecamatanList
        ], 200);
    }

    public function getDesaByKecamatan(Kecamatan $kecamatan) {
        $desaList = $kecamatan->desas;
        
        return response()->json([
            'message' => 'Data desa Berhasil Diambil',
            'desa' => $desaList
        ], 200);
        
    }

    public function updateProjectLocation(Request $request, Project $project) {
        $cacheKey = "project_{$project->project_id}_location";

        try {
            $validator = Validator::make($request->all(),[
                'kode_desa' => 'nullable',
                'project_address' => 'nullable',
                'latitude' => 'nullable',
                'longitude' => 'nullable'
            ]
            );

            if ($validator->fails()){
                return response()->json([
                    'message' => 'Validation Error',
                    'error' => $validator->errors()
                ],  400);
            }

            $validated = $validator->validated();

            $project->update($validated);

            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'location has successfully updated',
                'project' => $project
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error Occured',
                 'error' => $e->getMessage()
            ], 400);
            
        }
    }
}
