<?php

namespace Tests\Feature;

use App\Enums\Organization\OrganizationRole;
use App\Filament\Organization\Settings\Pages\Billing;
use App\Filament\Organization\Settings\Pages\GeneralSettings;
use App\Filament\Organization\Settings\Pages\Invoices;
use App\Filament\Organization\Settings\Pages\Notifications;
use App\Filament\Organization\Settings\Pages\Security;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettingsBreadcrumbsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['name' => 'Acme Erasmus']);
        $this->user->joinOrganization($this->organization, OrganizationRole::Admin);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('organization-settings'));
        Filament::setTenant($this->organization);
        URL::defaults(['organization' => $this->organization->slug]);
    }

    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function pageProvider(): array
    {
        return [
            'general' => [GeneralSettings::class, 'General settings'],
            'notifications' => [Notifications::class, 'Notifications'],
            'security' => [Security::class, 'Security'],
            'billing' => [Billing::class, 'Billing'],
            'invoices' => [Invoices::class, 'Invoices'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_breadcrumbs_have_three_segments(string $page, string $expectedTitle): void
    {
        $breadcrumbs = Livewire::test($page)
            ->instance()
            ->getBreadcrumbs();

        $values = array_values($breadcrumbs);
        $this->assertCount(3, $values);
        $this->assertSame('Acme Erasmus', $values[0]);
        $this->assertSame('Settings', $values[1]);
        $this->assertSame($expectedTitle, $values[2]);
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function pageClassProvider(): array
    {
        return array_map(
            static fn (array $case): array => [$case[0]],
            static::pageProvider()
        );
    }

    #[DataProvider('pageClassProvider')]
    public function test_organization_segment_is_a_link_to_org_panel_dashboard(string $page): void
    {
        $breadcrumbs = Livewire::test($page)
            ->instance()
            ->getBreadcrumbs();

        $orgUrl = array_key_first($breadcrumbs);

        $this->assertNotNull($orgUrl);
        $this->assertSame(
            $orgUrl,
            Filament::getPanel('organization')->getUrl(tenant: $this->organization)
        );
    }
}
