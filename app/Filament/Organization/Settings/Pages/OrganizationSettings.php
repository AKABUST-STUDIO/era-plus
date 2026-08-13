<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Enums\Permissions\OrganizationPermission;
use App\Facades\OrganizationService;
use App\Filament\Contracts\HasOrganizationPermissions;
use App\Filament\Organization\Settings\Pages\Concerns\HasOrgSettingsBreadcrumbs;
use App\Models\Organization;
use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationSettings extends Page implements HasOrganizationPermissions
{
    use HasOrgSettingsBreadcrumbs;

    protected static ?string $slug = 'overview';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.organization.settings.pages.general-settings';

    public static function getPermissionEnum(): string
    {
        return OrganizationPermission::class;
    }

    public ?Organization $organization = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return OrganizationService::current() instanceof Organization;
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.organization');
    }

    public function getTitle(): string
    {
        return __('navigation.organization');
    }

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);

        $this->organization = $organization;

        $this->form->fill($organization->attributesToArray());
        $this->form->loadStateFromRelationships(shouldHydrate: true);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->record($this->organization)
            ->statePath('data')
            ->components([
                $this->avatarSection(),
                $this->nameSection(),
                $this->urlSection(),
                $this->leaveSection(),
                $this->deleteSection(),
            ]);
    }

    protected function nameSection(): Section
    {
        return Section::make(__('settings.general.name.heading'))
            ->key('name-section')
            ->description(__('settings.general.name.description'))
            ->schema([
                TextInput::make('name')
                    ->label(__('settings.general.name.field'))
                    ->required()
                    ->maxLength(255),
            ])
            ->footerActions([
                Action::make('saveName')
                    ->authorize('update', $this->organization)
                    ->label(__('settings.general.name.action'))
                    ->action(function (): void {
                        $this->organization->update(['name' => $this->form->getState()['name']]);

                        Notification::make()
                            ->title(__('settings.general.name.saved'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function avatarSection(): Section
    {
        return Section::make(__('settings.general.avatar.heading'))
            ->key('avatar-section')
            ->description(__('settings.general.avatar.description'))
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
                Action::make('saveAvatar')
                    ->authorize('update', $this->organization)
                    ->label(__('settings.general.avatar.action'))
                    ->action(function (): void {
                        $this->form->getState();
                        $this->form->saveRelationships();

                        Notification::make()
                            ->title(__('settings.general.avatar.saved'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function urlSection(): Section
    {
        return Section::make(__('settings.general.url.heading'))
            ->key('url-section')
            ->description(__('settings.general.url.description'))
            ->schema([
                TextInput::make('slug')
                    ->label(__('settings.general.url.field'))
                    ->required()
                    ->alphaDash()
                    ->maxLength(255)
                    ->unique(Organization::class, 'slug', ignoreRecord: true)
                    ->prefix(parse_url(config('app.url'), PHP_URL_HOST).'/'),
            ])
            ->footerActions([
                Action::make('saveUrl')
                    ->authorize('update', $this->organization)
                    ->label(__('settings.general.url.action'))
                    ->action(function (): void {
                        $this->organization->update(['slug' => $this->form->getState()['slug']]);

                        Notification::make()
                            ->title(__('settings.general.url.saved'))
                            ->success()
                            ->send();

                        $this->redirect(static::getUrl(['organization' => $this->organization->slug]));
                    }),
            ]);
    }

    protected function leaveSection(): Section
    {
        $isLastAdmin = $this->currentUserIsLastAdmin();

        return Section::make(__('settings.general.leave.heading'))
            ->key('leave-section')
            ->description($isLastAdmin
                ? __('settings.general.leave.last_admin_description')
                : __('settings.general.leave.description'))
            ->footerActions([
                Action::make('leave')
                    ->label(__('settings.general.leave.action'))
                    ->color('warning')
                    ->disabled($isLastAdmin)
                    ->tooltip($isLastAdmin ? __('settings.general.leave.last_admin_tooltip') : null)
                    ->requiresConfirmation()
                    ->action(function (): void {
                        if ($this->currentUserIsLastAdmin()) {
                            Notification::make()
                                ->title(__('settings.general.leave.last_admin_title'))
                                ->body(__('settings.general.leave.last_admin_body'))
                                ->danger()
                                ->send();

                            return;
                        }

                        if ($this->organization->users()->count() <= 1) {
                            Notification::make()
                                ->title(__('settings.general.leave.only_member_title'))
                                ->body(__('settings.general.leave.only_member_body'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $this->organization->users()->detach(Filament::auth()->id());

                        OrganizationService::forget();

                        Notification::make()
                            ->title(__('settings.general.leave.saved'))
                            ->success()
                            ->send();

                        $this->redirect($this->organizationPanelUrl());
                    }),
            ]);
    }

    protected function deleteSection(): Section
    {
        $organizationName = (string) $this->organization?->name;
        $confirmPhrase = __('settings.general.delete.confirm_phrase');

        return Section::make(__('settings.general.delete.heading'))
            ->key('delete-section')
            ->description(__('settings.general.delete.description'))
            ->footerActions([
                DeleteAction::make('delete')
                    ->record(fn (): ?Organization => $this->organization)
                    ->authorize('delete')
                    ->label(__('settings.general.delete.action'))
                    ->color('danger')
                    ->modalIcon('lucide-triangle-alert')
                    ->modalIconColor('danger')
                    ->modalHeading(__('settings.general.delete.modal_heading', ['name' => $organizationName]))
                    ->modalDescription(__('settings.general.delete.modal_description', [
                        'name' => $organizationName,
                        'phrase' => $confirmPhrase,
                    ]))
                    ->modalSubmitActionLabel(__('settings.general.delete.action'))
                    ->form([
                        TextInput::make('name_confirm')
                            ->label(__('settings.general.delete.name_label', ['name' => $organizationName]))
                            ->required()
                            ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($organizationName): void {
                                if ((string) $value !== $organizationName) {
                                    $fail(__('settings.general.delete.name_mismatch'));
                                }
                            }),
                        TextInput::make('phrase_confirm')
                            ->label(__('settings.general.delete.phrase_label', ['phrase' => $confirmPhrase]))
                            ->required()
                            ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($confirmPhrase): void {
                                if ((string) $value !== $confirmPhrase) {
                                    $fail(__('settings.general.delete.phrase_mismatch'));
                                }
                            }),
                    ])
                    ->using(function (Organization $record): bool {
                        $record->users()->detach();
                        $deleted = (bool) $record->delete();

                        OrganizationService::forget();

                        return $deleted;
                    })
                    ->successNotificationTitle(__('settings.general.delete.saved'))
                    ->successRedirectUrl(fn (): string => $this->organizationPanelUrl()),
            ]);
    }

    protected function currentUserIsLastAdmin(): bool
    {
        $user = Filament::auth()->user();

        if ($user === null || $this->organization === null) {
            return false;
        }

        if (! $user->isOrgAdmin($this->organization)) {
            return false;
        }

        return $this->organization->admins()->count() <= 1;
    }

    protected function organizationPanelUrl(): string
    {
        return Filament::getPanel(OrganizationPanelProvider::PANEL_ID)->getUrl() ?? '/';
    }
}
