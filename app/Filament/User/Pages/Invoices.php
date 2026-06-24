<?php

namespace App\Filament\User\Pages;

use App\Models\Organization;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class Invoices extends Page
{
    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.user.pages.invoices';

    public function getTitle(): string
    {
        return __('user.invoices.title');
    }

    /**
     * @return Collection<int, Organization>
     */
    public function getOwnedOrganizations(): Collection
    {
        /** @var Collection<int, Organization> $orgs */
        $orgs = Organization::query()
            ->whereHas('users', fn ($q) => $q
                ->whereKey(auth()->id())
                ->where('organization_user.is_admin', true))
            ->orderBy('name')
            ->get();

        return $orgs;
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function getInvoiceRows(): SupportCollection
    {
        $rows = collect();

        foreach ($this->getOwnedOrganizations() as $organization) {
            if (! $organization->hasStripeId()) {
                continue;
            }

            foreach ($organization->invoices(includePending: true) as $invoice) {
                $rows->push([
                    'id' => $invoice->id,
                    'organization' => $organization->name,
                    'date' => $invoice->date()->toDateString(),
                    'total' => $invoice->total(),
                    'status' => $invoice->status,
                    'download_url' => route('cashier.invoice.download', [
                        'invoice' => $invoice->id,
                    ], absolute: false),
                ]);
            }
        }

        return $rows->sortByDesc('date')->values();
    }
}
