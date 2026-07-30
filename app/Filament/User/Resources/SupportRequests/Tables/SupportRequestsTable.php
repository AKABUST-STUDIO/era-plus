<?php

namespace App\Filament\User\Resources\SupportRequests\Tables;

use App\Enums\SupportRequest\SupportRequestStatus;
use App\Models\SupportRequest;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class SupportRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction('view')
            ->defaultSort('created_at', 'desc')
            ->defaultSortOptionLabel(__('user.support.opened'))
            ->columns([
                TextColumn::make('subject')
                    ->label(__('user.support.subject'))
                    ->description(fn (SupportRequest $record): string => (string) str($record->body)->limit(150))
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->grow(),
                TextColumn::make('status')
                    ->label(__('user.support.status'))
                    ->badge()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label(__('user.support.opened'))
                    ->since()
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filters([
                SelectFilter::make('status')->options(SupportRequestStatus::class),
            ])
            ->recordActions([
                ViewAction::make()
                    ->hidden()
                    ->modalHeading(function (SupportRequest $record): Htmlable {
                        $badge = Blade::render(
                            '<x-filament::badge :color="$color">{{ $label }}</x-filament::badge>',
                            [
                                'color' => $record->status->getColor(),
                                'label' => $record->status->getLabel(),
                            ],
                        );

                        return new HtmlString(
                            '<span class="inline-flex items-center gap-2">'.e($record->subject).' '.$badge.'</span>',
                        );
                    })
                    ->modalWidth(Width::Large)
                    ->modalCloseButton(false)
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->schema(self::viewSchema()),
            ])
            ->emptyStateHeading(__('user.support.empty.heading'))
            ->emptyStateDescription(__('user.support.empty.description'))
            ->emptyStateIcon('lucide-life-buoy');
    }

    /**
     * @return array<int, Component>
     */
    private static function viewSchema(): array
    {
        return [
            TextEntry::make('body')->label(__('user.support.body')),
            TextEntry::make('resolution')
                ->label(__('user.support.resolution'))
                ->placeholder(__('user.support.resolution_pending'))
                ->visible(fn (SupportRequest $record): bool => filled($record->resolution)),
        ];
    }
}
