<?php

namespace App\Filament\Organization\Pages\Tenancy;

use App\Actions\RegisterOrganization;
use App\Enums\Subscription\SubscriptionTier;
use App\Filament\Organization\Schemas\OrganizationForm;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Mail\CorporateEnquiryReceived;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Override;

class CreateOrganization extends RegisterTenant
{
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'subscription_tier' => SubscriptionTier::Pro->value,
        ]);
    }

    public static function getLabel(): string
    {
        return __('organization.register.label');
    }

    #[Override]
    public function getRegisterFormAction(): Action
    {
        return parent::getRegisterFormAction()
            ->label(new HtmlString(view('filament.forms.components.subscription-tier-submit-label', [
                'currentTier' => $this->data['subscription_tier'] ?? null,
            ])->render()));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components(OrganizationForm::fields());
    }

    public function register(): void
    {
        if ($this->isCorporateSelected()) {
            $this->mountAction('contactSales');

            return;
        }

        parent::register();
    }

    public function contactSalesAction(): Action
    {
        return Action::make('contactSales')
            ->label(__('organization.register.plans.corporate.title'))
            ->modalHeading(__('organization.register.contact_sales.heading'))
            ->modalDescription(__('organization.register.contact_sales.description'))
            ->modalWidth(Width::Large)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalSubmitActionLabel(__('organization.register.contact_sales.submit'))
            ->fillForm(fn (): array => [
                'subject' => __('organization.register.contact_sales.subject_default'),
            ])
            ->schema([
                TextInput::make('subject')
                    ->hiddenLabel()
                    ->placeholder(__('organization.register.contact_sales.subject_placeholder'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->hiddenLabel()
                    ->placeholder(__('organization.register.contact_sales.body_placeholder'))
                    ->required()
                    ->rows(6),
            ])
            ->action(function (array $data): void {
                $user = auth()->user();

                Mail::to(config('app.sales.recipient'))->queue(new CorporateEnquiryReceived(
                    user: $user,
                    subject: $data['subject'],
                    body: $data['body'],
                ));

                Notification::make()
                    ->title(__('organization.register.contact_sales.sent'))
                    ->success()
                    ->send();
            });
    }

    protected function handleRegistration(array $data): Model
    {
        $organization = RegisterOrganization::handle(auth()->user(), $data);

        Notification::make()
            ->title(__('notifications.organization_created'))
            ->success()
            ->send();

        return $organization;
    }

    protected function getRedirectUrl(): ?string
    {
        return CreateProject::getUrl(tenant: $this->tenant);
    }

    private function isCorporateSelected(): bool
    {
        return ($this->data['subscription_tier'] ?? null) === SubscriptionTier::Corporate->value;
    }
}
