<div class="w-full flex justify-between p-4">
    <x-filament-panels::logo />

    <div>
        @if (request()->route()->getName() === 'filament.organization.auth.login')
            {!! \Filament\Actions\Action::make('login')
            ->label(__('filament-panels::auth/pages/login.actions.register.label'))
            ->url(filament()->getRegistrationUrl())
            ->outlined()
            ->size('xs')
            ->toHtml() !!}
        @elif (request()->route()->getName() === 'filament.organization.auth.register')
            {!! \Filament\Actions\Action::make('register')
            ->label(__('filament-panels::auth/pages/register.actions.login.label'))
            ->url(filament()->getLoginUrl())
            ->outlined()
            ->size('xs')
            ->toHtml() !!}
        @endif
    </div>
    
</div>