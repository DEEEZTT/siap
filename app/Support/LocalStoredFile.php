<?php

namespace App\Support;

class LocalStoredFile
{
    public static function resolve(string $relativePath): ?string
    {
        $root = realpath((string) config('filesystems.disks.local.root'));

        if (! $root) {
            return null;
        }

        $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $absolutePath = realpath($root.DIRECTORY_SEPARATOR.$relativePath);

        if (! $absolutePath || ! str_starts_with($absolutePath, $root.DIRECTORY_SEPARATOR) || ! is_file($absolutePath)) {
            return null;
        }

        return $absolutePath;
    }
}
