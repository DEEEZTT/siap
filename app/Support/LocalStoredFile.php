<?php

namespace App\Support;

class LocalStoredFile
{
    public static function resolve(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $defaultDisk = (string) config('filesystems.default', 'local');
        $roots = array_unique(array_filter([
            config('filesystems.disks.local.root'),
            config('filesystems.disks.'.$defaultDisk.'.root'),
            storage_path('app'),
        ]));

        foreach ($roots as $configuredRoot) {
            $root = realpath((string) $configuredRoot);
            $absolutePath = $root ? realpath($root.DIRECTORY_SEPARATOR.$relativePath) : false;

            if ($absolutePath && str_starts_with($absolutePath, $root.DIRECTORY_SEPARATOR) && is_file($absolutePath)) {
                return $absolutePath;
            }
        }

        return null;
    }
}
