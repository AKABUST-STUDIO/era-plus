<?php

namespace Tests\Feature;

use App\Mail\AccountCreated;
use App\Models\User;
use App\Notifications\AccountAlreadyExistsNotification;
use App\Notifications\LoginCodeNotification;
use App\Notifications\RegistrationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationCodeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_sends_the_registration_code_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $user->sendInitialRegistrationMailable();

        Notification::assertSentTo($user, RegistrationCodeNotification::class);

        $this->assertSame(1, $user->oneTimePasswords()->count());
    }

    public function test_an_existing_account_sends_the_already_exists_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $user->sendAccountAlreadyExistsMailable();

        Notification::assertSentTo($user, AccountAlreadyExistsNotification::class);

        $this->assertSame(1, $user->oneTimePasswords()->count());
    }

    public function test_the_registration_mail_carries_the_code_and_a_valid_magic_link(): void
    {
        $user = User::factory()->create(['email' => 'newbie@akabust.studio']);
        $oneTimePassword = $user->createOneTimePassword();

        $mail = (new RegistrationCodeNotification($oneTimePassword))->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertStringContainsString($oneTimePassword->password, $mail->subject);

        $rendered = $this->render($mail);

        $this->assertStringContainsString($oneTimePassword->password, $rendered);
        $this->assertStringContainsString('newbie@akabust.studio', $rendered);

        $this->get($mail->viewData['magicLinkUrl'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_already_exists_mail_names_the_address_and_signs_the_user_in(): void
    {
        $user = User::factory()->create(['email' => 'returning@akabust.studio']);
        $oneTimePassword = $user->createOneTimePassword();

        $mail = (new AccountAlreadyExistsNotification($oneTimePassword))->toMail($user);

        $rendered = $this->render($mail);

        $this->assertStringContainsString('returning@akabust.studio', $rendered);
        $this->assertStringContainsString($oneTimePassword->password, $rendered);

        $this->get($mail->viewData['magicLinkUrl']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_notifications_are_queued_on_the_email_queue(): void
    {
        $user = User::factory()->create();
        $oneTimePassword = $user->createOneTimePassword();

        foreach ([LoginCodeNotification::class, RegistrationCodeNotification::class, AccountAlreadyExistsNotification::class] as $notification) {
            $this->assertSame(['mail' => config('queue.names.email')], (new $notification($oneTimePassword))->viaQueues());
        }
    }

    public function test_no_subject_or_body_string_is_left_untranslated(): void
    {
        $user = User::factory()->create();
        $oneTimePassword = $user->createOneTimePassword();

        foreach ([LoginCodeNotification::class, RegistrationCodeNotification::class, AccountAlreadyExistsNotification::class] as $notification) {
            $mail = (new $notification($oneTimePassword))->toMail($user);

            $this->assertStringNotContainsString('emails.', $mail->subject);
            $this->assertStringNotContainsString('emails.', $this->render($mail));
        }
    }

    public function test_the_welcome_mailable_is_queued_to_the_user(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'welcome@akabust.studio']);

        $user->sendWelcomeMailable();

        Mail::assertQueued(
            AccountCreated::class,
            fn (AccountCreated $mail): bool => $mail->hasTo('welcome@akabust.studio')
                && $mail->user->is($user),
        );
    }

    private function render(MailMessage $mail): string
    {
        return (string) view($mail->markdown, $mail->viewData)->render();
    }
}
