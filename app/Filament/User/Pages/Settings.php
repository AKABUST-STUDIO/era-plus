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
        return __('user.settings.title');
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
        return Section::make(__('forms.user.settings.avatar_heading'))
            ->description(__('forms.user.settings.avatar_description'))
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
                Action::make('saveAvatar')->label(__('forms.user.settings.save_avatar'))->action(fn () => $this->saveAvatar()),
            ]);
    }

    protected function profileSection(): Section
    {
        return Section::make(__('forms.user.settings.profile_heading'))
            ->description(__('forms.user.settings.profile_description'))
            ->schema([
                TextInput::make('name')->label(__('forms.user.settings.display_name'))->required()->maxLength(255),
            ])
            ->footerActions([
                Action::make('saveProfile')->label(__('forms.user.settings.save_profile'))->action(fn () => $this->saveProfile()),
            ]);
    }

    protected function emailSection(): Section
    {
        return Section::make(__('forms.user.settings.email_heading'))
            ->description(__('forms.user.settings.email_description'))
            ->schema([
                TextInput::make('email')->email()->required()->maxLength(255),
            ])
            ->footerActions([
                Action::make('saveEmail')->label(__('forms.user.settings.save_email'))->action(fn () => $this->saveEmail()),
            ]);
    }

    protected function defaultOrganizationSection(): Section
    {
        return Section::make(__('forms.user.settings.default_org_heading'))
            ->description(__('forms.user.settings.default_org_description'))
            ->schema([
                Select::make('default_organization_id')
                    ->label(__('forms.user.settings.default_org'))
                    ->options(fn (): array => $this->user
                        ->organizations()
                        ->orderBy('name')
                        ->pluck('name', 'organizations.id')
                        ->all())
                    ->placeholder(__('forms.user.settings.default_org_placeholder')),
            ])
            ->footerActions([
                Action::make('saveDefaultOrganization')
                    ->label(__('forms.user.settings.save_default_org'))
                    ->action(fn () => $this->saveDefaultOrganization()),
            ]);
    }

    protected function deleteSection(): Section
    {
        return Section::make(__('forms.user.settings.delete_heading'))
            ->description(__('forms.user.settings.delete_description'))
            ->footerActions([
                Action::make('deleteAccount')
                    ->label(__('forms.user.settings.delete_account'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn () => $this->deleteAccount()),
            ]);
    }

    public function saveAvatar(): void
    {
        $this->form->getState();
        $this->form->saveRelationships();

        Notification::make()->title(__('notifications.avatar_saved'))->success()->send();
    }

    public function saveProfile(): void
    {
        $this->user->update(['name' => $this->form->getState()['name']]);

        Notification::make()->title(__('notifications.profile_saved'))->success()->send();
    }

    public function saveEmail(): void
    {
        $this->user->update(['email' => $this->form->getState()['email']]);

        Notification::make()->title(__('notifications.email_saved'))->success()->send();
    }

    public function saveDefaultOrganization(): void
    {
        $orgId = $this->form->getState()['default_organization_id'] ?? null;

        $this->user->update(['default_organization_id' => $orgId]);

        Notification::make()->title(__('notifications.default_org_saved'))->success()->send();
    }

    public function deleteAccount(): void
    {
        $user = $this->user;

        auth()->logout();
        $user->delete();

        $this->redirect('/');
    }
}
