<?php

namespace App\Filament\User\Pages;

use App\Models\Organization;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserOrganizations extends Page implements HasTable
{
    use InteractsWithHeaderActions;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.user.pages.user-organizations';

    public function getTitle(): string
    {
        return 'Organizations';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create organization')
                ->icon('heroicon-o-plus')
                ->url(fn (): string => url('/new-organization')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Organization::query()
                ->whereHas('users', fn (Builder $q) => $q->whereKey(auth()->id())))
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->toggleable(),
                TextColumn::make('subscription_tier')->badge(),
                TextColumn::make('created_at')->date()->sortable()->toggleable(),
            ])
            ->defaultSort('name');
    }
}
