<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    public function __construct(protected string $disk = 'public') {}

    /**
     * رفع ملف وتخزينه في مسار محدد
     */
    public function upload(UploadedFile $file, string $folder): string
    {

        return $file->store($folder, $this->disk);
    }

    public function delete(?string $path): bool
    {
        if ($path && Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->delete($path);
        }

        return false;
    }

    /**
     * الحصول على الرابط المباشر للملف
     */
    public function url(?string $path, string $disk = 'public'): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk($disk)->url($path);
    }
}
