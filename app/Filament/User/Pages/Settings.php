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
use Filament\Support\Enums\Alignment;

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

        $this->form->fill($this->user->only([
            'name',
            'username',
            'email',
            'phone',
            'default_organization_id',
        ]));
        $this->form->loadStateFromRelationships(shouldHydrate: true);
    }

    public function getTitle(): string
    {
        return __('user.settings.title');
    }

    public static function getNavigationLabel(): string
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
                $this->usernameSection(),
                $this->emailSection(),
                $this->phoneSection(),
                $this->defaultOrganizationSection(),
                $this->userIdSection(),
                $this->deleteSection(),
            ]);
    }

    protected function avatarSection(): Section
    {
        return Section::make(__('forms.user.settings.avatar_heading'))
            ->columns(4)
            ->description(__('forms.user.settings.avatar_description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([
                SpatieMediaLibraryFileUpload::make('avatar')
                    ->hiddenLabel()
                    ->collection('avatar')
                    ->conversion('thumb')
                    ->avatar()
                    ->image()
                    ->imageEditor()
                    ->circleCropper()
                    ->afterStateHydrated(function (SpatieMediaLibraryFileUpload $component): void {
                        if (blank($component->getState()) && blank($this->user->getFilamentAvatarUrl())) {
                            $component->rawState(['__fallback' => '__fallback']);
                        }
                    })
                    ->getUploadedFileUsing(function (SpatieMediaLibraryFileUpload $component, string $file): ?array {
                        if ($file === '__fallback') {
                            return [
                                'name' => 'avatar',
                                'size' => 0,
                                'type' => 'image/png',
                                'url' => $this->user->avatarUrl(),
                            ];
                        }

                        $media = $component->getRecord()?->getRelationValue('media')->firstWhere('uuid', $file);

                        if (! $media) {
                            return null;
                        }

                        return [
                            'name' => $media->name,
                            'size' => $media->size,
                            'type' => $media->mime_type,
                            'url' => $media->getUrl($component->getConversion() ?? ''),
                        ];
                    }),
            ])
            ->footerActions([
                Action::make('saveAvatar')->label(__('forms.common.save'))->action(fn () => $this->saveAvatar()),
            ]);
    }

    protected function profileSection(): Section
    {
        return Section::make(__('forms.user.settings.profile_heading'))
            ->columns(4)
            ->description(__('forms.user.settings.profile_description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([
                TextInput::make('name')
                    ->hiddenLabel()
                    ->columnSpan(2)
                    ->placeholder(__('forms.user.settings.display_name'))
                    ->required()
                    ->maxLength(255),
            ])
            ->footerActions([
                Action::make('saveProfile')->label(__('forms.common.save'))->action(fn () => $this->saveProfile()),
            ]);
    }

    protected function usernameSection(): Section
    {
        return Section::make(__('forms.user.settings.username_heading'))
            ->columns(4)
            ->description(__('forms.user.settings.username_description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([
                TextInput::make('username')
                    ->hiddenLabel()
                    ->columnSpan(2)
                    ->placeholder(__('forms.user.settings.username_placeholder'))
                    ->prefix(str(parse_url(config('app.url'), PHP_URL_HOST))->append('/'))
                    ->alphaDash()
                    ->minLength(3)
                    ->maxLength(48)
                    ->unique(User::class, 'username', ignorable: fn (): User => $this->user),
            ])
            ->footerActions([
                Action::make('saveUsername')->label(__('forms.common.save'))->action(fn () => $this->saveUsername()),
            ]);
    }

    protected function emailSection(): Section
    {
        return Section::make(__('forms.user.settings.email_heading'))
            ->columns(4)
            ->description(__('forms.user.settings.email_description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([
                TextInput::make('email')
                    ->hiddenLabel()
                    ->columnSpan(2)
                    ->placeholder(__('forms.user.settings.email_placeholder'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(User::class, 'email', ignorable: fn (): User => $this->user),
            ])
            ->footerActions([
                Action::make('saveEmail')->label(__('forms.common.save'))->action(fn () => $this->saveEmail()),
            ]);
    }

    protected function phoneSection(): Section
    {
        return Section::make(__('forms.user.settings.phone_heading'))
            ->columns(4)
            ->description(__('forms.user.settings.phone_description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([
                TextInput::make('phone')
                    ->hiddenLabel()
                    ->columnSpan(2)
                    ->placeholder(__('forms.user.settings.phone_placeholder'))
                    ->tel()
                    ->rule('phone:INTERNATIONAL')
                    ->maxLength(40),
            ])
            ->footerActions([
                Action::make('savePhone')
                    ->label(__('forms.common.save'))
                    ->action(fn () => $this->savePhone()),
            ]);
    }

    protected function defaultOrganizationSection(): Section
    {
        return Section::make(__('forms.user.settings.default_org_heading'))
            ->columns(4)
            ->description(__('forms.user.settings.default_org_description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([
                Select::make('default_organization_id')
                    ->hiddenLabel()
                    ->columnSpan(2)
                    ->placeholder(__('forms.user.settings.default_org_placeholder'))
                    ->options(fn (): array => $this->user
                        ->organizations()
                        ->orderBy('name')
                        ->pluck('name', 'organizations.id')
                        ->all()),
            ])
            ->footerActions([
                Action::make('saveDefaultOrganization')
                    ->label(__('forms.common.save'))
                    ->action(fn () => $this->saveDefaultOrganization()),
            ]);
    }

    protected function userIdSection(): Section
    {
        return Section::make(__('forms.user.settings.user_id_heading'))
            ->columns(4)
            ->description(__('forms.user.settings.user_id_description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([
                TextInput::make('user_id_display')
                    ->hiddenLabel()
                    ->columnSpan(2)
                    ->default($this->user->uuid)
                    ->disabled()
                    ->dehydrated(false)
                    ->copyable(),
            ]);
    }

    protected function deleteSection(): Section
    {
        return Section::make(__('forms.user.settings.delete_heading'))
            ->description(__('forms.user.settings.delete_description'))
            ->footerActionsAlignment(Alignment::End)
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

    public function saveUsername(): void
    {
        $this->user->update(['username' => $this->form->getState()['username']]);

        Notification::make()->title(__('notifications.saved'))->success()->send();
    }

    public function saveEmail(): void
    {
        $this->user->update(['email' => $this->form->getState()['email']]);

        Notification::make()->title(__('notifications.email_saved'))->success()->send();
    }

    public function savePhone(): void
    {
        $this->user->update(['phone' => $this->form->getState()['phone']]);

        Notification::make()->title(__('notifications.saved'))->success()->send();
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
