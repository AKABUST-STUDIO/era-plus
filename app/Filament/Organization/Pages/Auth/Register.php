<?php

namespace App\Filament\Organization\Pages\Auth;

use App\Filament\Components\OtpInput;
use App\Mail\AccountCreated;
use App\Models\User;
use App\Support\EmailUsername;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
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
                $this->verifyCode();
            });
    }

    protected function setStep(string $step): void
    {
        $this->step = $step;

        $this->cacheSchema('form');
        $this->cacheSchema('content');
    }

    public function registerAndSendCode(): void
    {
        $data = $this->form->getState();
        $email = mb_strtolower(trim((string) $data['email']));
        $name = EmailUsername::toDisplayName($email);

        $user = User::query()->create([
            'email' => $email,
            'name' => $name,
            'password' => Str::random(64),
        ]);

        $user->sendOneTimePassword();

        $this->emailForCode = $email;
        $this->setStep('code');
        $this->form->fill();
    }

    public function verifyCode(): ?RegistrationResponse
    {
        $data = $this->form->getState();
        $code = trim((string) ($data['code'] ?? ''));

        if (blank($this->emailForCode)) {
            throw ValidationException::withMessages([
                'data.code' => __('filament-panels::auth/pages/register.messages.session_expired'),
            ]);
        }

        $user = User::query()->where('email', $this->emailForCode)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'data.code' => __('filament-panels::auth/pages/register.messages.account_missing'),
            ]);
        }

        $result = $user->attemptLoginUsingOneTimePassword($code, remember: true);

        if (! $result->isOk()) {
            throw ValidationException::withMessages([
                'data.code' => $result->validationMessage(),
            ]);
        }

        if ($user->email_verified_at === null) {
            $user->markEmailAsVerified();

            Mail::to($user->email)->queue(new AccountCreated($user));
        }

        session()->regenerate();

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
        return Action::make('registerAndSendCode')
            ->icon('lucide-mail')
            ->label(__('filament-panels::auth/pages/register.form.actions.register.label'))
            ->submit('registerAndSendCode');
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('registerAndSendCode')
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
