<?php

namespace App\Filament\User\Pages;

use App\Filament\Organization\Settings\Pages\Billing as OrganizationBilling;
use App\Models\Organization;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Override;

class BillingItems extends Page
{
    protected static ?int $navigationSort = 51;

    protected string $view = 'filament.user.pages.billing-items';

    #[Override]
    public static function canAccess(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('user.billing.items.title');
    }

    public function getTitle(): string
    {
        return __('user.billing.items.title');
    }

    /**
     * @return Collection<int, Organization>
     */
    public function getItems(): Collection
    {
        return auth()->user()->subscriptions()
            ->with('organization')
            ->get()
            ->map(fn ($subscription): ?Organization => $subscription->organization)
            ->filter()
            ->unique(fn (Organization $organization): int => $organization->id)
            ->sortBy('name')
            ->values();
    }

    public function statusLabelFor(Organization $organization): string
    {
        return $organization->subscription?->stripe_status ?? 'active';
    }

    public function statusBadgeColorFor(Organization $organization): string
    {
        return match ($this->statusLabelFor($organization)) {
            'active' => 'success',
            'trialing' => 'info',
            'past_due', 'unpaid' => 'warning',
            'canceled', 'incomplete_expired' => 'danger',
            default => 'gray',
        };
    }

    public function organizationBillingUrl(Organization $organization): string
    {
        return OrganizationBilling::getUrl(['organization' => $organization->slug], panel: 'organization.settings');
    }
}
