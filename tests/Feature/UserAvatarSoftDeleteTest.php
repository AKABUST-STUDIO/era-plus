<?php

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

test('user implements has media', function (): void {
    $this->assertInstanceOf(HasMedia::class, new User);
});

test('user can attach avatar', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('me.jpg', 600, 600))
        ->toMediaCollection('avatar');

    $this->assertCount(1, $user->fresh()->getMedia('avatar'));
    $this->assertNotNull($user->getFilamentAvatarUrl());
});

test('user avatar url is null when unset', function (): void {
    $user = User::factory()->create();

    $this->assertNull($user->getFilamentAvatarUrl());
});

test('organization soft deletes', function (): void {
    $organization = Organization::factory()->create();
    $id = $organization->id;

    $organization->delete();

    $this->assertSoftDeleted('organizations', ['id' => $id]);
});

test('organization can be restored', function (): void {
    $organization = Organization::factory()->create();
    $organization->delete();

    $organization->restore();

    $this->assertDatabaseHas('organizations', [
        'id' => $organization->id,
        'deleted_at' => null,
    ]);
});

test('soft deleted organization keeps project data', function (): void {
    $organization = Organization::factory()->create();
    $project = Project::factory()->for($organization)->create();

    $organization->delete();

    $this->assertDatabaseHas('projects', ['id' => $project->id]);
});
