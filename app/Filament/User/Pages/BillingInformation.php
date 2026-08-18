<?php

namespace App\Filament\User\Pages;

use App\Enums\Billing\TaxIdType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Nnjeim\World\Models\Country;
use Override;

class BillingInformation extends Page
{
    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.user.pages.billing-information';

    public ?User $user = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    #[Override]
    public static function canAccess(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->user = auth()->user();

        $this->form->fill($this->user->only([
            'invoice_email',
            'billing_company',
            'billing_line1',
            'billing_line2',
            'billing_city',
            'billing_state',
            'billing_postal_code',
            'billing_country',
            'invoice_language',
            'invoice_purchase_order',
            'tax_id_type',
            'tax_id_value',
        ]));
    }

    public static function getNavigationLabel(): string
    {
        return __('user.billing.information.title');
    }

    public function getTitle(): string
    {
        return __('user.billing.information.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->record($this->user)
            ->statePath('data')
            ->components([
                $this->paymentMethodSection(),
                $this->invoiceEmailSection(),
                $this->companyNameSection(),
                $this->billingAddressSection(),
                $this->invoiceLanguageSection(),
                $this->invoicePurchaseOrderSection(),
                $this->taxIdSection(),
            ]);
    }

    protected function paymentMethodSection(): Section
    {
        return Section::make(__('user.billing.payment_method.heading'))
            ->description(__('user.billing.payment_method.description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([])
            ->footerActions([
                Action::make('managePaymentMethod')
                    ->label(__('user.billing.payment_method.manage'))
                    ->action(fn () => $this->redirect($this->user->billingPortalUrl(static::getUrl()))),
            ]);
    }

    protected function invoiceEmailSection(): Section
    {
        return Section::make(__('user.billing.invoice_email.heading'))
            ->description(__('user.billing.invoice_email.description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([
                TextInput::make('invoice_email')
                    ->hiddenLabel()
                    ->email()
                    ->maxLength(254)
                    ->placeholder($this->user->email),
            ])
            ->footerActions([
                Action::make('saveInvoiceEmail')
                    ->label(__('user.billing.actions.save'))
                    ->action(fn () => $this->persist(['invoice_email'])),
            ]);
    }

    protected function companyNameSection(): Section
    {
        return Section::make(__('user.billing.company.heading'))
            ->description(__('user.billing.company.description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([
                TextInput::make('billing_company')
                    ->hiddenLabel()
                    ->maxLength(64),
            ])
            ->footerActions([
                Action::make('saveCompanyName')
                    ->label(__('user.billing.actions.save'))
                    ->action(fn () => $this->persist(['billing_company'])),
            ]);
    }

    protected function billingAddressSection(): Section
    {
        return Section::make(__('user.billing.address.heading'))
            ->description(__('user.billing.address.description'))
            ->footerActionsAlignment(Alignment::End)
            ->columns(6)
            ->schema([
                Select::make('billing_country')
                    ->label(__('user.billing.address.country'))
                    ->columnSpan(6)
                    ->options(fn (): array => Country::query()->orderBy('name')->pluck('name', 'iso2')->all())
                    ->searchable(),
                TextInput::make('billing_line1')
                    ->label(__('user.billing.address.line1'))
                    ->columnSpan(6)
                    ->maxLength(255),
                TextInput::make('billing_line2')
                    ->label(__('user.billing.address.line2'))
                    ->columnSpan(6)
                    ->maxLength(255),
                TextInput::make('billing_city')
                    ->label(__('user.billing.address.city'))
                    ->columnSpan(3)
                    ->maxLength(255),
                TextInput::make('billing_state')
                    ->label(__('user.billing.address.state'))
                    ->columnSpan(2)
                    ->maxLength(255),
                TextInput::make('billing_postal_code')
                    ->label(__('user.billing.address.postal_code'))
                    ->columnSpan(1)
                    ->maxLength(32),
            ])
            ->footerActions([
                Action::make('saveBillingAddress')
                    ->label(__('user.billing.actions.save'))
                    ->action(fn () => $this->persist([
                        'billing_country',
                        'billing_line1',
                        'billing_line2',
                        'billing_city',
                        'billing_state',
                        'billing_postal_code',
                    ])),
            ]);
    }

    protected function invoiceLanguageSection(): Section
    {
        return Section::make(__('user.billing.invoice_language.heading'))
            ->description(__('user.billing.invoice_language.description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([
                Select::make('invoice_language')
                    ->hiddenLabel()
                    ->options(fn (): array => collect(config('app.locales'))
                        ->mapWithKeys(fn (array $meta, string $code): array => [$code => $meta['name']])
                        ->all()),
            ])
            ->footerActions([
                Action::make('saveInvoiceLanguage')
                    ->label(__('user.billing.actions.save'))
                    ->action(fn () => $this->persist(['invoice_language'])),
            ]);
    }

    protected function invoicePurchaseOrderSection(): Section
    {
        return Section::make(__('user.billing.purchase_order.heading'))
            ->description(__('user.billing.purchase_order.description'))
            ->footerActionsAlignment(Alignment::End)
            ->schema([
                TextInput::make('invoice_purchase_order')
                    ->hiddenLabel()
                    ->maxLength(64),
            ])
            ->footerActions([
                Action::make('saveInvoicePurchaseOrder')
                    ->label(__('user.billing.actions.save'))
                    ->action(fn () => $this->persist(['invoice_purchase_order'])),
            ]);
    }

    protected function taxIdSection(): Section
    {
        return Section::make(__('user.billing.tax_id.heading'))
            ->description(__('user.billing.tax_id.description'))
            ->footerActionsAlignment(Alignment::End)
            ->columns(4)
            ->schema([
                Select::make('tax_id_type')
                    ->hiddenLabel()
                    ->placeholder(__('user.billing.tax_id.type'))
                    ->columnSpan(3)
                    ->options(TaxIdType::class)
                    ->searchable(),
                TextInput::make('tax_id_value')
                    ->hiddenLabel()
                    ->placeholder('DE000000000')
                    ->maxLength(64),
            ])
            ->footerActions([
                Action::make('saveTaxId')
                    ->label(__('user.billing.actions.save'))
                    ->action(fn () => $this->persist(['tax_id_type', 'tax_id_value'])),
            ]);
    }

    /**
     * @param  list<string>  $keys
     */
    protected function persist(array $keys): void
    {
        $state = $this->form->getState();

        $this->user->update(collect($state)->only($keys)->all());

        Notification::make()->title(__('notifications.saved'))->success()->send();
    }
}
