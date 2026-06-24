<?php

namespace App\Filament\User\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Settings extends Page
{
    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.user.pages.settings';

    public ?User $user = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->user = auth()->user();

        $this->form->fill($this->user->only(['name', 'email', 'default_organization_id']));
        $this->form->loadStateFromRelationships(shouldHydrate: true);
    }

    public function getTitle(): string
    {
        return 'Settings';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->record($this->user)
            ->statePath('data')
            ->components([
                $this->avatarSection(),
                $this->profileSection(),
                $this->emailSection(),
                $this->defaultOrganizationSection(),
                $this->deleteSection(),
            ]);
    }

    protected function avatarSection(): Section
    {
        return Section::make('Avatar')
            ->description('Your face across organizations.')
            ->schema([
                SpatieMediaLibraryFileUpload::make('avatar')
                    ->hiddenLabel()
                    ->collection('avatar')
                    ->conversion('thumb')
                    ->avatar()
                    ->image()
                    ->imageEditor()
                    ->circleCropper(),
            ])
            ->footerActions([
                Action::make('saveAvatar')->label('Save avatar')->action(fn () => $this->saveAvatar()),
            ]);
    }

    protected function profileSection(): Section
    {
        return Section::make('Profile')
            ->description('How others see you across organizations.')
            ->schema([
                TextInput::make('name')->label('Display name')->required()->maxLength(255),
            ])
            ->footerActions([
                Action::make('saveProfile')->label('Save profile')->action(fn () => $this->saveProfile()),
            ]);
    }

    protected function emailSection(): Section
    {
        return Section::make('Email')
            ->description('Primary contact and sign-in address.')
            ->schema([
                TextInput::make('email')->email()->required()->maxLength(255),
            ])
            ->footerActions([
                Action::make('saveEmail')->label('Save email')->action(fn () => $this->saveEmail()),
            ]);
    }

    protected function defaultOrganizationSection(): Section
    {
        return Section::make('Default organization')
            ->description('Where you land after sign-in.')
            ->schema([
                Select::make('default_organization_id')
                    ->label('Default organization')
                    ->options(fn (): array => $this->user
                        ->organizations()
                        ->orderBy('name')
                        ->pluck('name', 'organizations.id')
                        ->all())
                    ->placeholder('Pick on sign-in'),
            ])
            ->footerActions([
                Action::make('saveDefaultOrganization')
                    ->label('Save default organization')
                    ->action(fn () => $this->saveDefaultOrganization()),
            ]);
    }

    protected function deleteSection(): Section
    {
        return Section::make('Delete account')
            ->description('Permanently remove your account. Cannot be undone.')
            ->footerActions([
                Action::make('deleteAccount')
                    ->label('Delete account')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn () => $this->deleteAccount()),
            ]);
    }

    public function saveAvatar(): void
    {
        $this->form->getState();
        $this->form->saveRelationships();

        Notification::make()->title('Avatar saved')->success()->send();
    }

    public function saveProfile(): void
    {
        $this->user->update(['name' => $this->form->getState()['name']]);

        Notification::make()->title('Profile saved')->success()->send();
    }

    public function saveEmail(): void
    {
        $this->user->update(['email' => $this->form->getState()['email']]);

        Notification::make()->title('Email saved')->success()->send();
    }

    public function saveDefaultOrganization(): void
    {
        $orgId = $this->form->getState()['default_organization_id'] ?? null;

        $this->user->update(['default_organization_id' => $orgId]);

        Notification::make()->title('Default organization saved')->success()->send();
    }

    public function deleteAccount(): void
    {
        $user = $this->user;

        auth()->logout();
        $user->delete();

        $this->redirect('/');
    }
}
