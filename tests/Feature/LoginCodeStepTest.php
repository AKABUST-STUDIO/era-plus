<?php

namespace Tests\Feature;

use App\Filament\Organization\Pages\Auth\Login;
use App\Mail\MissingAccountSignInAttempt;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class LoginCodeStepTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('organization'));
    }

    private function requestCodeLabel(): string
    {
        return __('filament-panels::auth/pages/login.form.actions.request_code.label');
    }

    private function useDifferentEmailLabel(): string
    {
        return __('filament-panels::auth/pages/login.form.actions.use_different_email.label');
    }

    public function test_requesting_a_code_swaps_the_rendered_form_to_the_code_step(): void
    {
        $user = User::factory()->create(['email' => 'simon@akabust.studio']);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email])
            ->call('requestLogin')
            ->assertSuccessful()
            ->assertSet('step', 'code')
            ->assertSet('emailForCode', $user->email)
            ->assertSee($this->useDifferentEmailLabel())
            ->assertDontSee($this->requestCodeLabel());
    }

    public function test_using_a_different_email_returns_to_the_email_step(): void
    {
        $user = User::factory()->create(['email' => 'simon@akabust.studio']);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email])
            ->call('requestLogin')
            ->call('useDifferentEmail')
            ->assertSuccessful()
            ->assertSet('step', 'email')
            ->assertSet('emailForCode', null)
            ->assertSee($this->requestCodeLabel())
            ->assertDontSee($this->useDifferentEmailLabel());
    }

    public function test_unknown_email_advances_to_the_code_step_without_revealing_that_no_account_exists(): void
    {
        Mail::fake();

        Livewire::test(Login::class)
            ->fillForm(['email' => 'nobody@akabust.studio'])
            ->call('requestLogin')
            ->assertSuccessful()
            ->assertNoRedirect()
            ->assertSet('step', 'code')
            ->assertSee($this->useDifferentEmailLabel());
    }

    public function test_unknown_email_is_sent_a_missing_account_email_pointing_at_registration(): void
    {
        Mail::fake();

        Livewire::test(Login::class)
            ->fillForm(['email' => 'nobody@akabust.studio'])
            ->call('requestLogin');

        Mail::assertQueued(
            MissingAccountSignInAttempt::class,
            fn (MissingAccountSignInAttempt $mail): bool => $mail->hasTo('nobody@akabust.studio')
                && $mail->email === 'nobody@akabust.studio',
        );
    }

    public function test_the_missing_account_email_links_to_registration_with_the_address_prefilled(): void
    {
        $rendered = (new MissingAccountSignInAttempt('nobody@akabust.studio'))->render();

        $this->assertStringContainsString(
            e(Filament::getRegistrationUrl().'?email='.urlencode('nobody@akabust.studio')),
            $rendered,
        );
    }

    public function test_a_known_email_is_sent_a_code_and_no_missing_account_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'simon@akabust.studio']);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email])
            ->call('requestLogin');

        Mail::assertNotQueued(MissingAccountSignInAttempt::class);
    }

    public function test_submitting_a_valid_code_authenticates_and_redirects(): void
    {
        $user = User::factory()->create(['email' => 'simon@akabust.studio']);

        $component = Livewire::test(Login::class)
            ->fillForm(['email' => $user->email])
            ->call('requestLogin');

        $component->fillForm(['code' => $user->oneTimePasswords()->latest('id')->firstOrFail()->password])
            ->call('login')
            ->assertSuccessful()
            ->assertRedirect(Filament::getUrl());

        $this->assertAuthenticatedAs($user);
    }

    public function test_submitting_an_invalid_code_keeps_the_user_on_the_code_step(): void
    {
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
    }
}
