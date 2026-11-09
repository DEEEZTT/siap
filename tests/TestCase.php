<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    /** @var list<string> */
    private array $temporaryLocalRoots = [];

    protected function useTemporaryLocalDisk(): void
    {
        $root = storage_path('framework/testing/disks/local-'.bin2hex(random_bytes(8)));
        config(['filesystems.disks.local.root' => $root]);
        Storage::forgetDisk('local');
        $this->temporaryLocalRoots[] = $root;
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryLocalRoots as $root) {
            File::deleteDirectory($root);
        }

        parent::tearDown();
    }
}
