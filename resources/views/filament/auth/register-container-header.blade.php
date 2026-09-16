@if ($this->step !== 'code')
<div @class([
        "fi-auth-oauth flex flex-col gap-2",
        "pb-6 border-b border-gray-100 dark:border-gray-800" => filled(config('services.google.client_id'))
    ])
>
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
</div>
@endif