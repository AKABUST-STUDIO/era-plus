<?php

namespace App\Filament\Organization\Pages;

use App\Facades\OrganizationService;
use App\Filament\Tables\ActivityLogTable;
use App\Models\ActivityLog;
use App\Models\Organization;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Activity extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'activity';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.organization.pages.activity';

    public ?Organization $organization = null;

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->can('view', ActivityLog::class) ?? false;
    }

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);

        $this->organization = $organization;
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.activity.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.activity.title');
    }

    public function table(Table $table): Table
    {
        return ActivityLogTable::configure(
            $table,
            ActivityLog::query()->where('organization_id', $this->organization->id),
        );
    }
}
