<?php

namespace App\Filament\User\Pages;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
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
                        ->url(fn (): string => CreateOrganization::getUrl()),
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
                    SpatieMediaLibraryImageColumn::make('avatar')
                        ->collection('avatar')
                        ->conversion('thumb')
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
                Action::make('open')
                    ->label(__('user.organizations.actions.open'))
                    ->icon('lucide-arrow-right')
                    ->url(fn (Organization $r): string => route('filament.organization.pages.dashboard', ['tenant' => $r->slug])),
            ])
            ->defaultSort('name');
    }

    private function roleLabelFor(Organization $organization): string
    {
        $pivot = $organization->users()
            ->whereKey(auth()->id())
            ->first()?->pivot;

        if ($pivot?->is_admin) {
            return __('user.organizations.role.owner');
        }

        $role = OrganizationRole::tryFrom((string) $pivot?->role);

        return $role?->getLabel() ?? __('user.organizations.role.member');
    }
}
