<?php

namespace App\Filament\Organization\Pages\Auth;

use App\Facades\AuthenticationService;
use App\Filament\Components\OtpInput;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Override;

class Login extends BaseLogin
{
    public string $step = 'email';

    public ?string $emailForCode = null;

    protected Width|string|null $maxContentWidth = Width::Small;

    #[Override]
    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'email' => (string) request()->query('email', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(match ($this->step) {
            'code' => [$this->getCodeFormComponent()],
            default => [$this->getEmailFormComponent()],
        });
    }

    protected function setStep(string $step): void
    {
        $this->step = $step;

        $this->cacheSchema('form');
        $this->cacheSchema('content');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->placeholder(__('filament-panels::auth/pages/login.form.email.label'))
            ->hiddenLabel()
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus();
    }

    protected function getCodeFormComponent(): Component
    {
        return OtpInput::make('code')
            ->numberInput(6)
            ->hiddenLabel()
            ->required()
            ->string()
            ->autocomplete('one-time-code')
            ->autofocus()
            ->maxLength(6)
            ->afterStateUpdated(function () {
                $this->login();
            });
    }

    public function requestLogin(): void
    {
        $email = normalize_string($this->form->getState()['email'] ?? null);

        AuthenticationService::login($email);

        $this->emailForCode = $email;
        $this->setStep('code');
        $this->form->fill();
    }

    public function login(): ?LoginResponse
    {
        if (blank($this->emailForCode)) {
            throw ValidationException::withMessages([
                'data.code' => __('filament-panels::auth/pages/login.messages.session_expired'),
            ]);
        }

        $code = normalize_string(($this->form->getState()['code'] ?? null));

        AuthenticationService::authenticate($this->emailForCode, $code);

        return app(LoginResponse::class);
    }

    public function useDifferentEmail(): void
    {
        $this->setStep('email');
        $this->emailForCode = null;
        $this->form->fill();
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return match ($this->step) {
            'code' => [
                $this->getUseDifferentEmailFormAction(),
            ],
            default => [
                $this->getRequestLoginFormAction(),
            ],
        };
    }

    protected function getRequestLoginFormAction(): Action
    {
        return Action::make('requestLogin')
            ->label(__('filament-panels::auth/pages/login.form.actions.request_code.label'))
            ->extraAttributes(['class' => 'w-full mb-6 border border-b'])
            ->submit('requestLogin');
    }

    protected function getUseDifferentEmailFormAction(): Action
    {
        return Action::make('useDifferentEmail')
            ->label(__('filament-panels::auth/pages/login.form.actions.use_different_email.label'))
            ->link()
            ->color('gray')
            ->extraAttributes(['class' => 'w-full mt-4'])
            ->action('useDifferentEmail');
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('requestLogin')
            ->extraAttributes(['class' => 'gap-4'])
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->extraAttributes(fn () => $this->step == 'email' ? ['class' => 'border-b border-gray-100'] : [])
                    ->key('form-actions'),
            ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->step === 'code'
            ? __('filament-panels::auth/pages/login.code.heading')
            : __('filament-panels::auth/pages/login.heading', ['app' => str(config('app.name'))->ucfirst()]);
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->step === 'code') {
            return new HtmlString(__('filament-panels::auth/pages/login.code.subheading', ['email' => e($this->emailForCode), 'app' => str(config('app.name'))->ucfirst()]));
        }

        return null;
    }
}
