
<p class="fi-auth-consent text-sm text-gray-500 dark:text-gray-400 text-center items-baseline gap-2 mt-4">
    {{ __('filament-panels::auth/pages/register.consent.before') }}
    {!!
        \Filament\Actions\Action::make('terms')
            ->link()
            ->label(str(__('filament-panels::auth/pages/register.consent.terms.label'))->toHtmlString())
            ->url(route('legal.terms'))
            ->openUrlInNewTab()
            ->toHtml()
    !!}
    {{ __('filament-panels::auth/pages/register.consent.between') }}
    {!!
        \Filament\Actions\Action::make('privacy')
            ->link()
            ->label(str(__('filament-panels::auth/pages/register.consent.privacy.label'))->toHtmlString())
            ->url(route('legal.privacy'))
            ->openUrlInNewTab()
            ->toHtml()
    !!}
</p>
