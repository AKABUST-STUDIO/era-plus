<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Facades\OrganizationService;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Users extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'users';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.organization.settings.pages.users';

    public ?Organization $organization = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $inviteData = [];

    public static function getNavigationLabel(): string
    {
        return __('settings.users.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.users.title');
    }

    /**
     * @return array<int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            __('settings.breadcrumb'),
            __('settings.users.navigation_label'),
        ];
    }

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);

        $this->organization = $organization;

        $this->inviteForm->fill();
    }

    public function inviteForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('inviteData')
            ->components([
                Section::make(__('settings.users.invite.heading'))
                    ->description(__('settings.users.invite.description'))
                    ->schema([
                        TextInput::make('email')
                            ->label(__('settings.users.invite.email'))
                            ->email()
                            ->required(),
                    ])
                    ->footerActions([
                        Action::make('invite')
                            ->label(__('settings.users.invite.action'))
                            ->action(fn () => $this->invite()),
                    ]),
            ]);
    }

    public function invite(): void
    {
        $this->inviteForm->getState();

        Notification::make()
            ->title(__('settings.users.invite.wip'))
            ->warning()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): BelongsToMany => $this->organization->users())
            ->columns([
                TextColumn::make('name')
                    ->label(__('settings.users.table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('settings.users.table.email'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('pivot.created_at')
                    ->label(__('settings.users.table.joined'))
                    ->dateTime(),
            ]);
    }
}
