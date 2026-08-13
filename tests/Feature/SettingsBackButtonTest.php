<?php

namespace Tests\Feature;

use App\Facades\OrganizationService;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettingsBackButtonTest extends TestCase
{
    use RefreshDatabase;

    private function render(): string
    {
        return view('filament.user.components.back')->render();
    }

    public function test_it_links_back_to_the_organization_overview(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->joinOrganization($organization);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
        OrganizationService::remember($organization);

        $html = $this->render();

        $this->assertStringContainsString(__('navigation.back'), $html);
        $this->assertStringContainsString(
            ProjectResource::getUrl('index', panel: 'organization', tenant: $organization),
            $html,
        );
    }

    public function test_it_renders_nothing_without_a_current_organization(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
        OrganizationService::forget();

        $this->assertSame('', trim($this->render()));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function panelProvider(): array
    {
        return [
            'organization settings' => ['organization.settings'],
            'project settings' => ['project.settings'],
            'user' => ['user'],
        ];
    }

    #[DataProvider('panelProvider')]
    public function test_the_back_navigation_item_offsets_its_label_past_the_icon(string $panel): void
    {
        $item = collect(Filament::getPanel($panel)->getNavigationItems())
            ->first(fn (NavigationItem $item): bool => $item->getLabel() === __('navigation.back'));

        $this->assertInstanceOf(NavigationItem::class, $item);
        $this->assertSame(
            '[&_.fi-sidebar-item-label]:me-9 [&_.fi-sidebar-item-label]:text-center',
            $item->getExtraAttributes()['class'] ?? null,
        );
    }
}
