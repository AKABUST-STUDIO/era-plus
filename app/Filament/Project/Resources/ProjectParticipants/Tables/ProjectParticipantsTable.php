<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Tables;

use App\Models\Project;
use App\Models\Project\ProjectParticipant;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectParticipantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $project = Filament::getTenant();

                if ($project instanceof Project) {
                    $query->where('project_id', $project->id);
                }

                return $query->with(['country', 'sendingOrganization']);
            })
            ->paginated(false)
            ->columns([
                Split::make([])
                    ->extraAttributes(['class' => 'p-4'])
                    ->schema([
                        ImageColumn::make('participable.avatar')
                            ->state(fn (ProjectParticipant $record): string => 'https://ui-avatars.com/api/?name='.urlencode((string) $record->participable?->name).'&size=128')
                            ->circular()
                            ->size(96)
                            ->grow(false),
                        Stack::make([
                            TextColumn::make('verified')
                                ->label(__('forms.common.name'))
                                ->searchable()
                                ->weight('semibold')
                                ->size('lg'),
                            TextColumn::make('participable.name')
                                ->label(__('forms.common.name'))
                                ->searchable()
                                ->weight('semibold')
                                ->size('lg'),
                            TextColumn::make('participable.email')
                                ->label(__('forms.common.email'))
                                ->icon('lucide-mail')
                                ->color('gray')
                                ->searchable()
                                ->visible(fn (?ProjectParticipant $record): bool => (bool) $record?->participable?->email),
                            TextColumn::make('country.name')
                                ->label(__('participant.fields.country'))
                                ->icon(fn (ProjectParticipant $record): string => self::countryIcon($record->country?->iso2))
                                ->color('gray'),
                            TextColumn::make('sendingOrganization.name')
                                ->label(__('participant.fields.sending_organization'))
                                ->icon('lucide-building-2')
                                ->color('gray'),
                        ])->space(1)->alignment('end')->grow(false),
                    ])->from('md'),
            ])
            ->contentGrid([
                'md' => 1,
                'xl' => 2,
                '2xl' => 3,
            ])
            ->toolbarActions([])
            ->emptyStateHeading(__('participant.empty.heading'))
            ->emptyStateDescription(__('participant.empty.description'))
            ->emptyStateIcon('lucide-users')
            ->emptyStateActions([
                CreateAction::make()
                    ->label(__('participant.actions.add')),
            ]);
    }

    public static function countryIcon(?string $iso2): string
    {
        return filled($iso2)
            ? 'flag-4x3-'.strtolower($iso2)
            : 'lucide-flag';
    }
}
