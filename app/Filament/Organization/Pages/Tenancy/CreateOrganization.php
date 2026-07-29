<?php

namespace App\Filament\Organization\Pages\Tenancy;

use App\Actions\RegisterOrganization;
use App\Enums\Subscription\SubscriptionTier;
use App\Filament\Organization\Schemas\OrganizationForm;
use App\Filament\Resources\Projects\Pages\CreateProject;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Override;

class CreateOrganization extends RegisterTenant
{
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'subscription_tier' => SubscriptionTier::Trial->value,
        ]);
    }

    public static function getLabel(): string
    {
        return __('organization.register.label');
    }

    #[Override]
    public function getRegisterFormAction(): Action
    {
        return parent::getRegisterFormAction()
            ->label(__('organization.register.action'));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components(OrganizationForm::fields());
    }

    protected function handleRegistration(array $data): Model
    {
        $organization = RegisterOrganization::handle(auth()->user(), $data);

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
