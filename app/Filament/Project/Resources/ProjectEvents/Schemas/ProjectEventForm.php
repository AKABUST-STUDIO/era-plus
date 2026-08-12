<?php

namespace App\Filament\Project\Resources\ProjectEvents\Schemas;

use App\Filament\Project\Resources\ProjectParticipants\Components\ParticipableSelect;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ProjectParticipant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProjectEventForm
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
            Section::make()
                ->hiddenLabel()
                ->contained(false)
                ->columns(4)
                ->components(self::detailsComponents()),
            Section::make()
                ->hiddenLabel()
                ->contained(false)
                ->columns(4)
                ->components(self::whenComponents()),
            Section::make()
                ->hiddenLabel()
                ->contained(false)
                ->columns(4)
                ->components(self::participantsComponents()),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function detailsComponents(): array
    {
        return [
            TextInput::make('title')
                ->hiddenLabel()
                ->validationAttribute(__('events.fields.title'))
                ->placeholder(__('events.fields.title'))
                ->prefixIcon('lucide-tag')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('description')
                ->hiddenLabel()
                ->validationAttribute(__('events.fields.description'))
                ->placeholder(__('events.fields.description'))
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function whenComponents(): array
    {
        return [
            TextInput::make('location')
                ->hiddenLabel()
                ->validationAttribute(__('events.fields.location'))
                ->placeholder(__('events.fields.location'))
                ->prefixIcon('lucide-map-pin')
                ->maxLength(255)
                ->columnSpanFull(),

            DateTimePicker::make('starts_at')
                ->hiddenLabel()
                ->validationAttribute(__('events.fields.starts_at'))
                ->placeholder(__('events.fields.starts_at'))
                ->prefixIcon('lucide-calendar-arrow-up')
                ->seconds(false)
                ->native(false)
                ->required()
                ->columnSpan(2),

            DateTimePicker::make('ends_at')
                ->hiddenLabel()
                ->validationAttribute(__('events.fields.ends_at'))
                ->placeholder(__('events.fields.ends_at'))
                ->prefixIcon('lucide-calendar-arrow-down')
                ->seconds(false)
                ->native(false)
                ->required()
                ->afterOrEqual('starts_at')
                ->columnSpan(2),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function participantsComponents(): array
    {
        return [
            Toggle::make('all_participants')
                ->label(__('events.fields.all_participants'))
                ->helperText(__('events.fields.all_participants_helper'))
                ->default(true)
                ->live()
                ->columnSpanFull(),

            ParticipableSelect::make('participable_refs', excludeAttached: false)
                ->multiple()
                ->placeholder(__('events.fields.participants_placeholder'))
                ->helperText(null)
                ->afterStateUpdated(null)
                ->visible(fn (Get $get): bool => ! $get('all_participants'))
                ->required(fn (Get $get): bool => ! $get('all_participants'))
                ->columnSpanFull(),
        ];
    }

    /**
     * @param  array<int, string>  $refs
     * @return array<int, int>
     */
    public static function refsToProjectParticipantIds(array $refs): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project || $refs === []) {
            return [];
        }

        $participantIds = [];
        $userIds = [];

        foreach ($refs as $ref) {
            if (! is_string($ref) || ! str_contains($ref, ':')) {
                continue;
            }

            [$type, $id] = explode(':', $ref, 2);

            if ($type === 'p') {
                $participantIds[] = (int) $id;
            } elseif ($type === 'u') {
                $userIds[] = (int) $id;
            }
        }

        $ids = [];

        if ($participantIds !== []) {
            $ids = array_merge($ids, ProjectParticipant::query()
                ->where('project_id', $project->id)
                ->where('participable_type', Participant::class)
                ->whereIn('participable_id', $participantIds)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all());
        }

        if ($userIds !== []) {
            $ids = array_merge($ids, ProjectParticipant::query()
                ->where('project_id', $project->id)
                ->where('participable_type', User::class)
                ->whereIn('participable_id', $userIds)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all());
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int, int>  $projectParticipantIds
     * @return array<int, string>
     */
    public static function projectParticipantIdsToRefs(array $projectParticipantIds): array
    {
        if ($projectParticipantIds === []) {
            return [];
        }

        return ProjectParticipant::query()
            ->whereIn('id', $projectParticipantIds)
            ->get(['participable_type', 'participable_id'])
            ->map(fn (ProjectParticipant $pp): string => ($pp->participable_type === User::class ? 'u:' : 'p:').$pp->participable_id)
            ->all();
    }
}
