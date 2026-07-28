<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Components;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\ParticipantOrganization;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;

class SendingOrganizationSelect
{
    public static function make(string $name = 'sending_organization_id'): Select
    {
        $load = function (?string $search): array {
            $results = [];

            $project = Filament::getTenant();
            $currentOrganizationId = $project instanceof Project ? $project->organization_id : null;

            Organization::query()
                ->when(filled($search), fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->when($currentOrganizationId, fn ($query, $id) => $query->whereKeyNot($id))
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->each(function (Organization $organization) use (&$results): void {
                    $results['o:'.$organization->getKey()] = view('filament.participants.organization-option', ['record' => $organization])->render();
                });

            ParticipantOrganization::query()
                ->when(filled($search), fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->each(function (ParticipantOrganization $organization) use (&$results): void {
                    $results['po:'.$organization->getKey()] = view('filament.participants.organization-option', ['record' => $organization])->render();
                });

            if (filled($search)) {
                $results['__quick_add__'.$search] = __('quick-add::quick-add.add', ['term' => $search]);
            }

            return $results;
        };

        return Select::make($name)
            ->hiddenLabel()
            ->validationAttribute(__('participant.fields.sending_organization'))
            ->placeholder(__('participant.fields.sending_organization'))
            ->searchPrompt(__('participant.fields.sending_organization_search_prompt'))
            ->required()
            ->searchable()
            ->preload()
            ->allowHtml()
            ->live()
            ->columnSpanFull()
            ->options(fn (): array => $load(null))
            ->getSearchResultsUsing($load)
            ->getOptionLabelUsing(fn ($value): ?string => filled($record = self::resolve($value))
                ? view('filament.participants.organization-option', ['record' => $record])->render()
                : null)
            ->afterStateUpdated(function (Select $component, $state): void {
                if (! is_string($state) || ! str_starts_with($state, '__quick_add__')) {
                    return;
                }

                $searchTerm = substr($state, strlen('__quick_add__'));
                $newRecord = ParticipantOrganization::create(['name' => $searchTerm]);

                $component->state('po:'.$newRecord->getKey());
                $component->refreshSelectedOptionLabel();
            });
    }

    public static function resolve(?string $ref): Organization|ParticipantOrganization|null
    {
        if (blank($ref) || ! str_contains($ref, ':')) {
            return null;
        }

        [$type, $id] = explode(':', $ref, 2);

        return match ($type) {
            'o' => Organization::find((int) $id),
            'po' => ParticipantOrganization::find((int) $id),
            default => null,
        };
    }
}
