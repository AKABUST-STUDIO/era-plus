<?php

namespace App\Filament\Organization\Pages\Auth;

use App\Facades\AuthenticationService;
use App\Filament\Components\OtpInput;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
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

class Register extends BaseRegister
{
    public string $step = 'form';

    public ?string $emailForCode = null;

    protected Width|string|null $maxContentWidth = Width::Medium;

    public function mount(): void
    {
        parent::mount();

        $email = (string) request()->query('email', '');

        $this->form->fill([
            'email' => $email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(match ($this->step) {
            'code' => [$this->getCodeFormComponent()],
            default => [$this->getEmailFormComponent()],
        });
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->placeholder(__('filament-panels::auth/pages/register.form.email.label'))
            ->hiddenLabel()
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(User::class, 'email')
            ->autocomplete('email')
            ->autofocus()
            ->live(onBlur: true);
    }

    protected function getCodeFormComponent(): Component
    {
        return OtpInput::make('code')
            ->numberInput(6)
            ->hiddenLabel()
            ->required()
            ->autocomplete('one-time-code')
            ->autofocus()
            ->maxLength(6)
            ->afterStateUpdated(function () {
                $this->register();
            });
    }

    protected function setStep(string $step): void
    {
        $this->step = $step;

        $this->cacheSchema('form');
        $this->cacheSchema('content');
    }

    public function requestRegister(): void
    {
        $email = normalize_string($this->form->getState()['email'] ?? null);

        AuthenticationService::register($email);

        $this->emailForCode = $email;
        $this->setStep('code');
        $this->form->fill();
    }

    public function register(): ?RegistrationResponse
    {
        if (blank($this->emailForCode)) {
            throw ValidationException::withMessages([
                'data.code' => __('filament-panels::auth/pages/login.messages.session_expired'),
            ]);
        }

        $code = normalize_string(($this->form->getState()['code'] ?? null));

        AuthenticationService::authenticate($this->emailForCode, $code, shouldSendWelcomeMailable: true);

        return app(RegistrationResponse::class);
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
            default => [$this->getRegisterFormAction()],
        };
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

    public function getRegisterFormAction(): Action
    {
        return Action::make('requestRegister')
            ->icon('lucide-mail')
            ->label(__('filament-panels::auth/pages/register.form.actions.register.label'))
            ->submit('requestRegister');
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('requestRegister')
            ->extraAttributes(['class' => 'gap-4'])
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('filament-panels::auth/pages/register.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->step === 'code') {
            return new HtmlString(__('filament-panels::auth/pages/register.code.subheading', ['email' => e($this->emailForCode)]));
        }

        return null;
    }
}
