<?php

use App\Mail\AccountCreated;
use App\Models\User;
use App\Notifications\AccountAlreadyExistsNotification;
use App\Notifications\LoginCodeNotification;
use App\Notifications\RegistrationCodeNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

function renderMail(MailMessage $mail): string
{
    return (string) view($mail->markdown, $mail->viewData)->render();
}

test('registering sends the registration code notification', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $user->sendInitialRegistrationMailable();

    Notification::assertSentTo($user, RegistrationCodeNotification::class);

    $this->assertSame(1, $user->oneTimePasswords()->count());
});

test('an existing account sends the already exists notification', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $user->sendAccountAlreadyExistsMailable();

    Notification::assertSentTo($user, AccountAlreadyExistsNotification::class);

    $this->assertSame(1, $user->oneTimePasswords()->count());
});

test('the registration mail carries the code and a valid magic link', function (): void {
    $user = User::factory()->create(['email' => 'newbie@akabust.studio']);
    $oneTimePassword = $user->createOneTimePassword();

    $mail = (new RegistrationCodeNotification($oneTimePassword))->toMail($user);

    $this->assertInstanceOf(MailMessage::class, $mail);
    $this->assertStringContainsString($oneTimePassword->password, $mail->subject);

    $rendered = renderMail($mail);

    $this->assertStringContainsString($oneTimePassword->password, $rendered);
    $this->assertStringContainsString('newbie@akabust.studio', $rendered);

    $this->get($mail->viewData['magicLinkUrl'])->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

test('the already exists mail names the address and signs the user in', function (): void {
    $user = User::factory()->create(['email' => 'returning@akabust.studio']);
    $oneTimePassword = $user->createOneTimePassword();

    $mail = (new AccountAlreadyExistsNotification($oneTimePassword))->toMail($user);

    $rendered = renderMail($mail);

    $this->assertStringContainsString('returning@akabust.studio', $rendered);
    $this->assertStringContainsString($oneTimePassword->password, $rendered);

    $this->get($mail->viewData['magicLinkUrl']);
    $this->assertAuthenticatedAs($user);
});

test('the notifications are queued on the email queue', function (): void {
    $user = User::factory()->create();
    $oneTimePassword = $user->createOneTimePassword();

        foreach ([LoginCodeNotification::class, RegistrationCodeNotification::class, AccountAlreadyExistsNotification::class] as $notification) {
            $this->assertSame(['mail' => config('queue.names.email')], (new $notification($oneTimePassword))->viaQueues());
        }
    }

test('no subject or body string is left untranslated', function (): void {
    $user = User::factory()->create();
    $oneTimePassword = $user->createOneTimePassword();

    foreach ([LoginCodeNotification::class, RegistrationCodeNotification::class, AccountAlreadyExistsNotification::class] as $notification) {
        $mail = (new $notification($oneTimePassword))->toMail($user);

        $this->assertStringNotContainsString('emails.', $mail->subject);
        $this->assertStringNotContainsString('emails.', renderMail($mail));
    }
});

test('the welcome mailable is queued to the user', function (): void {
    Mail::fake();

    $user = User::factory()->create(['email' => 'welcome@akabust.studio']);

    $user->sendWelcomeMailable();

    Mail::assertQueued(
        AccountCreated::class,
        fn (AccountCreated $mail): bool => $mail->hasTo('welcome@akabust.studio')
            && $mail->user->is($user),
    );
});
