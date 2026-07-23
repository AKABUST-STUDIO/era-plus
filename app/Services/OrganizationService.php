<?php

namespace App\Services;

use App\Enums\Subscription\SubscriptionTier;
use App\Filament\Organization\Pages\Overview;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class OrganizationService
{
    private const SESSION_KEY = 'selected_organization_id';

    public function current(): ?Organization
    {
        $tenant = Filament::getTenant();

        return match (true) {
            $tenant instanceof Organization => $tenant,
            $tenant instanceof Project => $tenant->organization,
            default => $this->selected(),
        };
    }

    /**
     * @return Collection<int, Organization>
     */
    public function organizationsFor(User $user): Collection
    {
        return $user
            ->organizations()
            ->orderBy('name')
            ->get();
    }

    public function urlFor(Organization $organization): string
    {
        return Overview::getUrl(['organization' => $organization->slug]);
    }

    public function remember(Organization $organization): void
    {
        session()->put(self::SESSION_KEY, $organization->id);
    }

    public function selected(): ?Organization
    {
        $id = session()->get(self::SESSION_KEY);

        return $id ? Organization::find($id) : null;
    }

    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function shouldShowUpgradeCta(): bool
    {
        $organization = $this->current();

        return $organization instanceof Organization
            && $organization->subscription_tier === SubscriptionTier::Basic;
    }
}
