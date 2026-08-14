<?php

namespace App\Providers;

use App\Filament\Organization\Settings\Pages\Billing;
use App\Filament\Organization\Settings\Pages\BillingItems;
use App\Filament\Organization\Settings\Pages\Invoices;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\User;
use App\Policies\Organization\BillingPolicy;
use App\Policies\Organization\InvoicePolicy;
use App\Providers\Filament\ProjectPanelProvider;
use App\Services\GoogleCalendar\Contracts\CalendarClient;
use App\Services\GoogleCalendar\GoogleCalendarClient;
use App\Support\GoogleCalendarCredentials;
use Illuminate\Routing\Events\Routing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use SocialiteProviders\Apple\Provider as AppleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CalendarClient::class, fn (): CalendarClient => new GoogleCalendarClient);
    }

    public function boot(): void
    {
        $this->registerCashier();
        $this->registerPagePolicies();
        $this->registerRouteBindings();
        $this->deprioritizeProjectTenantRoute();
        $this->registerSocialiteProviders();

        GoogleCalendarCredentials::materialize();
    }

    protected function registerPagePolicies(): void
    {
        Gate::policy(Billing::class, BillingPolicy::class);
        Gate::policy(BillingItems::class, BillingPolicy::class);
        Gate::policy(Invoices::class, InvoicePolicy::class);
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

        Route::bind('project', function (string $value): Project {
            $project = (new Project)->resolveRouteBinding($value);

            abort_unless($project instanceof Project, 404);

            return $project;
        });
    }

    protected function registerSocialiteProviders(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('microsoft', MicrosoftProvider::class);
            $event->extendSocialite('apple', AppleProvider::class);
        });
    }
}
