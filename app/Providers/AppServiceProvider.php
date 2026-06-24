<?php

namespace App\Providers;

use App\Models\Organization;
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
