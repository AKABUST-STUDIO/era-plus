<?php

use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

test('registration populates uuid and slug', function (): void {
    Mail::fake();

    app(AuthenticationService::class)->register('ada.lovelace@example.com');

    $user = User::query()->where('email', 'ada.lovelace@example.com')->firstOrFail();

    $this->assertTrue(Str::isUuid($user->uuid));
    $this->assertNotEmpty($user->slug);
    $this->assertStringStartsWith('ada-lovelace-', $user->slug);
    $this->assertSame($user->slug, $user->username);
});

test('username can be persisted and stays unique', function (): void {
    User::factory()->create(['username' => 'taken']);

    $user = User::factory()->create();
    $user->update(['username' => 'available']);

    $this->assertSame('available', $user->fresh()->username);

    $this->expectException(QueryException::class);

    $second = User::factory()->create();
    $second->update(['username' => 'taken']);
});
