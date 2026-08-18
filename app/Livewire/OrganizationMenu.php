<?php

namespace App\Livewire;

use App\Actions\RegisterOrganization;
use App\Facades\OrganizationService;
use App\Filament\Organization\Schemas\OrganizationForm;
use App\Filament\Panels\PanelPage;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrganizationMenu extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public string $page = '';

    public function mount(): void
    {
        $this->page = PanelPage::currentSlug();
    }

    public function createOrganizationAction(): Action
    {
        return Action::make('createOrganization')
            ->label(__('menu.organization.create'))
            ->modalHeading(__('menu.organization.create'))
            ->modalWidth(Width::Small)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->schema(OrganizationForm::innerFields())
            ->action(function (array $data): void {
                $organization = RegisterOrganization::handle(auth()->user(), $data);

                Notification::make()
                    ->title(__('notifications.organization_created'))
                    ->success()
                    ->send();

                $this->redirect(OrganizationService::urlFor($organization), navigate: true);
            });
    }

    public function render(): View
    {
        $user = auth()->user();
        $currentOrganization = OrganizationService::current();

        if (! $user instanceof User || ! $currentOrganization instanceof Organization) {
            return view('livewire.organization-menu', [
                'currentOrganization' => null,
                'organizationUrl' => null,
                'items' => collect(),
                'canCreate' => false,
            ]);
        }

        $items = OrganizationService::organizationsFor($user)->map(fn (Organization $organization) => [
            'name' => $organization->name,
            'url' => OrganizationService::urlFor($organization, $this->page),
            'image' => $organization->getAvatarUrl(),
            'isCurrent' => $organization->is($currentOrganization),
        ]);

        return view('livewire.organization-menu', [
            'currentOrganization' => $currentOrganization,
            'organizationUrl' => OrganizationService::urlFor($currentOrganization),
            'items' => $items,
            'canCreate' => true,
        ]);
    }
}
