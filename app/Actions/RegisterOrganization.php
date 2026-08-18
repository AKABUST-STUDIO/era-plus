<?php

namespace App\Actions;

use App\Enums\Organization\OrganizationRole;
use App\Enums\Subscription\SubscriptionTier;
use App\Models\Organization;
use App\Models\User;

class RegisterOrganization
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function handle(User $user, array $data): Organization
    {
        $subscription = $user
            ->newSubscription('default', SubscriptionTier::Pro->stripePriceId())
            ->create();

        $organization = Organization::create([
            'name' => $data['name'],
            'subscription_id' => $subscription->id,
        ]);

        $user->joinOrganization($organization, OrganizationRole::Admin);

        if ($user->default_organization_id === null) {
            $user->update(['default_organization_id' => $organization->id]);
        }

        return $organization;
    }
}
