<?php

namespace App\Filament\Organization\Pages\Tenancy;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterOrganization extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register organization';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
            ]);
    }

    protected function handleRegistration(array $data): Organization
    {
        $organization = Organization::create($data);

        $organization->users()->attach(auth()->user(), [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);

        return $organization;
    }
}
