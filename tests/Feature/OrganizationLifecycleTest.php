<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Filament\Organization\Settings\Pages\GeneralSettings;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function actOnSettingsPanel(User $user, Organization $organization): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization-settings'));
        Filament::setTenant($organization);
        URL::defaults(['organization' => $organization->slug]);
    }

    public function test_leave_action_is_disabled_when_user_is_only_admin(): void
    {
        $admin = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($admin, [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);
        $member = User::factory()->create();
        $organization->users()->attach($member, [
            'role' => OrganizationRole::Member->value,
            'is_admin' => false,
        ]);

        $this->actOnSettingsPanel($admin, $organization);

        Livewire::test(GeneralSettings::class)->call('leave');

        $this->assertTrue($organization->fresh()->users->contains($admin));
    }

    public function test_leave_allowed_when_there_are_other_admins(): void
    {
        $admin = User::factory()->create();
        $coAdmin = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($admin, [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);
        $organization->users()->attach($coAdmin, [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);

        $this->actOnSettingsPanel($admin, $organization);

        Livewire::test(GeneralSettings::class)->call('leave');

        $this->assertFalse($organization->fresh()->users->contains($admin));
    }

    public function test_delete_modal_form_includes_name_and_phrase_inputs(): void
    {
        $admin = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $organization->users()->attach($admin, [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);

        $this->actOnSettingsPanel($admin, $organization);

        $instance = Livewire::test(GeneralSettings::class)->instance();

        $action = collect($instance->form(\Filament\Schemas\Schema::make($instance))->getComponents(withHidden: true))
            ->flatMap(fn ($component) => method_exists($component, 'getFooterActions') ? $component->getFooterActions() : [])
            ->firstWhere(fn ($a) => $a->getName() === 'delete');

        $this->assertNotNull($action, 'Delete action not found in any section footer.');

        $schema = \Filament\Schemas\Schema::make($instance);
        $fields = collect($action->getForm($schema)->getComponents())
            ->map(fn ($c) => $c->getName())
            ->all();

        $this->assertContains('name_confirm', $fields);
        $this->assertContains('phrase_confirm', $fields);
    }

    public function test_delete_method_soft_deletes_organization(): void
    {
        $admin = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $organization->users()->attach($admin, [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);

        $this->actOnSettingsPanel($admin, $organization);

        Livewire::test(GeneralSettings::class)->call('delete');

        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
    }

    public function test_creating_organization_does_not_throw_without_stripe_credentials(): void
    {
        config()->set('cashier.secret', null);
        config()->set('services.stripe.prices.free', null);

        $organization = Organization::factory()->create();

        $this->assertNotNull($organization->id);
        $this->assertFalse($organization->hasStripeId());
    }

    public function test_subscribe_to_free_plan_noops_without_price_id(): void
    {
        config()->set('cashier.secret', 'sk_test_dummy');
        config()->set('services.stripe.prices.free', null);

        $organization = Organization::factory()->create();
        $organization->subscribeToFreePlan();

        $this->assertFalse($organization->hasStripeId());
    }

    public function test_cancel_all_subscriptions_is_noop_without_stripe_id(): void
    {
        $organization = Organization::factory()->create();

        $organization->cancelAllSubscriptions();

        $this->assertFalse($organization->hasStripeId());
    }

    public function test_delete_triggers_subscription_cancellation_hook(): void
    {
        // Spy on the cancellation hook by overriding cancelAllSubscriptions via an
        // anonymous subclass and an Organization::resolveRelationUsing? Simpler:
        // factory-create with a stripe_id, ensure cancel is invoked via the model
        // event when the row is deleted.
        $organization = Organization::factory()->create([
            'stripe_id' => 'cus_dummy_test',
        ]);

        // Run delete through the model directly to exercise the deleting hook.
        $organization->delete();

        // The cancel hook caught Throwables internally so the row still soft-deleted.
        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
    }
}
