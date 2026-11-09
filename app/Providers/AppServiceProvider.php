<?php

namespace App\Providers;

use Illuminate\Filesystem\LocalFilesystemAdapter as LaravelLocalFilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::extend('local', function ($app, array $config) {
            $visibility = PortableVisibilityConverter::fromArray(
                $config['permissions'] ?? [],
                $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE,
            );
            $links = ($config['links'] ?? null) === 'skip'
                ? LocalAdapter::SKIP_LINKS
                : LocalAdapter::DISALLOW_LINKS;
            $mimeDetectorClass = $config['mime_detector'] ?? ExtensionMimeTypeDetector::class;
            $mimeDetector = new $mimeDetectorClass();

            $adapter = new LocalAdapter(
                $config['root'],
                $visibility,
                $config['lock'] ?? LOCK_EX,
                $links,
                $mimeDetector,
                lazyRootCreation: $config['lazy_root_creation'] ?? false,
            );
            $flysystemAdapter = $adapter;

            if ($config['read-only'] ?? false) {
                $flysystemAdapter = new ReadOnlyFilesystemAdapter($flysystemAdapter);
            }
            if (! empty($config['prefix'])) {
                $flysystemAdapter = new PathPrefixedAdapter($flysystemAdapter, $config['prefix']);
            }

            $driver = new Flysystem($flysystemAdapter, Arr::only($config, [
                'directory_visibility',
                'disable_asserts',
                'retain_visibility',
                'temporary_url',
                'url',
                'visibility',
            ]));

            return (new LaravelLocalFilesystemAdapter($driver, $adapter, $config))
                ->diskName($config['disk_name'] ?? 'local')
                ->shouldServeSignedUrls(
                    $config['serve'] ?? false,
                    fn () => $app['url'],
                );
        });
    }
}
