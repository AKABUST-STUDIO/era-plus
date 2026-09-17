<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Components;

use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ProjectParticipant;
use App\Models\User;
use App\Support\EmailUsername;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;

class ParticipableSelect
{
    public static function make(string $name = 'participable_id', bool $excludeAttached = true): Select
    {
        $load = function (?string $search) use ($excludeAttached): array {
            $project = Filament::getTenant();

            if (! $project instanceof Project) {
                return [];
            }

            $rows = ProjectParticipant::query()
                ->where('project_id', $project->id)
                ->get(['participable_type', 'participable_id']);

            $attachedParticipantIds = $rows
                ->where('participable_type', Participant::class)
                ->pluck('participable_id')
                ->all();

            $attachedUserIds = $rows
                ->where('participable_type', User::class)
                ->pluck('participable_id')
                ->all();

            $results = [];

            Participant::query()
                ->when(filled($search), fn ($query) => $query
                    ->where(fn (Builder $query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                )
                ->when($excludeAttached, fn (Builder $query) => $query->whereNotIn('id', $attachedParticipantIds))
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->each(function (Participant $participant) use (&$results): void {
                    $results['p:'.$participant->getKey()] = view('filament.participants.select-option', ['record' => $participant])->render();
                });

            User::query()
                ->whereHas('organizations', fn (Builder $query) => $query->whereKey($project->organization_id))
                ->when(filled($search), fn ($query) => $query
                    ->where(fn (Builder $query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                )
                ->when($excludeAttached, fn (Builder $query) => $query->whereNotIn('id', $attachedUserIds))
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->each(function (User $user) use (&$results): void {
                    $results['u:'.$user->getKey()] = view('filament.participants.select-option', ['record' => $user])->render();
                });

            if (filled($search)) {
                $results['__quick_add__'.$search] = __('quick-add::quick-add.add', ['term' => $search]);
            }

            return $results;
        };

        return Select::make($name)
            ->hiddenLabel()
            ->required()
            ->extraAttributes([
                'x-data' => '{}',
                'x-init' => "setTimeout(() => { const btn = \$el.querySelector('button.fi-select-input-btn'); if (btn && !btn.querySelector('.fi-select-input-value-ctn > :not(.fi-select-input-placeholder)')) btn.click(); }, 50)",
            ])
            ->validationAttribute(__('participant.fields.participable'))
            ->placeholder(__('participant.add.participable_placeholder'))
            ->helperText(fn (Get $get): ?string => $get('_pending_source') === 'email'
                ? __('participant.add.invite_helper')
                : null)
            ->searchable()
            ->searchPrompt(__('participant.add.existing_prompt'))
            ->allowHtml()
            ->live()
            ->preload()
            ->columnSpanFull()
            ->options(fn (): array => $load(null))
            ->getSearchResultsUsing($load)
            ->getOptionLabelUsing(function ($value, Get $get): ?string {
                if (is_string($value) && str_starts_with($value, 'pending:')) {
                    return view('filament.participants.select-option', [
                        'record' => new Participant($get('_pending_participable') ?? []),
                    ])->render();
                }

                return filled($record = self::resolve($value))
                    ? view('filament.participants.select-option', ['record' => $record])->render()
                    : null;
            })
            ->afterStateUpdated(function (Select $component, Set $set, $state): void {
                $set('country_id', null);
                $set('sending_organization_id', null);
                $set('participable', null);
                $set('_pending_participable', null);
                $set('_pending_source', null);

                if (! is_string($state) || ! str_starts_with($state, '__quick_add__')) {
                    return;
                }

                $searchTerm = substr($state, strlen('__quick_add__'));
                $isEmail = str($searchTerm)->contains('@');

                $pending = $isEmail
                    ? ['email' => $searchTerm, 'name' => EmailUsername::toDisplayName($searchTerm)]
                    : ['name' => $searchTerm];

                $component->state('pending:'.md5($searchTerm));
                $component->refreshSelectedOptionLabel();

                $set('_pending_participable', $pending);
                $set('_pending_source', $isEmail ? 'email' : 'name');
                $set('participable.name', $pending['name'] ?? null);
                $set('participable.email', $pending['email'] ?? null);
            });
    }

    public static function resolve(?string $ref): Participant|User|null
    {
        if (blank($ref) || ! str_contains($ref, ':')) {
            return null;
        }

        [$type, $id] = explode(':', $ref, 2);

        return match ($type) {
            'p' => Participant::find((int) $id),
            'u' => User::find((int) $id),
            default => null,
        };
    }
}
