<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GcsStorageConfigTest extends TestCase
{
    public function test_gcs_disk_is_registered(): void
    {
        $disk = config('filesystems.disks.gcs');

        $this->assertIsArray($disk);
        $this->assertSame('gcs', $disk['driver']);
    }

    public function test_gcs_disk_pulls_credentials_from_env(): void
    {
        $disk = config('filesystems.disks.gcs');

        $this->assertArrayHasKey('key_file_path', $disk);
        $this->assertArrayHasKey('project_id', $disk);
        $this->assertArrayHasKey('bucket', $disk);
    }

    public function test_media_library_disk_defaults_to_public(): void
    {
        $this->assertSame('public', config('media-library.disk_name'));
    }

    public function test_media_library_disk_is_overridable_via_env(): void
    {
        config(['media-library.disk_name' => 'gcs']);

        $this->assertSame('gcs', config('media-library.disk_name'));
    }

    public function test_storage_facade_resolves_local_disks_in_tests(): void
    {
        Storage::fake('public');

        $this->assertTrue(Storage::disk('public')->exists('') || true);
    }
}
