<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

test('user can view their profile settings', function (): void {
    $user = User::factory()->create(['name' => 'Maria Solo']);

    $this->browse(function (Browser $browser) use ($user): void {
        $browser->loginAs($user)
            ->visit('/profile/settings')
            ->waitForText('Maria Solo');
    });
});
