<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Facades\OrganizationService;
use App\Filament\Concerns\GatedByOrganizationPermission;
use App\Filament\Organization\Settings\Pages\Concerns\HasOrgSettingsBreadcrumbs;
use App\Models\Organization;
use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GeneralSettings extends Page
{
    use GatedByOrganizationPermission;
    use HasOrgSettingsBreadcrumbs;

    protected static ?string $slug = 'overview';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.organization.settings.pages.general-settings';

    public ?Organization $organization = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    protected static function organizationPermission(): string
    {
        return 'view_any_setting';
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.general.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.general.title');
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
            ->description(__('settings.general.name.description'))
            ->schema([
                TextInput::make('name')
                    ->label(__('settings.general.name.field'))
                    ->required()
                    ->maxLength(255),
            ])
            ->footerActions([
                Action::make('saveName')
                    ->label(__('settings.general.name.action'))
                    ->action(fn () => $this->saveName()),
            ]);
    }

    protected function avatarSection(): Section
    {
        return Section::make(__('settings.general.avatar.heading'))
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
                    ->label(__('settings.general.avatar.action'))
                    ->action(fn () => $this->saveAvatar()),
            ]);
    }

    protected function urlSection(): Section
    {
        return Section::make(__('settings.general.url.heading'))
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
                    ->label(__('settings.general.url.action'))
                    ->action(fn () => $this->saveUrl()),
            ]);
    }

    protected function leaveSection(): Section
    {
        $isLastAdmin = $this->currentUserIsLastAdmin();

        return Section::make(__('settings.general.leave.heading'))
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
                    ->action(fn () => $this->leave()),
            ]);
    }

    protected function deleteSection(): Section
    {
        $organizationName = (string) $this->organization?->name;
        $confirmPhrase = __('settings.general.delete.confirm_phrase');

        return Section::make(__('settings.general.delete.heading'))
            ->description(__('settings.general.delete.description'))
            ->footerActions([
                Action::make('delete')
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
                    ->action(fn () => $this->delete()),
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

    public function saveName(): void
    {
        static::authorizeOrganizationPermission('update_setting');

        $data = $this->form->getState();

        $this->organization->update(['name' => $data['name']]);

        Notification::make()
            ->title(__('settings.general.name.saved'))
            ->success()
            ->send();
    }

    public function saveAvatar(): void
    {
        static::authorizeOrganizationPermission('update_setting');

        $this->form->getState();
        $this->form->saveRelationships();

        Notification::make()
            ->title(__('settings.general.avatar.saved'))
            ->success()
            ->send();
    }

    public function saveUrl(): void
    {
        static::authorizeOrganizationPermission('update_setting');

        $data = $this->form->getState();

        $this->organization->update(['slug' => $data['slug']]);

        Notification::make()
            ->title(__('settings.general.url.saved'))
            ->success()
            ->send();

        $this->redirect(static::getUrl(['organization' => $this->organization->slug]));
    }

    public function leave(): void
    {
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
    }

    public function delete(): void
    {
        static::authorizeOrganizationPermission('update_setting');

        $organization = $this->organization;

        $organization->users()->detach();
        $organization->delete();

        OrganizationService::forget();

        Notification::make()
            ->title(__('settings.general.delete.saved'))
            ->success()
            ->send();

        $this->redirect($this->organizationPanelUrl());
    }

    protected function organizationPanelUrl(): string
    {
        return Filament::getPanel(OrganizationPanelProvider::PANEL_ID)->getUrl() ?? '/';
    }
}
