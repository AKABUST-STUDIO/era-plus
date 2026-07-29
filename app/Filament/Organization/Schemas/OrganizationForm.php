<?php

namespace App\Filament\Organization\Schemas;

use App\Enums\Subscription\SubscriptionTier;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::fields());
    }

    /**
     * @return array<int, Component>
     */
    public static function fields(): array
    {
        return [
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
        ];
    }
}
