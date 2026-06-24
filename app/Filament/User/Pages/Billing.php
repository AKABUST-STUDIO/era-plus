<?php

namespace App\Filament\User\Pages;

use App\Enums\SubscriptionTier;
use App\Models\Organization;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

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
        /** @var Collection<int, Organization> $orgs */
        $orgs = Organization::query()
            ->whereHas('users', fn ($q) => $q
                ->whereKey(auth()->id())
                ->where('organization_user.is_admin', true))
            ->orderBy('name')
            ->get();

        return $orgs;
    }

    public function manage(int $organizationId): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $organization = $this->ownedOrgOrFail($organizationId);

        return redirect()->away(
            $organization->billingPortalUrl(static::getUrl())
        );
    }

    public function upgrade(int $organizationId, string $plan): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $organization = $this->ownedOrgOrFail($organizationId);

        $priceId = match ($plan) {
            'pro' => config('services.stripe.prices.pro'),
            'premium' => config('services.stripe.prices.premium'),
            default => null,
        };

        abort_unless($priceId, 400, 'Stripe price ID not configured for plan '.$plan);

        $checkout = $organization
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => static::getUrl(),
                'cancel_url' => static::getUrl(),
            ]);

        return redirect()->away($checkout->url);
    }

    public function planLabel(Organization $organization): string
    {
        return $organization->subscription_tier?->getLabel() ?? SubscriptionTier::Free->getLabel();
    }

    public function isFreeTier(Organization $organization): bool
    {
        return $organization->subscription_tier === SubscriptionTier::Free;
    }

    public function cardLabel(Organization $organization): string
    {
        if (! $organization->pm_last_four) {
            return __('user.billing.no_payment_method');
        }

        return sprintf('%s …%s', ucfirst((string) $organization->pm_type), $organization->pm_last_four);
    }

    private function ownedOrgOrFail(int $organizationId): Organization
    {
        $organization = Organization::query()->findOrFail($organizationId);

        abort_unless(auth()->user()?->isOrgAdmin($organization), 403);

        return $organization;
    }
}
