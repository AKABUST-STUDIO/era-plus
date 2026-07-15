<?php

namespace App\Filament\User\Pages;

use App\Enums\OrganizationRole;
use App\Filament\Organization\Pages\Overview;
use App\Filament\Organization\Settings\Pages\GeneralSettings;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Organizations extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.user.pages.organizations';

    public function getTitle(): string
    {
        return __('user.organizations.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('user.organizations.create.heading'))
                ->description(__('user.organizations.create.description'))
                ->footerActions([
                    Action::make('create')
                        ->label(__('user.organizations.create.action'))
                        ->icon('lucide-plus')
                        ->url(fn (): ?string => Filament::getPanel('organization')->getTenantRegistrationUrl()),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Organization::query()
                ->whereHas('users', fn (Builder $query) => $query->whereKey(auth()->id())))
            ->columns([
                Split::make([
                    ImageColumn::make('avatar')
                        ->getStateUsing(fn (Organization $organization): string => $organization->getAvatarUrl())
                        ->circular()
                        ->grow(false),
                    Stack::make([
                        Split::make([
                            TextColumn::make('name')
                                ->weight('bold')
                                ->searchable()
                                ->sortable(),
                            TextColumn::make('subscription_tier')
                                ->badge()
                                ->grow(false),
                        ])->grow(false)->from('md'),
                        TextColumn::make('role')
                            ->state(fn (Organization $organization): string => $this->roleLabelFor($organization))
                            ->color('gray'),
                    ]),
                ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label(__('user.organizations.actions.view'))
                        ->icon('lucide-eye')
                        ->url(fn (Organization $organization): string => Overview::getUrl(panel: 'organization', tenant: $organization)),
                    Action::make('manage')
                        ->label(__('user.organizations.actions.manage'))
                        ->icon('lucide-settings')
                        ->url(fn (Organization $organization): string => GeneralSettings::getUrl(['organization' => $organization->slug], panel: 'organization.settings')),
                ]),
            ])
            ->defaultSort('name');
    }

    private function roleLabelFor(Organization $organization): string
    {
        $role = auth()->user()->roleFor($organization);
        $enum = $role !== null ? OrganizationRole::tryFrom($role->name) : null;

        if ($enum === OrganizationRole::Admin) {
            return __('user.organizations.role.owner');
        }

        return $enum?->getLabel() ?? __('user.organizations.role.member');
    }
}
