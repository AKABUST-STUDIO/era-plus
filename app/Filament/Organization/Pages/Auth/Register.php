<?php

namespace App\Filament\Organization\Pages\Auth;

use App\Models\User;
use App\Services\LoginCodeService;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Register extends BaseRegister
{
    public string $step = 'form';

    public ?string $emailForCode = null;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());

            return;
        }

        $email = (string) request()->query('email', '');

        $this->form->fill([
            'email' => $email,
            'name' => $email !== '' ? LoginCodeService::deriveNameFromEmail($email) : '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(match ($this->step) {
            'code' => [$this->getCodeFormComponent()],
            default => [$this->getEmailFormComponent(), $this->getNameFormComponent()],
        });
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(User::class, 'email')
            ->autocomplete('email')
            ->autofocus()
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                if (blank($state)) {
                    return;
                }

                if (filled($get('name'))) {
                    return;
                }

                $set('name', LoginCodeService::deriveNameFromEmail($state));
            });
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Name')
            ->required()
            ->maxLength(255)
            ->autocomplete('name');
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

    public function registerAndSendCode(LoginCodeService $codes): void
    {
        $data = $this->form->getState();
        $email = mb_strtolower(trim((string) $data['email']));
        $name = trim((string) $data['name']);

        if ($codes->tooManyRequests($email)) {
            Notification::make()
                ->title('Too many code requests. Try again in '.$codes->secondsUntilRetry($email).' seconds.')
                ->danger()
                ->send();

            return;
        }

        User::query()->create([
            'email' => $email,
            'name' => $name,
            'password' => Str::random(64),
        ]);

        $codes->issueAndSend($email);

        $this->emailForCode = $email;
        $this->step = 'code';
        $this->form->fill();
    }

    public function verifyCode(LoginCodeService $codes): ?RegistrationResponse
    {
        $data = $this->form->getState();
        $code = trim((string) ($data['code'] ?? ''));

        if (blank($this->emailForCode) || ! $codes->verify($this->emailForCode, $code)) {
            throw ValidationException::withMessages([
                'data.code' => 'That code is invalid or has expired.',
            ]);
        }

        $user = User::query()->where('email', $this->emailForCode)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'data.code' => 'Account not found.',
            ]);
        }

        if ($user->email_verified_at === null) {
            $user->markEmailAsVerified();
        }

        Filament::auth()->login($user, remember: true);
        session()->regenerate();

        return app(RegistrationResponse::class);
    }

    public function resendCode(LoginCodeService $codes): void
    {
        if (blank($this->emailForCode)) {
            $this->step = 'form';

            return;
        }

        if ($codes->tooManyRequests($this->emailForCode)) {
            Notification::make()
                ->title('Too many code requests. Try again in '.$codes->secondsUntilRetry($this->emailForCode).' seconds.')
                ->danger()
                ->send();

            return;
        }

        $codes->issueAndSend($this->emailForCode);

        Notification::make()->title('A new code is on the way.')->success()->send();
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
            ],
            default => [$this->getRegisterFormAction()],
        };
    }

    public function getRegisterFormAction(): Action
    {
        return Action::make('registerAndSendCode')
            ->label('Continue')
            ->submit('registerAndSendCode');
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

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler($this->step === 'code' ? 'verifyCode' : 'registerAndSendCode')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->step === 'code' ? 'Check your email' : 'Create your account';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->step === 'code') {
            return new HtmlString('Enter the code we just sent to <strong>'.e($this->emailForCode).'</strong>.');
        }

        if (! Filament::hasLogin()) {
            return null;
        }

        return new HtmlString('Already have an account? '.$this->loginAction->toHtml());
    }
}
