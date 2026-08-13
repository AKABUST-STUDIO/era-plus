@if ($organization = \App\Facades\OrganizationService::current())
    <div class="flex w-full py-4">
        {!!
            \Filament\Actions\Action::make('back')
                ->label(__('navigation.back'))
                ->icon('lucide-arrow-left')
                ->color('gray')
                ->outlined()
                ->extraAttributes(['class' => 'drop-shadow-none ring-0 shadow-none w-full place-content-start'])
                ->url(\App\Facades\OrganizationService::urlFor($organization))
                ->toHtml()
        !!}
    </div>
@endif
