<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Facades\OrganizationService;
use App\Filament\Concerns\GatedByOrganizationPermission;
use App\Filament\Organization\Settings\Pages\Concerns\HasOrgSettingsBreadcrumbs;
use App\Models\ActivityLog;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Security extends Page
{
    use GatedByOrganizationPermission;
    use HasOrgSettingsBreadcrumbs;

    protected static ?string $slug = 'security';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.organization.settings.pages.security';

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
        return __('settings.security.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.security.title');
    }

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);

        $this->organization = $organization;

        $this->form->fill([
            'enforce_two_factor' => (bool) $organization->enforce_two_factor,
            'enforce_email_verification' => (bool) $organization->enforce_email_verification,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('settings.security.two_factor.heading'))
                    ->description(__('settings.security.two_factor.description'))
                    ->schema([
                        Toggle::make('enforce_two_factor')
                            ->label(__('settings.security.two_factor.label')),
                    ])
                    ->footerActions([
                        Action::make('saveTwoFactor')
                            ->label(__('settings.security.two_factor.action'))
                            ->action(fn () => $this->saveTwoFactor()),
                    ]),
                Section::make(__('settings.security.email_verification.heading'))
                    ->description(__('settings.security.email_verification.description'))
                    ->schema([
                        Toggle::make('enforce_email_verification')
                            ->label(__('settings.security.email_verification.label')),
                    ])
                    ->footerActions([
                        Action::make('saveEmailVerification')
                            ->label(__('settings.security.email_verification.action'))
                            ->action(fn () => $this->saveEmailVerification()),
                    ]),
            ]);
    }

    public function saveTwoFactor(): void
    {
        static::authorizeOrganizationPermission('update_setting');

        $value = (bool) ($this->form->getState()['enforce_two_factor'] ?? false);

        $this->organization->update(['enforce_two_factor' => $value]);

        ActivityLog::record(
            $this->organization,
            $value ? 'Enabled enforced 2FA' : 'Disabled enforced 2FA',
            eventType: 'organization.security.two_factor',
            target: $this->organization,
        );

        Notification::make()->title(__('notifications.two_factor_saved'))->success()->send();
    }

    public function saveEmailVerification(): void
    {
        static::authorizeOrganizationPermission('update_setting');

        $value = (bool) ($this->form->getState()['enforce_email_verification'] ?? false);

        $this->organization->update(['enforce_email_verification' => $value]);

        ActivityLog::record(
            $this->organization,
            $value ? 'Enabled enforced email verification' : 'Disabled enforced email verification',
            eventType: 'organization.security.email_verification',
            target: $this->organization,
        );

        Notification::make()->title(__('notifications.email_verification_saved'))->success()->send();
    }
}
