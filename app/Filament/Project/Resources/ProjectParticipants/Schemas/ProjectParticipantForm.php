<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Schemas;

use App\Filament\Project\Resources\ProjectParticipants\Components\CountrySelect;
use App\Filament\Project\Resources\ProjectParticipants\Components\ParticipableSelect;
use App\Filament\Project\Resources\ProjectParticipants\Components\SendingOrganizationSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProjectParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::sections());
    }

    /**
     * @return array<int, Component>
     */
    public static function sections(): array
    {
        return [
            Section::make(__('participant.sections.participable'))
                ->icon('lucide-circle-user-round')
                ->columns(4)
                ->contained(false)
                ->components(self::participableComponents()),
            Section::make(__('participant.sections.participant_info'))
                ->icon('lucide-at-sign')
                ->visible(fn (Get $get): bool => is_string($get('participable_id')) && str_starts_with($get('participable_id'), 'pending:'))
                ->columns(4)
                ->contained(false)
                ->components(self::participantComponents()),
            Section::make(__('participant.sections.origin'))
                ->contained(false)
                ->icon('lucide-map-pin-house')
                ->visible(fn (Get $get): bool => filled($get('participable_id')))
                ->columns(4)
                ->components(self::originComponents()),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function participableComponents(): array
    {
        return [
            ParticipableSelect::make(),
            Hidden::make('_pending_participable'),
            Hidden::make('_pending_source'),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function participantComponents(): array
    {
        return [
            TextInput::make('participable.name')
                ->hiddenLabel()
                ->placeholder(__('forms.common.name'))
                ->required()
                ->columnSpan(3)
                ->visible(fn (Get $get): bool => $get('_pending_source') !== 'name')
                ->maxLength(255),
            TextInput::make('participable.email')
                ->hiddenLabel()
                ->placeholder(__('forms.common.email'))
                ->email()
                ->live()
                ->helperText(fn ($state): ?string => filled($state)
                    ? __('participant.add.invite_helper')
                    : null)
                ->columnSpan(3)
                ->visible(fn (Get $get): bool => $get('_pending_source') !== 'email')
                ->maxLength(255),
            TextInput::make('participable.phone')
                ->hiddenLabel()
                ->placeholder(__('participant.fields.phone'))
                ->columnSpan(2)
                ->tel()
                ->maxLength(40),
            DatePicker::make('participable.date_of_birth')
                ->hiddenLabel()
                ->columnSpan(2)
                ->placeholder(__('participant.fields.date_of_birth'))
                ->native(false),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function originComponents(): array
    {
        return [
            CountrySelect::make(),
            SendingOrganizationSelect::make(),
        ];
    }
}
