<?php

namespace App\Filament\Organization\Pages\Tenancy;

use App\Enums\OrganizationRole;
use App\Enums\SubscriptionTier;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Models\Organization;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class RegisterOrganization extends RegisterTenant
{
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'subscription_tier' => SubscriptionTier::Basic->value,
        ]);
    }

    public static function getLabel(): string
    {
        return __('organization.register.label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Radio::make('subscription_tier')
                    ->hiddenLabel()
                    ->options([
                        SubscriptionTier::Trial->value => __('organization.register.plans.trial.label'),
                        SubscriptionTier::Basic->value => __('organization.register.plans.basic.label'),
                    ])
                    ->reactive()
                    ->descriptions([
                        SubscriptionTier::Trial->value => __('organization.register.plans.trial.description'),
                        SubscriptionTier::Basic->value => __('organization.register.plans.basic.description'),
                    ])
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                TextEntry::make('trial_info')
                    ->label(__('organization.register.info'))
                    ->visible(fn ($get) => $get('subscription_tier') === SubscriptionTier::Trial->value),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $user = auth()->user();
        $tier = $data['subscription_tier'] instanceof SubscriptionTier
            ? $data['subscription_tier']
            : SubscriptionTier::from($data['subscription_tier']);

        $subscriptionId = app()->environment('testing')
            ? null
            : $user->newSubscription('default', $tier->stripePriceId())->create()->id;

        $organization = Organization::create([
            'name' => $data['name'],
            'subscription_id' => $subscriptionId,
        ]);

        $user->joinOrganization($organization, OrganizationRole::Admin);

        if ($user->default_organization_id === null) {
            $user->update(['default_organization_id' => $organization->id]);
        }

        Notification::make()
            ->title(__('notifications.organization_created'))
            ->success()
            ->send();

        return $organization;
    }

    protected function getRedirectUrl(): ?string
    {
        return CreateProject::getUrl(tenant: $this->tenant);
    }
}
