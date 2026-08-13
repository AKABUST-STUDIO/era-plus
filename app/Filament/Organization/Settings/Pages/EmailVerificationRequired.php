<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Facades\OrganizationService;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

class EmailVerificationRequired extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.organization.settings.pages.email-verification-required';

    public ?Organization $organization = null;

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);
        abort_unless($organization->enforce_email_verification, 404);

        $this->organization = $organization;
    }

    public function getTitle(): string
    {
        return __('settings.email_verification_required.title');
    }

    /**
     * @return array<int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [__('settings.breadcrumb'), __('settings.email_verification_required.title')];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resend')
                ->label(__('settings.email_verification_required.resend'))
                ->action(fn () => $this->resend()),
        ];
    }

    public function resend(): void
    {
        $user = auth()->user();

        if ($user === null || $user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();

        Notification::make()
            ->title(__('settings.email_verification_required.resend_sent'))
            ->success()
            ->send();
    }

    public function refreshStatus(): void
    {
        $user = auth()->user();

        if ($user?->fresh()?->hasVerifiedEmail()) {
            Event::dispatch(new Verified($user));

            $this->redirect(OrganizationSettings::getUrl([
                'tenant' => $this->organization->slug,
            ]));
        }
    }
}
