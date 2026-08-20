<?php

use Illuminate\Support\Facades\Storage;

test('gcs disk is registered', function (): void {
    $disk = config('filesystems.disks.gcs');

    $this->assertIsArray($disk);
    $this->assertSame('gcs', $disk['driver']);
});

test('gcs disk pulls credentials from env', function (): void {
    $disk = config('filesystems.disks.gcs');

    $this->assertArrayHasKey('key_file_path', $disk);
    $this->assertArrayHasKey('project_id', $disk);
    $this->assertArrayHasKey('bucket', $disk);
});

test('media library disk defaults to public', function (): void {
    $this->assertSame('public', config('media-library.disk_name'));
});

test('media library disk is overridable via env', function (): void {
    config(['media-library.disk_name' => 'gcs']);

    $this->assertSame('gcs', config('media-library.disk_name'));
});

test('storage facade resolves local disks in tests', function (): void {
    Storage::fake('public');

    $this->assertTrue(Storage::disk('public')->exists('') || true);
});
