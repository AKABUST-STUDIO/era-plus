<?php

use App\Filament\User\Pages\Activity;
use App\Filament\User\Pages\BillingInformation;
use App\Filament\User\Pages\BillingItems;
use App\Filament\User\Pages\Invoices;
use App\Filament\User\Pages\Settings;
use App\Mail\EmailChangeConfirmation;
use App\Mail\EmailChangeRequestedNotice;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create(['name' => 'Maria', 'email' => 'maria@example.test']);

    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('user'));
});

function buildConfirmationUrl(User $user, string $newEmail): string
{
    return URL::temporarySignedRoute(
        'settings.email.confirm',
        now()->addMinutes(10),
        ['user' => $user->id, 'email' => $newEmail],
    );
}

test('dashboard loads for any logged in user', function (): void {
    Livewire::test(Dashboard::class)->assertSuccessful();
});

test('settings page renders with user data', function (): void {
    Livewire::test(Settings::class)
        ->assertSuccessful()
        ->assertFormSet(['name' => 'Maria']);
});

test('settings can update name', function (): void {
    Livewire::test(Settings::class)
        ->fillForm(['name' => 'Anne'])
        ->callAction(TestAction::make('saveProfile')->schemaComponent('profile-section', 'form'));

    $this->assertSame('Anne', $this->user->fresh()->name);
});

test('email change sends confirmation and leaves email unchanged', function (): void {
    Mail::fake();

    Livewire::test(Settings::class)
        ->fillForm(['email' => 'new@example.test'])
        ->callAction(TestAction::make('saveEmail')->schemaComponent('email-section', 'form'))
        ->assertHasNoFormErrors()
        ->assertFormSet(['email' => 'maria@example.test']);

    $this->assertSame('maria@example.test', $this->user->fresh()->email);

    Mail::assertQueued(EmailChangeConfirmation::class, fn (EmailChangeConfirmation $mail): bool => $mail->hasTo('new@example.test')
        && $mail->newEmail === 'new@example.test'
        && $mail->user->is($this->user));

    Mail::assertQueued(EmailChangeRequestedNotice::class, fn (EmailChangeRequestedNotice $mail): bool => $mail->hasTo('maria@example.test')
        && $mail->newEmail === 'new@example.test');
});

test('email change normalizes case', function (): void {
    Mail::fake();

    Livewire::test(Settings::class)
        ->fillForm(['email' => 'NEW@Example.Test'])
        ->callAction(TestAction::make('saveEmail')->schemaComponent('email-section', 'form'))
        ->assertHasNoFormErrors();

    Mail::assertQueued(EmailChangeConfirmation::class, fn (EmailChangeConfirmation $mail): bool => $mail->newEmail === 'new@example.test');
});

test('email change is a noop when email is unchanged', function (): void {
    Mail::fake();

    Livewire::test(Settings::class)
        ->fillForm(['email' => 'maria@example.test'])
        ->callAction(TestAction::make('saveEmail')->schemaComponent('email-section', 'form'))
        ->assertHasNoFormErrors();

    Mail::assertNothingQueued();
});

test('email change requires a value', function (): void {
    Mail::fake();

    Livewire::test(Settings::class)
        ->fillForm(['email' => ''])
        ->callAction(TestAction::make('saveEmail')->schemaComponent('email-section', 'form'))
        ->assertHasFormErrors(['email' => 'required']);

    Mail::assertNothingQueued();
    $this->assertSame('maria@example.test', $this->user->fresh()->email);
});

test('email change rejects invalid format', function (): void {
    Mail::fake();

    Livewire::test(Settings::class)
        ->fillForm(['email' => 'not-an-email'])
        ->callAction(TestAction::make('saveEmail')->schemaComponent('email-section', 'form'))
        ->assertHasFormErrors(['email' => 'email']);

    Mail::assertNothingQueued();
    $this->assertSame('maria@example.test', $this->user->fresh()->email);
});

