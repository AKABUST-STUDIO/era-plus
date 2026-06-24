<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Tests\TestCase;

class UserAvatarSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_implements_has_media(): void
    {
        $this->assertInstanceOf(HasMedia::class, new User);
    }

    public function test_user_can_attach_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->addMedia(UploadedFile::fake()->image('me.jpg', 600, 600))
            ->toMediaCollection('avatar');

        $this->assertCount(1, $user->fresh()->getMedia('avatar'));
        $this->assertNotNull($user->getFilamentAvatarUrl());
    }

    public function test_user_avatar_url_is_null_when_unset(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->getFilamentAvatarUrl());
    }

    public function test_organization_soft_deletes(): void
    {
        $organization = Organization::factory()->create();
        $id = $organization->id;

        $organization->delete();

        $this->assertSoftDeleted('organizations', ['id' => $id]);
    }

    public function test_organization_can_be_restored(): void
    {
        $organization = Organization::factory()->create();
        $organization->delete();

        $organization->restore();

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'deleted_at' => null,
        ]);
    }

    public function test_soft_deleted_organization_keeps_project_data(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->for($organization)->create();

        $organization->delete();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }
}
