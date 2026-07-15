<?php

namespace App\Filament\User\Pages;

use App\Enums\OrganizationRole;
use App\Filament\Organization\Pages\Tenancy\RegisterOrganization;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
                        ->url(fn (): string => RegisterOrganization::getUrl()),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Organization::query()
                ->whereHas('users', fn (Builder $q) => $q->whereKey(auth()->id())))
            ->columns([
                Split::make([
                    ImageColumn::make('avatar')
                        ->getStateUsing(fn (Organization $r): string => $r->getAvatarUrl())
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
                            ->state(fn (Organization $r): string => $this->roleLabelFor($r))
                            ->color('gray'),
                    ]),
                ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label(__('user.organizations.actions.view'))
                        ->icon('lucide-eye')
                        ->url(fn (Organization $r): string => route('filament.organization.pages.dashboard', ['tenant' => $r->slug])),
                    Action::make('manage')
                        ->label(__('user.organizations.actions.manage'))
                        ->icon('lucide-settings')
                        ->url(fn (Organization $r): string => route('filament.organization-settings.pages.general', ['organization' => $r->slug])),
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
