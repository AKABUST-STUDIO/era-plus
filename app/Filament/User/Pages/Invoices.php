<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Invoices extends Page
{
    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.user.pages.invoices';

    public function getTitle(): string
    {
        return __('user.invoices.title');
    }

    public function hasBillingAccount(): bool
    {
        return (bool) auth()->user()->hasStripeId();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getInvoiceRows(): Collection
    {
        $user = auth()->user();

        if (! $user->hasStripeId()) {
            return collect();
        }

        return collect($user->invoices(includePending: true))
            ->map(fn ($invoice): array => [
                'id' => $invoice->id,
                'date' => $invoice->date()->toDateString(),
                'total' => $invoice->total(),
                'status' => $invoice->status,
                'download_url' => route('invoices.download', ['invoice' => $invoice->id], absolute: false),
            ])
            ->values();
    }
}
