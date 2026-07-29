<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_populates_uuid_and_slug(): void
    {
        Mail::fake();

        app(AuthenticationService::class)->register('ada.lovelace@example.com');

        $user = User::query()->where('email', 'ada.lovelace@example.com')->firstOrFail();

        $this->assertTrue(Str::isUuid($user->uuid));
        $this->assertNotEmpty($user->slug);
        $this->assertStringStartsWith('ada-lovelace-', $user->slug);
        $this->assertSame($user->slug, $user->username);
    }

    public function test_username_can_be_persisted_and_stays_unique(): void
    {
        User::factory()->create(['username' => 'taken']);

        $user = User::factory()->create();
        $user->update(['username' => 'available']);

        $this->assertSame('available', $user->fresh()->username);

        $this->expectException(QueryException::class);

        $second = User::factory()->create();
        $second->update(['username' => 'taken']);
    }
}
