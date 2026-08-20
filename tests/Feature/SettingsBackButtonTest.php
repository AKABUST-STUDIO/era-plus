<?php

use App\Facades\OrganizationService;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;

function settingsBackRender(): string
{
    return view('filament.user.components.back')->render();
}

test('it links back to the organization overview', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->joinOrganization($organization);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
    OrganizationService::remember($organization);

    $html = settingsBackRender();

    $this->assertStringContainsString(__('navigation.back'), $html);
    $this->assertStringContainsString(
        ProjectResource::getUrl('index', panel: 'organization', tenant: $organization),
        $html,
    );
});

test('it renders nothing without a current organization', function (): void {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('organization.settings'));
    OrganizationService::forget();

    $this->assertSame('', trim(settingsBackRender()));
});

dataset('settings_back_panels', [
    'organization settings' => ['organization.settings'],
    'project settings' => ['project.settings'],
    'user' => ['user'],
]);

test('the back navigation item offsets its label past the icon', function (string $panel): void {
    $item = collect(Filament::getPanel($panel)->getNavigationItems())
        ->first(fn (NavigationItem $item): bool => $item->getLabel() === __('navigation.back'));

    $this->assertInstanceOf(NavigationItem::class, $item);
    $this->assertSame(
        '[&_.fi-sidebar-item-label]:me-9 [&_.fi-sidebar-item-label]:text-center',
        $item->getExtraAttributes()['class'] ?? null,
    );
})->with('settings_back_panels');
