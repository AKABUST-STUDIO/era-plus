<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class OrganizationService
{
    private const SESSION_KEY = 'selected_organization_id';

    /**
     * The organization in context: the current panel's tenant when one exists
     * (Organization directly, or a Project's organization), otherwise the
     * session-selected organization for panels that are not tenant-scoped.
     */
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
        return route('filament.'.OrganizationPanelProvider::PANEL_ID.'.pages.dashboard', [
            'tenant' => $organization->slug,
        ]);
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
}
