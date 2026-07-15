<?php

namespace App\Filament\User\Pages;

use App\Enums\SubscriptionTier;
use App\Models\Organization;
use App\Models\Subscription;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Billing extends Page
{
    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.user.pages.billing';

    public function getTitle(): string
    {
        return __('user.billing.title');
    }

    /**
     * @return Collection<int, Organization>
     */
    public function getOwnedOrganizations(): Collection
    {
        return auth()->user()->subscriptions()
            ->with('organization')
            ->get()
            ->map(fn (Subscription $subscription): ?Organization => $subscription->organization)
            ->filter()
            ->unique(fn (Organization $organization): int => $organization->id)
            ->sortBy('name')
            ->values();
    }

    public function hasBillingAccount(): bool
    {
        return (bool) auth()->user()->hasStripeId();
    }

    public function manage(int $organizationId): RedirectResponse
    {
        $this->ownedOrgOrFail($organizationId);

        return auth()->user()->redirectToBillingPortal(static::getUrl());
    }

    public function upgrade(int $organizationId, string $plan): RedirectResponse
    {
        $organization = $this->ownedOrgOrFail($organizationId);

        $priceId = match ($plan) {
            'pro' => config('services.stripe.prices.pro'),
            default => null,
        };

        abort_unless($priceId, 400, 'Stripe price ID not configured for plan '.$plan);

        $checkout = auth()->user()
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => static::getUrl(),
                'cancel_url' => static::getUrl(),
                'metadata' => ['organization_id' => $organization->id],
            ]);

        return redirect()->away($checkout->url);
    }

    public function planLabel(Organization $organization): string
    {
        return $organization->subscription_tier?->getLabel() ?? SubscriptionTier::Basic->getLabel();
    }

    public function isBasicTier(Organization $organization): bool
    {
        return $organization->subscription_tier === SubscriptionTier::Basic;
    }

    public function cardLabel(): string
    {
        $user = auth()->user();

        if (! $user->pm_last_four) {
            return __('user.billing.no_payment_method');
        }

        return sprintf('%s …%s', ucfirst((string) $user->pm_type), $user->pm_last_four);
    }

    private function ownedOrgOrFail(int $organizationId): Organization
    {
        $organization = Organization::query()->findOrFail($organizationId);

        abort_unless(auth()->user()?->isOrgAdmin($organization), 403);

        return $organization;
    }
}
