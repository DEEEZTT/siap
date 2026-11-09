<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

final class UploadedDocument
{
    public static function extension(UploadedFile $file): ?string
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            return null;
        }

        $signature = fread($handle, 8);
        fclose($handle);

        if (str_starts_with($signature, '%PDF-')) {
            return 'pdf';
        }
        if (str_starts_with($signature, "\xFF\xD8\xFF")) {
            return 'jpg';
        }
        if ($signature === "\x89PNG\r\n\x1A\n") {
            return 'png';
        }

        return null;
    }

    public static function store(UploadedFile $file, string $directory, string $disk = 'local'): string|false
    {
        $extension = self::extension($file);
        if ($extension === null) {
            return false;
        }

        return $file->storeAs($directory, bin2hex(random_bytes(16)).'.'.$extension, $disk);
    }
}
