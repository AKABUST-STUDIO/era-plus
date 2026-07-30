<?php

namespace App\Filament\User\Pages;

use App\Filament\Tables\ActivityLogTable;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Activity extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.user.pages.activity';

    public function getTitle(): string
    {
        return __('user.activity.title');
    }

    public function table(Table $table): Table
    {
        return ActivityLogTable::configure(
            $table,
            ActivityLog::query()
                ->where('causer_type', User::class)
                ->where('causer_id', auth()->id()),
        );
    }
}
