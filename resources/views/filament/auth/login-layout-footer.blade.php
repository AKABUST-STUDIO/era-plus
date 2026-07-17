@if (request()->route()->getName() === 'filament.organization.auth.login')
    <div class="text-center justify-center inline-flex items-baseline gap-2 mb-6">
        {!! \Filament\Actions\Action::make('terms')
        ->link()
        ->label(str(__('filament-panels::auth/pages/register.consent.terms.label'))->toHtmlString())
        ->url(route('legal.terms'))
        ->size('xs')
        ->openUrlInNewTab()
        ->toHtml() !!}
        {!! \Filament\Actions\Action::make('privacy')
        ->link()
        ->size('xs')
        ->label(str(__('filament-panels::auth/pages/register.consent.privacy.label'))->toHtmlString())
        ->url(route('legal.privacy'))
        ->openUrlInNewTab()
        ->toHtml() !!}
    </div>
@endif