test('email change rejects address already in use', function (): void {
    User::factory()->create(['email' => 'taken@example.test']);
    Mail::fake();

    Livewire::test(Settings::class)
        ->fillForm(['email' => 'taken@example.test'])
        ->callAction(TestAction::make('saveEmail')->schemaComponent('email-section', 'form'))
        ->assertHasFormErrors(['email' => 'unique']);

    Mail::assertNothingQueued();
});

test('signed confirmation link updates the email', function (): void {
    $url = buildConfirmationUrl($this->user, 'new@example.test');

    $this->get($url)->assertRedirect(Settings::getUrl(panel: 'user'));

    $this->assertSame('new@example.test', $this->user->fresh()->email);
});

test('confirmation link without valid signature is rejected', function (): void {
    $url = route('settings.email.confirm', [
        'user' => $this->user->id,
        'email' => 'new@example.test',
    ]);

    $this->get($url)->assertForbidden();
    $this->assertSame('maria@example.test', $this->user->fresh()->email);
});

test('expired confirmation link is rejected', function (): void {
    $url = URL::temporarySignedRoute(
        'settings.email.confirm',
        now()->subMinute(),
        ['user' => $this->user->id, 'email' => 'new@example.test'],
    );

    $this->get($url)->assertForbidden();
    $this->assertSame('maria@example.test', $this->user->fresh()->email);
});

test('tampered email parameter is rejected', function (): void {
    $url = buildConfirmationUrl($this->user, 'new@example.test');
    $tampered = str_replace('new%40example.test', 'attacker%40example.test', $url);

    $this->get($tampered)->assertForbidden();
    $this->assertSame('maria@example.test', $this->user->fresh()->email);
});

test('confirmation link requires login as the requesting user', function (): void {
    $intruder = User::factory()->create();
    $url = buildConfirmationUrl($this->user, 'new@example.test');

    $this->actingAs($intruder);
    $this->get($url)->assertForbidden();

    $this->assertSame('maria@example.test', $this->user->fresh()->email);
});

test('confirmation link redirects to login when unauthenticated', function (): void {
    auth()->logout();
    $url = buildConfirmationUrl($this->user, 'new@example.test');

    $this->get($url)->assertRedirect();
    $this->assertSame('maria@example.test', $this->user->fresh()->email);
});

test('confirmation link fails if new email was taken meanwhile', function (): void {
    $url = buildConfirmationUrl($this->user, 'new@example.test');
    User::factory()->create(['email' => 'new@example.test']);

    $this->get($url)->assertRedirect(Settings::getUrl(panel: 'user'));

    $this->assertSame('maria@example.test', $this->user->fresh()->email);
});

test('settings can set default organization', function (): void {
    $org = Organization::factory()->create();
    $this->user->joinOrganization($org);

    Livewire::test(Settings::class)
        ->fillForm(['default_organization_id' => $org->id])
        ->callAction(TestAction::make('saveDefaultOrganization')->schemaComponent('default-org-section', 'form'));

    $this->assertSame($org->id, $this->user->fresh()->default_organization_id);
});

test('each wip page renders', function (): void {
    $this->markTestSkipped('BillingInformation, BillingItems and Invoices user pages are intentionally hidden.');

    foreach ([Activity::class, BillingInformation::class, BillingItems::class, Invoices::class] as $page) {
        Livewire::test($page)->assertSuccessful();
    }
});

test('activity page scopes to current user as causer', function (): void {
    $stranger = User::factory()->create();
    $org = Organization::factory()->create();

    $this->actingAs($this->user);
    ActivityLog::record($org, 'Mine');

    $this->actingAs($stranger);
    ActivityLog::record($org, 'Not mine');

    $this->actingAs($this->user);

    Livewire::test(Activity::class)
        ->assertCanSeeTableRecords(
            ActivityLog::query()->where('causer_id', $this->user->id)->get(),
        )
        ->assertCanNotSeeTableRecords(
            ActivityLog::query()->where('causer_id', $stranger->id)->get(),
        );
});
