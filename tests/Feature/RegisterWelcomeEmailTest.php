<?php

namespace Tests\Feature;

use App\Filament\Organization\Pages\Auth\Register;
use App\Mail\AccountCreated;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterWelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('organization'));
    }

    private function currentCodeFor(User $user): string
    {
        return $user->oneTimePasswords()->latest('id')->firstOrFail()->password;
    }

    public function test_verifying_the_code_sends_the_welcome_email(): void
    {
        Mail::fake();

        $component = Livewire::test(Register::class)
            ->fillForm(['email' => 'newbie@akabust.studio'])
            ->call('registerAndSendCode')
            ->assertSet('step', 'code');

        $user = User::query()->where('email', 'newbie@akabust.studio')->firstOrFail();

        $this->assertNull($user->email_verified_at);

        $component
            ->fillForm(['code' => $this->currentCodeFor($user)])
            ->call('verifyCode');

        $this->assertNotNull($user->fresh()->email_verified_at);

        Mail::assertQueued(
            AccountCreated::class,
            fn (AccountCreated $mail): bool => $mail->hasTo('newbie@akabust.studio')
                && $mail->user->is($user),
        );
    }

    public function test_the_welcome_email_is_not_sent_again_for_an_already_verified_user(): void
    {
        Mail::fake();

        $component = Livewire::test(Register::class)
            ->fillForm(['email' => 'newbie@akabust.studio'])
            ->call('registerAndSendCode');

        $user = User::query()->where('email', 'newbie@akabust.studio')->firstOrFail();

        $component
            ->fillForm(['code' => $this->currentCodeFor($user)])
            ->call('verifyCode');

        Mail::assertQueuedCount(1);

        $user->refresh()->sendOneTimePassword();

        $component
            ->fillForm(['code' => $this->currentCodeFor($user->fresh())])
            ->call('verifyCode');

        Mail::assertQueuedCount(1);
    }

    public function test_the_welcome_email_greets_by_name_and_links_to_the_panel(): void
    {
        $user = User::factory()->create([
            'email' => 'newbie@akabust.studio',
            'name' => 'Newbie',
        ]);

        $rendered = (new AccountCreated($user))->render();

        $this->assertStringContainsString('Newbie', $rendered);
        $this->assertStringContainsString(e(Filament::getPanel('organization')->getUrl()), $rendered);
    }
}
