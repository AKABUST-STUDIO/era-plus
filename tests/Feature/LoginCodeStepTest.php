<?php

use App\Filament\Organization\Pages\Auth\Login;
use App\Mail\MissingAccountSignInAttempt;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('organization'));
});

function requestCodeLabel(): string
{
    return __('filament-panels::auth/pages/login.form.actions.request_code.label');
}

function useDifferentEmailLabel(): string
{
    return __('filament-panels::auth/pages/login.form.actions.use_different_email.label');
}

test('requesting a code swaps the rendered form to the code step', function (): void {
    $user = User::factory()->create(['email' => 'simon@akabust.studio']);

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email])
        ->call('requestLogin')
        ->assertSuccessful()
        ->assertSet('step', 'code')
        ->assertSet('emailForCode', $user->email)
        ->assertSee(useDifferentEmailLabel())
        ->assertDontSee(requestCodeLabel());
});

test('using a different email returns to the email step', function (): void {
    $user = User::factory()->create(['email' => 'simon@akabust.studio']);

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email])
        ->call('requestLogin')
        ->call('useDifferentEmail')
        ->assertSuccessful()
        ->assertSet('step', 'email')
        ->assertSet('emailForCode', null)
        ->assertSee(requestCodeLabel())
        ->assertDontSee(useDifferentEmailLabel());
});

test('unknown email advances to the code step without revealing that no account exists', function (): void {
    Mail::fake();

    Livewire::test(Login::class)
        ->fillForm(['email' => 'nobody@akabust.studio'])
        ->call('requestLogin')
        ->assertSuccessful()
        ->assertNoRedirect()
        ->assertSet('step', 'code')
        ->assertSee(useDifferentEmailLabel());
});

test('unknown email is sent a missing account email pointing at registration', function (): void {
    Mail::fake();

    Livewire::test(Login::class)
        ->fillForm(['email' => 'nobody@akabust.studio'])
        ->call('requestLogin');

    Mail::assertQueued(
        MissingAccountSignInAttempt::class,
        fn (MissingAccountSignInAttempt $mail): bool => $mail->hasTo('nobody@akabust.studio')
            && $mail->email === 'nobody@akabust.studio',
    );
});

test('the missing account email links to registration with the address prefilled', function (): void {
    $rendered = (new MissingAccountSignInAttempt('nobody@akabust.studio'))->render();

    $this->assertStringContainsString(
        e(Filament::getRegistrationUrl().'?email='.urlencode('nobody@akabust.studio')),
        $rendered,
    );
});

test('a known email is sent a code and no missing account email', function (): void {
    Mail::fake();

    $user = User::factory()->create(['email' => 'simon@akabust.studio']);

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email])
        ->call('requestLogin');

    Mail::assertNotQueued(MissingAccountSignInAttempt::class);
});

test('submitting a valid code authenticates and redirects', function (): void {
    $user = User::factory()->create(['email' => 'simon@akabust.studio']);

    $component = Livewire::test(Login::class)
        ->fillForm(['email' => $user->email])
        ->call('requestLogin');

    $component->fillForm(['code' => $user->oneTimePasswords()->latest('id')->firstOrFail()->password])
        ->call('login')
        ->assertSuccessful()
        ->assertRedirect(Filament::getUrl());

    $this->assertAuthenticatedAs($user);
});

test('submitting an invalid code keeps the user on the code step', function (): void {
    $user = User::factory()->create(['email' => 'simon@akabust.studio']);

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email])
        ->call('requestLogin')
        ->fillForm(['code' => '000000'])
        ->call('login')
        ->assertHasErrors('data.code')
        ->assertNoRedirect()
        ->assertSet('step', 'code');

    $this->assertGuest();
});
