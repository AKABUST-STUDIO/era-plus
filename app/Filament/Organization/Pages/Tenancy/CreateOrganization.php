<?php

namespace App\Filament\Organization\Pages\Tenancy;

use App\Actions\RegisterOrganization;
use App\Filament\Organization\Schemas\OrganizationForm;
use App\Filament\Resources\Projects\Pages\CreateProject;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateOrganization extends RegisterTenant
{
    public ?array $data = [];

    public static function getLabel(): string
    {
        return __('organization.register.label');
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
