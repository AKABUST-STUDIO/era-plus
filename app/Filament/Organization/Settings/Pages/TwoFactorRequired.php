<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Facades\OrganizationService;
use App\Models\Organization;
use Filament\Pages\Page;

class TwoFactorRequired extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.organization.settings.pages.two-factor-required';

    public ?Organization $organization = null;

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);
        abort_unless($organization->enforce_two_factor, 404);

        $this->organization = $organization;
    }

    public function getTitle(): string
    {
        return __('settings.two_factor_required.title');
    }

    /**
     * @return array<int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [__('settings.breadcrumb'), __('settings.two_factor_required.title')];
    }
}
