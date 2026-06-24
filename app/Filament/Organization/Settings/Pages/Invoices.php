<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\Concerns\HasOrgSettingsBreadcrumbs;
use App\Models\Organization;
use Filament\Pages\Page;

class Invoices extends Page
{
    use HasOrgSettingsBreadcrumbs;

    protected static ?string $slug = 'invoices';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.organization.settings.pages.invoices';

    public ?Organization $organization = null;

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);

        $this->organization = $organization;
    }

    public function getTitle(): string
    {
        return 'Invoices';
    }

    public static function getNavigationLabel(): string
    {
        return 'Invoices';
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getInvoiceRows(): array
    {
        if (! $this->organization?->hasStripeId()) {
            return [];
        }

        $rows = [];

        foreach ($this->organization->invoices(includePending: true) as $invoice) {
            $rows[] = [
                'id' => $invoice->id,
                'date' => $invoice->date()->toDateString(),
                'total' => $invoice->total(),
                'status' => $invoice->status,
                'download_url' => route('cashier.invoice.download', [
                    'invoice' => $invoice->id,
                ], absolute: false),
            ];
        }

        return $rows;
    }
}
