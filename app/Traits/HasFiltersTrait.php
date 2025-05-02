<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasFiltersTrait
{
    public function scopeFilter(Builder $query, array $filters): Builder
    {   

        if ($filters['status'] == null && $filters['sort'] == null && $filters['category'] == null && $filters['search'] == null) {
            return $query; // Tidak ada filter, kembalikan query tanpa perubahan
        }
        foreach ($filters as $key => $value) {
            // Pastikan metode filter ada di model dan nilai filter tidak null
            if (method_exists($this, 'filter' . ucfirst($key)) && !is_null($value)) {
                // Panggil metode filter{Key} yang ada di model
                $this->{'filter' . ucfirst($key)}($query, $value);
            }
        }

        return $query;
    }

    public function filterProjects($projects, $request)
    {
        // Filter berdasarkan 'status' (jika ada dan tidak kosong)
        if ($request->filled('status')) {
            $projects = $projects->where('project_status', $request->status);
        }
    
        // Filter berdasarkan 'category' (jika ada dan tidak kosong)
        if ($request->filled('category')) {
            $projects = $projects->where('project_category', $request->category);
        }
    
        // Pencarian berdasarkan 'search' (jika ada dan tidak kosong)
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $projects = $projects->filter(function ($project) use ($search) {
                return str_contains(strtolower($project['project_title']), $search) ||
                       str_contains(strtolower($project['project_description']), $search);
            });
        }
    
        // Sorting berdasarkan 'project_end_date' (jika ada dan tidak kosong)
        if ($request->filled('sort') && in_array($request->sort, ['asc', 'desc'])) {
            $projects = $projects->sortBy('project_end_date', SORT_REGULAR, $request->sort === 'desc');
        }
    
        // Reset index setelah sorting
        return $projects->values();
    }
    

    public function filterAllProject($projects, $querySorts, $search)
    {
        // Urutkan berdasarkan $querySorts ['project_category', 'project_status', 'project_target_amount', 'created_at'] // (jika ada dan tidak kosong)
        if ($querySorts) {
            $projects = $projects->sortBy($querySorts);
        }

        // Pencarian berdasarkan 'search' gunakan where like (jika ada dan tidak kosong)
        if ($search) {
            $projects = $projects->filter(function ($project) use ($search) {
                return str_contains(strtolower($project['project_title']), $search) ||
                       str_contains(strtolower($project['project_description']), $search);
            });
        }


        return $projects;


    }
}
