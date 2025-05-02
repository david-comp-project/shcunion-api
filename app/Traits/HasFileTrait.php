<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HasFileTrait
{
    public function getPathFile(UploadedFile $file, string $path_folder, string $disk = 'public'): string
    {
        $disk = $disk ?? config('filesystems.default');

        $fileName = date('dmY') . '_' . $file->getClientOriginalName();
        
        return $file->storeAs($path_folder, $fileName, $disk);
    }

    public function deleteFile(array|string $file, string $disk = null): bool
    {
        $disk = $disk ?? config('filesystems.default');

        if (is_array($file)) {
            foreach ($file as $f) {
                if (Storage::disk($disk)->exists($f)) {
                    Storage::disk($disk)->delete($f);
                }
            }
        } else {
            if (Storage::disk($disk)->exists($file)) {
                Storage::disk($disk)->delete($file);
            }
        }

        return true;
    }

    public function getUrlFile(?string $filePath, string $disk = null): ?string
    {
        $disk = $disk ?? config('filesystems.default');

        return $filePath ? asset(Storage::url($filePath)) : null;
    }
}
