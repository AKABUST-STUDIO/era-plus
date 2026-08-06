<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Enums\Subscription\SubscriptionTier;
use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\Concerns\HasOrgSettingsBreadcrumbs;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Billing extends Page
{
    use HasOrgSettingsBreadcrumbs;

    protected static ?string $slug = 'billing';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.organization.settings.pages.billing';

    public ?Organization $organization = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->can('view', self::class) ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.billing.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.billing.title');
    }

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);

        $this->organization = $organization;

        $address = $organization->billing_address ?? [];

        $this->form->fill([
            'address_line1' => $address['line1'] ?? null,
            'address_line2' => $address['line2'] ?? null,
            'address_city' => $address['city'] ?? null,
            'address_state' => $address['state'] ?? null,
            'address_postal_code' => $address['postal_code'] ?? null,
            'address_country' => $address['country'] ?? null,
            'invoice_language' => $organization->invoice_language,
            'tax_id' => $organization->tax_id,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                $this->planSection(),
                $this->paymentMethodSection(),
                $this->addressSection(),
                $this->languageSection(),
                $this->taxIdSection(),
            ]);
    }

    protected function planSection(): Section
    {
        $isBasic = $this->organization->subscription_tier === SubscriptionTier::Basic;

        return Section::make(__('settings.billing.plan.heading'))
            ->description(match ($this->organization->subscription_tier) {
                SubscriptionTier::Basic => __('settings.billing.plan.basic_pitch'),
                SubscriptionTier::Pro, SubscriptionTier::Trial => __('settings.billing.plan.pro_active'),
            })
            ->footerActions([
                Action::make('checkoutPro')
                    ->authorize('update', self::class)
                    ->label(__('settings.billing.plan.upgrade_pro'))
                    ->color('primary')
                    ->visible(fn (): bool => $isBasic && filled(config('services.stripe.prices.pro')))
                    ->action(function (): RedirectResponse {
                        $priceId = config('services.stripe.prices.pro');

                        abort_unless($priceId, 400, 'Stripe price ID not configured.');

                        $checkout = $this->organization
                            ->newSubscription('default', $priceId)
                            ->checkout([
                                'success_url' => static::getUrl(['tenant' => $this->organization]),
                                'cancel_url' => static::getUrl(['tenant' => $this->organization]),
                            ]);

                        return redirect()->away($checkout->url);
                    }),
                Action::make('manage')
                    ->authorize('update', self::class)
                    ->label(__('settings.billing.plan.manage'))
                    ->visible(! $isBasic)
                    ->action(fn (): RedirectResponse => auth()->user()->redirectToBillingPortal(
                        route('filament.organization.home', ['tenant' => $this->organization])
                    )),
            ]);
    }

    protected function paymentMethodSection(): Section
    {
        return Section::make(__('settings.billing.payment_method.heading'))
            ->description(__('settings.billing.payment_method.description'))
            ->footerActions([
                Action::make('managePaymentMethod')
                    ->label(__('settings.billing.payment_method.manage'))
                    ->disabled()
                    ->tooltip(__('settings.billing.payment_method.coming_soon')),
            ]);
    }

    protected function addressSection(): Section
    {
        return Section::make(__('settings.billing.address.heading'))
            ->description(__('settings.billing.address.description'))
            ->schema([
                TextInput::make('address_line1')
                    ->label(__('settings.billing.address.line1'))
                    ->maxLength(255),
                TextInput::make('address_line2')
                    ->label(__('settings.billing.address.line2'))
                    ->maxLength(255),
                TextInput::make('address_city')
                    ->label(__('settings.billing.address.city'))
                    ->maxLength(120),
                TextInput::make('address_state')
                    ->label(__('settings.billing.address.state'))
                    ->maxLength(120),
                TextInput::make('address_postal_code')
                    ->label(__('settings.billing.address.postal_code'))
                    ->maxLength(32),
                TextInput::make('address_country')
                    ->label(__('settings.billing.address.country'))
                    ->maxLength(2)
                    ->helperText(__('settings.billing.address.country_helper')),
            ])
            ->footerActions([
                Action::make('saveAddress')
                    ->authorize('update', self::class)
                    ->label(__('settings.billing.address.action'))
                    ->action(function (): void {
                        $data = $this->form->getState();

                        $this->organization->update([
                            'billing_address' => [
                                'line1' => $data['address_line1'] ?? null,
                                'line2' => $data['address_line2'] ?? null,
                                'city' => $data['address_city'] ?? null,
                                'state' => $data['address_state'] ?? null,
                                'postal_code' => $data['address_postal_code'] ?? null,
                                'country' => $data['address_country'] ?? null,
                            ],
                        ]);

                        Notification::make()
                            ->title(__('settings.billing.address.saved'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function languageSection(): Section
    {
        return Section::make(__('settings.billing.language.heading'))
            ->description(__('settings.billing.language.description'))
            ->schema([
                Select::make('invoice_language')
                    ->label(__('settings.billing.language.field'))
                    ->options([
                        'en' => 'English',
                        'et' => 'Eesti',
                        'de' => 'Deutsch',
                        'fr' => 'Français',
                        'es' => 'Español',
                    ])
                    ->required(),
            ])
            ->footerActions([
                Action::make('saveLanguage')
                    ->authorize('update', self::class)
                    ->label(__('settings.billing.language.action'))
                    ->action(function (): void {
                        $this->organization->update(['invoice_language' => $this->form->getState()['invoice_language']]);

                        Notification::make()
                            ->title(__('settings.billing.language.saved'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function taxIdSection(): Section
    {
        return Section::make(__('settings.billing.tax.heading'))
            ->description(__('settings.billing.tax.description'))
            ->schema([
                TextInput::make('tax_id')
                    ->label(__('settings.billing.tax.field'))
                    ->maxLength(64),
            ])
            ->footerActions([
                Action::make('saveTaxId')
                    ->authorize('update', self::class)
                    ->label(__('settings.billing.tax.action'))
                    ->action(function (): void {
                        $this->organization->update(['tax_id' => $this->form->getState()['tax_id']]);

                        Notification::make()
                            ->title(__('settings.billing.tax.saved'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
