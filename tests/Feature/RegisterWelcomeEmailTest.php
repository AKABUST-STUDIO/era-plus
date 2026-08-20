<?php

use App\Filament\Organization\Pages\Auth\Register;
use App\Mail\AccountCreated;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('organization'));
});

function registerWelcomeEmailCurrentCodeFor(User $user): string
{
    return $user->oneTimePasswords()->latest('id')->firstOrFail()->password;
}

test('verifying the code sends the welcome email', function (): void {
    Mail::fake();

    $component = Livewire::test(Register::class)
        ->fillForm(['email' => 'newbie@akabust.studio'])
        ->call('requestRegister')
        ->assertSet('step', 'code');

    $user = User::query()->where('email', 'newbie@akabust.studio')->firstOrFail();

    $this->assertNull($user->email_verified_at);

    $component
        ->fillForm(['code' => registerWelcomeEmailCurrentCodeFor($user)])
        ->call('register')
        ->assertRedirect(Filament::getUrl());

    $this->assertNotNull($user->fresh()->email_verified_at);

    Mail::assertQueued(
        AccountCreated::class,
        fn (AccountCreated $mail): bool => $mail->hasTo('newbie@akabust.studio')
            && $mail->user->is($user),
    );
});

test('the welcome email is not sent again for an already verified user', function (): void {
    Mail::fake();

    $component = Livewire::test(Register::class)
        ->fillForm(['email' => 'newbie@akabust.studio'])
        ->call('requestRegister');

    $user = User::query()->where('email', 'newbie@akabust.studio')->firstOrFail();

    $component
        ->fillForm(['code' => registerWelcomeEmailCurrentCodeFor($user)])
        ->call('register');

    Mail::assertQueuedCount(1);

    $user->refresh()->sendOneTimePassword();

    $component
        ->fillForm(['code' => registerWelcomeEmailCurrentCodeFor($user->fresh())])
        ->call('register');

    Mail::assertQueuedCount(1);
});

test('the welcome email greets by name and links to the panel', function (): void {
    $user = User::factory()->create([
        'email' => 'newbie@akabust.studio',
        'name' => 'Newbie',
    ]);

    $rendered = (new AccountCreated($user))->render();

    $this->assertStringContainsString('Newbie', $rendered);
    $this->assertStringContainsString(e(Filament::getPanel('organization')->getUrl()), $rendered);
});
