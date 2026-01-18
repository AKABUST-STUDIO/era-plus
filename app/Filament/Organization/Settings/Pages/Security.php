<?php

namespace App\Filament\Organization\Settings\Pages;

use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Security extends Page
{
    protected static ?string $slug = 'security';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.organization.settings.pages.security';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('settings.security.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.security.title');
    }

    /**
     * @return array<int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            __('settings.breadcrumb'),
            __('settings.security.navigation_label'),
        ];
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('settings.security.two_factor.heading'))
                    ->description(__('settings.security.two_factor.description'))
                    ->schema([
                        Toggle::make('enforce_two_factor')
                            ->label(__('settings.security.two_factor.label'))
                            ->hint(__('settings.security.two_factor.coming_soon'))
                            ->disabled(),
                    ]),
            ]);
    }
}
