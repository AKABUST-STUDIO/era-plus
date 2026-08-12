<?php

declare(strict_types=1);

namespace App\Filament\Project\Resources\ProjectEvents\Schemas;

use App\Models\Project\ProjectEvent;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    /**
     * @return array<int, mixed>
     */
    public static function components(): array
    {
        return [
            Section::make()
                ->hiddenLabel()
                ->contained(false)
                ->columns(1)
                ->components([
                    TextEntry::make('title')
                        ->hiddenLabel()
                        ->weight('bold')
                        ->size('lg'),

                    TextEntry::make('description')
                        ->hiddenLabel()
                        ->visible(fn (ProjectEvent $record): bool => filled($record->description)),

                    TextEntry::make('when')
                        ->hiddenLabel()
                        ->icon('lucide-calendar-clock')
                        ->state(fn (ProjectEvent $record): string => sprintf(
                            '%s → %s',
                            $record->starts_at?->format('D, M j · H:i') ?? '—',
                            $record->ends_at?->format('D, M j · H:i') ?? '—',
                        )),

                    TextEntry::make('location')
                        ->hiddenLabel()
                        ->icon('lucide-map-pin')
                        ->visible(fn (ProjectEvent $record): bool => filled($record->location)),

                    ViewEntry::make('attendees')
                        ->hiddenLabel()
                        ->view('filament.project.events.attendees'),
                ]),
        ];
    }
}
