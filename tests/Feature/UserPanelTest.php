<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class UserPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Maria', 'email' => 'maria@example.test']);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('user'));
    }

    public function test_dashboard_loads_for_any_logged_in_user(): void
    {
        Livewire::test(Dashboard::class)->assertSuccessful();
    }

    public function test_settings_page_renders_with_user_data(): void
    {
        Livewire::test(Settings::class)
            ->assertSuccessful()
            ->assertFormSet(['name' => 'Maria']);
    }

    public function test_settings_can_update_name(): void
    {
        Livewire::test(Settings::class)
            ->fillForm(['name' => 'Anne'])
            ->callAction(TestAction::make('saveProfile')->schemaComponent('profile-section', 'form'));

        $this->assertSame('Anne', $this->user->fresh()->name);
    }

    public function test_email_change_sends_confirmation_and_leaves_email_unchanged(): void
    {
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
    }

    public function test_email_change_normalizes_case(): void
    {
        Mail::fake();

        Livewire::test(Settings::class)
            ->fillForm(['email' => 'NEW@Example.Test'])
            ->callAction(TestAction::make('saveEmail')->schemaComponent('email-section', 'form'))
            ->assertHasNoFormErrors();

        Mail::assertQueued(EmailChangeConfirmation::class, fn (EmailChangeConfirmation $mail): bool => $mail->newEmail === 'new@example.test');
    }

    public function test_email_change_is_a_noop_when_email_is_unchanged(): void
    {
        Mail::fake();

        Livewire::test(Settings::class)
            ->fillForm(['email' => 'maria@example.test'])
            ->callAction(TestAction::make('saveEmail')->schemaComponent('email-section', 'form'))
            ->assertHasNoFormErrors();

        Mail::assertNothingQueued();
    }

    public function test_email_change_requires_a_value(): void
    {
        Mail::fake();

        Livewire::test(Settings::class)
            ->fillForm(['email' => ''])
            ->callAction(TestAction::make('saveEmail')->schemaComponent('email-section', 'form'))
            ->assertHasFormErrors(['email' => 'required']);

        Mail::assertNothingQueued();
        $this->assertSame('maria@example.test', $this->user->fresh()->email);
    }

    public function test_email_change_rejects_invalid_format(): void
    {
        Mail::fake();

        Livewire::test(Settings::class)
            ->fillForm(['email' => 'not-an-email'])
            ->callAction(TestAction::make('saveEmail')->schemaComponent('email-section', 'form'))
            ->assertHasFormErrors(['email' => 'email']);

        Mail::assertNothingQueued();
        $this->assertSame('maria@example.test', $this->user->fresh()->email);
    }

    public function test_email_change_rejects_address_already_in_use(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);
        Mail::fake();

        Livewire::test(Settings::class)
            ->fillForm(['email' => 'taken@example.test'])
            ->callAction(TestAction::make('saveEmail')->schemaComponent('email-section', 'form'))
            ->assertHasFormErrors(['email' => 'unique']);

        Mail::assertNothingQueued();
    }

    public function test_signed_confirmation_link_updates_the_email(): void
    {
        $url = $this->buildConfirmationUrl($this->user, 'new@example.test');

        $this->get($url)->assertRedirect(Settings::getUrl(panel: 'user'));

        $this->assertSame('new@example.test', $this->user->fresh()->email);
    }

    public function test_confirmation_link_without_valid_signature_is_rejected(): void
    {
        $url = route('settings.email.confirm', [
            'user' => $this->user->id,
            'email' => 'new@example.test',
        ]);

        $this->get($url)->assertForbidden();
        $this->assertSame('maria@example.test', $this->user->fresh()->email);
    }

    public function test_expired_confirmation_link_is_rejected(): void
    {
        $url = URL::temporarySignedRoute(
            'settings.email.confirm',
            now()->subMinute(),
            ['user' => $this->user->id, 'email' => 'new@example.test'],
        );

        $this->get($url)->assertForbidden();
        $this->assertSame('maria@example.test', $this->user->fresh()->email);
    }

    public function test_tampered_email_parameter_is_rejected(): void
    {
        $url = $this->buildConfirmationUrl($this->user, 'new@example.test');
        $tampered = str_replace('new%40example.test', 'attacker%40example.test', $url);

        $this->get($tampered)->assertForbidden();
        $this->assertSame('maria@example.test', $this->user->fresh()->email);
    }

    public function test_confirmation_link_requires_login_as_the_requesting_user(): void
    {
        $intruder = User::factory()->create();
        $url = $this->buildConfirmationUrl($this->user, 'new@example.test');

        $this->actingAs($intruder);
        $this->get($url)->assertForbidden();

        $this->assertSame('maria@example.test', $this->user->fresh()->email);
    }

    public function test_confirmation_link_redirects_to_login_when_unauthenticated(): void
    {
        auth()->logout();
        $url = $this->buildConfirmationUrl($this->user, 'new@example.test');

        $this->get($url)->assertRedirect();
        $this->assertSame('maria@example.test', $this->user->fresh()->email);
    }

    public function test_confirmation_link_fails_if_new_email_was_taken_meanwhile(): void
    {
        $url = $this->buildConfirmationUrl($this->user, 'new@example.test');
        User::factory()->create(['email' => 'new@example.test']);

        $this->get($url)->assertRedirect(Settings::getUrl(panel: 'user'));

        $this->assertSame('maria@example.test', $this->user->fresh()->email);
    }

    private function buildConfirmationUrl(User $user, string $newEmail): string
    {
        return URL::temporarySignedRoute(
            'settings.email.confirm',
            now()->addMinutes(10),
            ['user' => $user->id, 'email' => $newEmail],
        );
    }

    public function test_settings_can_set_default_organization(): void
    {
        $org = Organization::factory()->create();
        $this->user->joinOrganization($org);

        Livewire::test(Settings::class)
            ->fillForm(['default_organization_id' => $org->id])
            ->callAction(TestAction::make('saveDefaultOrganization')->schemaComponent('default-org-section', 'form'));

        $this->assertSame($org->id, $this->user->fresh()->default_organization_id);
    }

    public function test_each_wip_page_renders(): void
    {
        foreach ([Activity::class, BillingInformation::class, BillingItems::class, Invoices::class] as $page) {
            Livewire::test($page)->assertSuccessful();
        }
    }

    public function test_activity_page_scopes_to_current_user_as_causer(): void
    {
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
    }
}
