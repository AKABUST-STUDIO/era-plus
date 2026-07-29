<div wire:show="step !== 'code'" class="fi-auth-oauth flex flex-col gap-2">
    @if (filled(config('services.google.client_id')))
        {!!  
            \Filament\Actions\Action::make('oauth_google')
                ->label(__('filament-panels::auth/pages/login.form.actions.oauth.google.label'))
                ->icon('selfhst-google')
                ->url(route('auth.oauth.redirect', ['provider' => 'google']))
                ->color('gray')
                ->outlined()
                ->extraAttributes(['class' => 'w-full'])
                ->toHtml()
        !!}
    @endif

    @if (filled(config('services.microsoft.client_id')))
        {!!  
            \Filament\Actions\Action::make('oauth_microsoft')
                ->label(__('filament-panels::auth/pages/login.form.actions.oauth.microsoft.label'))
                ->icon('selfhst-microsoft')
                ->url(route('auth.oauth.redirect', ['provider' => 'microsoft']))
                ->color('gray')
                ->outlined()
                ->extraAttributes(['class' => 'w-full'])
                ->toHtml()
        !!}
    @endif

    @if (filled(config('services.apple.client_id')))
        {!!  
            \Filament\Actions\Action::make('oauth_apple')
                ->label(__('filament-panels::auth/pages/login.form.actions.oauth.apple.label'))
                ->icon('selfhst-apple')
                ->url(route('auth.oauth.redirect', ['provider' => 'apple']))
                ->color('gray')
                ->outlined()
                ->extraAttributes(['class' => 'w-full'])
                ->toHtml()
        !!}
    @endif
    @if (request()->route()->getName() === 'filament.organization.auth.login')
        <p class="text-center justify-center inline-flex items-baseline gap-2 mt-6">
            {{ __('filament-panels::auth/pages/login.actions.register.before') }}
            {!! \Filament\Actions\Action::make('register')
            ->link()
            ->label(__('filament-panels::auth/pages/login.actions.register.label'))
            ->url(filament()->getRegistrationUrl())
            ->toHtml() !!}
        </p>
    @endif
</div>