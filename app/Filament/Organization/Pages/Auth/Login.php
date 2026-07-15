<?php

namespace App\Filament\Organization\Pages\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Spatie\OneTimePasswords\Enums\ConsumeOneTimePasswordResult;

class Login extends BaseLogin
{
    public string $step = 'email';

    public ?string $emailForCode = null;

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

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus();
    }

    protected function getCodeFormComponent(): Component
    {
        return TextInput::make('code')
            ->label('One-time code')
            ->helperText('We sent a 6-digit code to '.$this->emailForCode)
            ->required()
            ->autocomplete('one-time-code')
            ->autofocus()
            ->maxLength(6);
    }

    public function requestCode(): void
    {
        $data = $this->form->getState();
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            redirect()->to(Filament::getRegistrationUrl().'?email='.urlencode($email));

            return;
        }

        $user->sendOneTimePassword();

        $this->emailForCode = $email;
        $this->step = 'code';
        $this->form->fill();
    }

    public function verifyCode(): ?LoginResponse
    {
        $data = $this->form->getState();
        $code = trim((string) ($data['code'] ?? ''));

        if (blank($this->emailForCode)) {
            throw ValidationException::withMessages([
                'data.code' => 'Session expired. Please request a new code.',
            ]);
        }

        $user = User::query()->where('email', $this->emailForCode)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'data.code' => 'Account no longer exists.',
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
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function resendCode(): void
    {
        if (blank($this->emailForCode)) {
            $this->step = 'email';

            return;
        }

        $user = User::query()->where('email', $this->emailForCode)->first();

        if (! $user) {
            $this->step = 'email';

            return;
        }

        $user->sendOneTimePassword();

        Notification::make()->title('A new code is on the way.')->success()->send();
    }

    public function useDifferentEmail(): void
    {
        $this->step = 'email';
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
                $this->getVerifyCodeFormAction(),
                $this->getResendCodeFormAction(),
                $this->getUseDifferentEmailFormAction(),
            ],
            default => [$this->getRequestCodeFormAction()],
        };
    }

    protected function getRequestCodeFormAction(): Action
    {
        return Action::make('requestCode')
            ->label('Send me a sign-in code')
            ->submit('requestCode');
    }

    protected function getVerifyCodeFormAction(): Action
    {
        return Action::make('verifyCode')
            ->label('Sign in')
            ->submit('verifyCode');
    }

    protected function getResendCodeFormAction(): Action
    {
        return Action::make('resendCode')
            ->label('Resend code')
            ->link()
            ->action('resendCode');
    }

    protected function getUseDifferentEmailFormAction(): Action
    {
        return Action::make('useDifferentEmail')
            ->label('Use a different email')
            ->link()
            ->color('gray')
            ->action('useDifferentEmail');
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler($this->step === 'code' ? 'verifyCode' : 'requestCode')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->step === 'code' ? 'Check your email' : 'Sign in';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->step === 'code') {
            return new HtmlString('Enter the code we just sent to <strong>'.e($this->emailForCode).'</strong>.');
        }

        if (! Filament::hasRegistration()) {
            return null;
        }

        return new HtmlString('New here? '.$this->registerAction->toHtml());
    }
}
