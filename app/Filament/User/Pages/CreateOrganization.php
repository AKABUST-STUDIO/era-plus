<?php

namespace App\Filament\User\Pages;

use App\Enums\OrganizationRole;
use App\Enums\SubscriptionTier;
use App\Models\Organization;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class CreateOrganization extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.user.pages.create-organization';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'subscription_tier' => SubscriptionTier::Free->value,
        ]);
    }

    public function getTitle(): string
    {
        return 'New organization';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Wizard::make([
                    Step::make('Details')
                        ->description('Name your new organization.')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->autofocus(),
                        ]),
                    Step::make('Plan')
                        ->description('Pick a starting plan. You can upgrade later.')
                        ->schema([
                            Radio::make('subscription_tier')
                                ->label('Plan')
                                ->options(SubscriptionTier::class)
                                ->descriptions([
                                    SubscriptionTier::Free->value => 'One project. Best for trying things out.',
                                    SubscriptionTier::Pro->value => 'Unlimited projects, advanced reporting, priority support.',
                                    SubscriptionTier::Premium->value => 'Everything in Pro plus white-labeling, dedicated deployment, custom integrations.',
                                ])
                                ->default(SubscriptionTier::Free)
                                ->required(),
                        ]),
                ])
                    ->submitAction(view('filament.user.pages.create-organization-submit')),
            ]);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $organization = Organization::create([
            'name' => $data['name'],
            'subscription_tier' => $data['subscription_tier'] ?? SubscriptionTier::Free->value,
        ]);

        $organization->users()->attach(auth()->id(), [
            'role' => OrganizationRole::Admin->value,
            'is_admin' => true,
        ]);

        if (SchemaFacade::hasColumn('users', 'default_organization_id') && auth()->user()->default_organization_id === null) {
            auth()->user()->update(['default_organization_id' => $organization->id]);
        }

        Notification::make()
            ->title('Organization created')
            ->success()
            ->send();

        $this->redirect(
            \Filament\Facades\Filament::getPanel('organization')->getUrl(tenant: $organization) ?? '/'
        );
    }
}
