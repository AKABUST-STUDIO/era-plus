<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Settings\Pages\OrganizationSettings;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
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
        $admin->joinOrganization($organization, OrganizationRole::Admin);
        $member = User::factory()->create();
        $member->joinOrganization($organization, OrganizationRole::Member);

        $this->actOnSettingsPanel($admin, $organization);

        Livewire::test(OrganizationSettings::class)->callAction(TestAction::make('leave')->schemaComponent('leave-section', 'form'));

        $this->assertTrue($organization->fresh()->users->contains($admin));
    }

    public function test_leave_allowed_when_there_are_other_admins(): void
    {
        $admin = User::factory()->create();
        $coAdmin = User::factory()->create();
        $organization = Organization::factory()->create();
        $admin->joinOrganization($organization, OrganizationRole::Admin);
        $coAdmin->joinOrganization($organization, OrganizationRole::Admin);

        $this->actOnSettingsPanel($admin, $organization);

        Livewire::test(OrganizationSettings::class)->callAction(TestAction::make('leave')->schemaComponent('leave-section', 'form'));

        $this->assertFalse($organization->fresh()->users->contains($admin));
    }

    public function test_delete_modal_form_includes_name_and_phrase_inputs(): void
    {
        $admin = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $admin->joinOrganization($organization, OrganizationRole::Admin);

        $this->actOnSettingsPanel($admin, $organization);

        $instance = Livewire::test(OrganizationSettings::class)->instance();

        $action = collect($instance->form(Schema::make($instance))->getComponents(withHidden: true))
            ->flatMap(fn ($component) => method_exists($component, 'getFooterActions') ? $component->getFooterActions() : [])
            ->firstWhere(fn ($a) => $a->getName() === 'delete');

        $this->assertNotNull($action, 'Delete action not found in any section footer.');

        $schema = Schema::make($instance);
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
        $admin->joinOrganization($organization, OrganizationRole::Admin);

        $this->actOnSettingsPanel($admin, $organization);

        Livewire::test(OrganizationSettings::class)
            ->callAction(TestAction::make('delete')->schemaComponent('delete-section', 'form'), data: [
                'name_confirm' => 'Acme Corp',
                'phrase_confirm' => __('settings.general.delete.confirm_phrase'),
            ]);

        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
    }
}
