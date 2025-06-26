<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

trait HasFileTrait
{
    public function getPathFile(UploadedFile $file, string $path_folder, string $disk = 'WasabiTemp'): string
    {
        $disk = $disk ?? config('filesystems.default');

        $fileName = date('dmY') . '_' . $file->getClientOriginalName();

        // Log::info('filename : ', ["filename" => $fileName]);

        $path = Storage::disk($disk)->putFileAs($path_folder, $file, $fileName);

        // return $file->storeAs($path_folder, $fileName, $disk);

        //print $path value
        // Log::info('File upload path:', ['path' => $path]);

        return $path;
    }

    public function deleteFile(array|string $file, string $disk = 'WasabiTemp'): bool
    {
        $disk = $disk ?? config('filesystems.default');

        $files = is_array($file) ? $file : [$file];

        foreach ($files as $f) {
            if (Storage::disk($disk)->exists($f)) {
                Storage::disk($disk)->delete($f);
            }
        }

        return true;
    }

public function getUrlFile(?string $filePath, string $disk = 'WasabiTemp'): ?string
{
    $disk = $disk ?? config('filesystems.default');

    if (!$filePath) return null;

    $urlPath = '';

    if ($disk === 'WasabiTemp') {
        // Gunakan signed URL (berlaku 1 jam)
        try {
            $expiresAt = now()->addMinutes(60);
            $urlPath = Storage::disk($disk)->temporaryUrl($filePath, $expiresAt);
        } catch (\Exception $e) {
            Log::error('Failed to generate Wasabi signed URL', ['error' => $e->getMessage()]);
            return null;
        }
    } else {
        // Default local/public disk
        $urlPath = asset(Storage::url($filePath));
    }

    // Log::info('Getting file URL:', [
    //     'filePath' => $filePath,
    //     'disk' => $disk,
    //     'urlPath' => $urlPath
    // ]);

    return $urlPath;
}

}
