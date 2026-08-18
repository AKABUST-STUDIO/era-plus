<?php

namespace App\Filament\User\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Override;

class Invoices extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 52;

    protected string $view = 'filament.user.pages.invoices';

    #[Override]
    public static function canAccess(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('user.billing.invoices.title');
    }

    public function getTitle(): string
    {
        return __('user.billing.invoices.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->invoiceRows())
            ->paginated(false)
            ->columns([
                TextColumn::make('date')
                    ->label(__('user.billing.invoices.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('user.billing.invoices.description')),
                TextColumn::make('total')
                    ->label(__('user.billing.invoices.total')),
                TextColumn::make('status')
                    ->label(__('user.billing.invoices.status'))
                    ->badge(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('user.billing.invoices.view'))
                    ->icon('lucide-external-link')
                    ->url(fn (array $record): ?string => $record['hosted_invoice_url'])
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading(__('user.billing.invoices.empty_heading'))
            ->emptyStateDescription(__('user.billing.invoices.empty_description'))
            ->emptyStateIcon('lucide-file-text');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function invoiceRows(): array
    {
        $user = auth()->user();

        if (! $user->hasStripeId()) {
            return [];
        }

        return collect($user->invoices(includePending: true))
            ->map(fn ($invoice, int $key): array => [
                'id' => $key,
                'stripe_id' => $invoice->id,
                'date' => $invoice->date()->toDateString(),
                'description' => $invoice->lines->data[0]->description ?? '—',
                'total' => $invoice->total(),
                'status' => $invoice->status,
                'hosted_invoice_url' => $invoice->hosted_invoice_url ?? null,
            ])
            ->values()
            ->all();
    }
}
