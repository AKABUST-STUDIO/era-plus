<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Facades\OrganizationService;
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
    protected static ?string $slug = 'security';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.organization.settings.pages.security';

    public ?Organization $organization = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('settings.security.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.security.title');
    }

    /**
     * @return array<int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            __('settings.breadcrumb'),
            __('settings.security.navigation_label'),
        ];
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
                            ->label('Save 2FA setting')
                            ->action(fn () => $this->saveTwoFactor()),
                    ]),
                Section::make('Email verification')
                    ->description('Require members to verify their email before accessing the organization.')
                    ->schema([
                        Toggle::make('enforce_email_verification')
                            ->label('Require verified email for all members'),
                    ])
                    ->footerActions([
                        Action::make('saveEmailVerification')
                            ->label('Save email verification setting')
                            ->action(fn () => $this->saveEmailVerification()),
                    ]),
            ]);
    }

    public function saveTwoFactor(): void
    {
        $value = (bool) ($this->form->getState()['enforce_two_factor'] ?? false);

        $this->organization->update(['enforce_two_factor' => $value]);

        ActivityLog::record(
            $this->organization,
            $value ? 'Enabled enforced 2FA' : 'Disabled enforced 2FA',
            eventType: 'organization.security.two_factor',
            target: $this->organization,
        );

        Notification::make()->title('2FA setting saved')->success()->send();
    }

    public function saveEmailVerification(): void
    {
        $value = (bool) ($this->form->getState()['enforce_email_verification'] ?? false);

        $this->organization->update(['enforce_email_verification' => $value]);

        ActivityLog::record(
            $this->organization,
            $value ? 'Enabled enforced email verification' : 'Disabled enforced email verification',
            eventType: 'organization.security.email_verification',
            target: $this->organization,
        );

        Notification::make()->title('Email verification setting saved')->success()->send();
    }
}
