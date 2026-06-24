<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccess;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Cashier::useCustomerModel(Organization::class);

        $this->registerRouteBindings();
        $this->registerLivewireScriptRoute();
        $this->registerProjectAccessGates();

        // Org Admin (AKA-36) and Project Coordinator (AKA-53) bypass Shield's
        // generated permission checks. Falls through to project policies for
        // Leader / Participant.
        Gate::before(function (User $user, string $ability) {
            $tenant = \Filament\Facades\Filament::getTenant();
            $organization = match (true) {
                $tenant instanceof Organization => $tenant,
                $tenant instanceof Project => $tenant->organization,
                default => null,
            };

            if ($organization && $user->isOrgAdmin($organization)) {
                return true;
            }

            if ($tenant instanceof Project) {
                $role = app(ProjectAccess::class)->projectRole($user, $tenant);
                if ($role === \App\Enums\ProjectRole::Coordinator) {
                    return true;
                }
            }

            return null;
        });
    }

    protected function registerProjectAccessGates(): void
    {
        $service = app(ProjectAccess::class);

        $abilities = [
            ProjectAccess::ABILITY_VIEW_FINANCE,
            ProjectAccess::ABILITY_MANAGE_FINANCE,
            ProjectAccess::ABILITY_MANAGE_MEMBERS,
            ProjectAccess::ABILITY_MANAGE_PARTICIPANTS,
            ProjectAccess::ABILITY_MANAGE_TASKS,
            ProjectAccess::ABILITY_MANAGE_SETTINGS,
        ];

        foreach ($abilities as $ability) {
            Gate::define($ability, fn (User $user, Project $project) => $service->can($user, $ability, $project));
        }
    }

    protected function registerRouteBindings(): void
    {
        Route::bind('organization', function (string $value): Organization {
            return Organization::where('slug', $value)->firstOrFail();
        });
    }

    protected function registerLivewireScriptRoute(): void
    {
        Livewire::setScriptRoute(function ($handle) {
            $file = config('app.debug') ? 'livewire.js' : 'livewire.min.js';

            return Route::get(EndpointResolver::prefix().'/asset/'.$file, $handle);
        });
    }
}
