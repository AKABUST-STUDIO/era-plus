<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Providers\Filament\ProjectPanelProvider;
use Illuminate\Routing\Events\Routing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerCashier();
        $this->registerRouteBindings();
        $this->deprioritizeProjectTenantRoute();
        $this->registerSocialiteProviders();
    }

    protected function registerCashier(): void
    {
        Cashier::useCustomerModel(User::class);
        Cashier::useSubscriptionModel(Subscription::class);
    }

    protected function deprioritizeProjectTenantRoute(): void
    {
        Event::listen(Routing::class, function (): void {
            Route::getRoutes()
                ->getByName('filament.'.ProjectPanelProvider::PANEL_ID.'.tenant')
                ?->fallback();
        });
    }

    protected function registerRouteBindings(): void
    {
        Route::bind('organization', function (string $value): Organization {
            return Organization::where('slug', $value)->firstOrFail();
        });
    }

    protected function registerSocialiteProviders(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });
    }
}
