<?php

namespace App\Filament\Project\Resources\ProjectEvents\Widgets;

use App\Filament\Project\Resources\ProjectEvents\Pages\ListProjectEvents;
use App\Filament\Project\Resources\ProjectEvents\Schemas\ProjectEventForm;
use App\Filament\Project\Resources\ProjectEvents\Schemas\ProjectEventInfolist;
use App\Models\Project;
use App\Models\Project\ProjectEvent;
use App\Models\Project\ProjectParticipant;
use App\Services\GoogleCalendar\EventService;
use App\Support\GoogleCalendarEventColor;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use Saade\FilamentFullCalendar\Actions\DeleteAction;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Actions\ViewAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class ProjectEventsCalendar extends FullCalendarWidget
{
    public Model|string|null $model = ProjectEvent::class;

    protected int|string|array $columnSpan = 'full';

    public function config(): array
    {
        $config = [
            'headerToolbar' => false,
            'initialView' => 'timeGridWeek',
            'height' => '100%',
            'expandRows' => true,
            'selectable' => true,
            'editable' => Filament::auth()->user()?->can('updateAny', ProjectEvent::class) ?? false,
            'nowIndicator' => true,
            'slotMinTime' => '06:00:00',
            'slotMaxTime' => '22:00:00',
            'firstDay' => 1,
        ];

        $tenant = Filament::getTenant();

        if ($tenant instanceof Project) {
            $firstUpcoming = ProjectEvent::query()
                ->where('project_id', $tenant->id)
                ->where('starts_at', '>=', now()->startOfDay())
                ->orderBy('starts_at')
                ->value('starts_at');

            if ($firstUpcoming !== null) {
                $config['initialDate'] = $firstUpcoming->toDateString();
            }
        }

        return $config;
    }

    public function fetchEvents(array $info): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Project) {
            return [];
        }

        return ProjectEvent::query()
            ->with(['attendees.projectParticipant.participable'])
            ->where('project_id', $tenant->id)
            ->whereBetween('starts_at', [$info['start'], $info['end']])
            ->get()
            ->map(function (ProjectEvent $activity): array {
                $color = GoogleCalendarEventColor::for((string) $activity->id);

                $attendees = $activity->attendees
                    ->map(function ($a): ?array {
                        $person = $a->projectParticipant?->participable;

                        if ($person === null) {
                            return null;
                        }

                        $name = (string) $person->name;

                        return [
                            'name' => $name,
                            'email' => (string) $person->email,
                            'avatarHtml' => view('filament.project.events.avatar-chip', [
                                'url' => $a->projectParticipant->avatarUrl(),
                                'name' => $name,
                            ])->render(),
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id' => (string) $activity->id,
                    'title' => $activity->title,
                    'start' => $activity->starts_at?->toIso8601String(),
                    'end' => $activity->ends_at?->toIso8601String(),
                    'allDay' => $activity->all_day,
                    'backgroundColor' => $color['hex'],
                    'borderColor' => $color['hex'],
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'location' => $activity->location,
                        'description' => $activity->description,
                        'attendees' => $attendees,
                    ],
                ];
            })
            ->all();
    }

    public function eventContent(): string
    {
        return <<<'JS'
            function ({ event, timeText, view }) {
                const attendees = event.extendedProps.attendees || [];
                const MAX = 3;
                const visible = attendees.slice(0, MAX);
                const overflow = attendees.length - visible.length;

                const durationMs = (event.end && event.start)
                    ? (event.end.getTime() - event.start.getTime())
                    : 0;
                const showAvatars = event.allDay || durationMs >= 3600000;

                const avatarHtml = showAvatars ? visible.map(a => a.avatarHtml).join('') : '';

                const overflowHtml = showAvatars && overflow > 0
                    ? `<span class="fc-avatar fc-avatar-overflow">+${overflow}</span>`
                    : '';

                const timeHtml = timeText ? `<div class="fc-event-time">${timeText}</div>` : '';
                const titleHtml = `<div class="fc-event-title">${event.title}</div>`;
                const avatarsWrap = showAvatars && (visible.length || overflow > 0)
                    ? `<div class="fc-avatars">${avatarHtml}${overflowHtml}</div>`
                    : '';

                const html = `<div class="fc-event-body">${timeHtml}${titleHtml}${avatarsWrap}</div>`;

                return { html };
            }
        JS;
    }

    public function onDateSelect(string $start, ?string $end, bool $allDay, ?array $view, ?array $resource): void
    {
        $this->mountAction('create', [
            'type' => 'select',
            'start' => $start,
            'end' => $end,
            'allDay' => $allDay,
            'resource' => $resource,
        ]);
    }

    public function getFormSchema(): array
    {
        return ProjectEventForm::sections();
    }

    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::Large)
                ->modalCloseButton(false)
                ->modalCancelAction(false)
                ->modalHeading(false)
                ->modalSubmitActionLabel(__('events.actions.create'))
                ->modalFooterActionsAlignment(Alignment::End)
                ->createAnother(true)
                ->createAnotherAction(fn (Action $action) => $action
                    ->label(__('events.actions.create_another'))
                    ->color('gray'))
                ->preserveFormDataWhenCreatingAnother(fn (array $data): array => $data)
                ->authorize(fn (): bool => Filament::auth()->user()?->can('create', ProjectEvent::class) ?? false)
                ->mountUsing(function ($form, array $arguments): void {
                    $start = $arguments['start'] ?? null;
                    $end = $arguments['end'] ?? null;

                    if (! is_string($start) || $start === '') {
                        return;
                    }

                    $allDay = (bool) ($arguments['allDay'] ?? false);
                    $startCarbon = Carbon::parse($start);
                    $endCarbon = is_string($end) && $end !== ''
                        ? Carbon::parse($end)
                        : $startCarbon->copy()->addHour();

                    if ($allDay) {
                        $startCarbon = $startCarbon->startOfDay();
                        $endCarbon = $endCarbon->startOfDay();
                    }

                    $form->fill([
                        'all_participants' => true,
                        'starts_at' => $startCarbon->toDateTimeString(),
                        'ends_at' => $endCarbon->toDateTimeString(),
                    ]);
                })
                ->using(function (array $data): ProjectEvent {
                    $participantIds = ListProjectEvents::resolveParticipantIds($data);

                    unset($data['participable_refs'], $data['all_participants']);

                    $event = new ProjectEvent($data);
                    $event->created_by = Filament::auth()->id();
                    $event->save();

                    return app(EventService::class)->create($event, $participantIds);
                })
                ->successNotification(fn () => Notification::make()->success()->title(__('events.notifications.created'))),
        ];
    }

    protected function modalActions(): array
    {
        return [
            ViewAction::make()
                ->modalWidth(Width::Large)
                ->modalCloseButton(false)
                ->modalHeading(false)
                ->modalFooterActionsAlignment(Alignment::End)
                ->modalFooterActions(function (): array {
                    $byName = [];

                    foreach ($this->getCachedFormActions() as $action) {
                        $byName[$action->getName()] = $action;
                    }

                    return array_values(array_filter([
                        $byName['delete'] ?? null,
                        $byName['edit'] ?? null,
                    ]));
                })
                ->authorize(fn (Model $record): bool => Filament::auth()->user()?->can('view', $record) ?? false)
                ->infolist(ProjectEventInfolist::components()),

            EditAction::make()
                ->modalWidth(Width::Large)
                ->modalCloseButton(false)
                ->modalCancelAction(false)
                ->modalHeading(false)
                ->modalSubmitActionLabel(__('events.actions.save'))
                ->modalFooterActionsAlignment(Alignment::End)
                ->authorize(fn (): bool => Filament::auth()->user()?->can('updateAny', ProjectEvent::class) ?? false)
                ->schema(ProjectEventForm::sections())
                ->mountUsing(function ($record, $form): void {
                    if (! $record instanceof ProjectEvent) {
                        return;
                    }

                    $projectParticipantIds = $record->attendees->pluck('project_participant_id')
                        ->map(fn ($id): int => (int) $id)
                        ->all();

                    $totalParticipants = ProjectParticipant::query()
                        ->where('project_id', $record->project_id)
                        ->count();

                    $form->fill([
                        'title' => $record->title,
                        'description' => $record->description,
                        'location' => $record->location,
                        'starts_at' => $record->starts_at?->toDateTimeString(),
                        'ends_at' => $record->ends_at?->toDateTimeString(),
                        'all_participants' => count($projectParticipantIds) === $totalParticipants && $totalParticipants > 0,
                        'participable_refs' => ProjectEventForm::projectParticipantIdsToRefs($projectParticipantIds),
                    ]);
                })
                ->using(function (Model $record, array $data): ProjectEvent {
                    if (! $record instanceof ProjectEvent) {
                        throw new RuntimeException('Unexpected record type.');
                    }

                    $participantIds = ListProjectEvents::resolveParticipantIds($data);

                    unset($data['participable_refs'], $data['all_participants']);

                    $record->fill($data);
                    $record->save();

                    return app(EventService::class)->update($record, $participantIds);
                })
                ->successNotification(fn () => Notification::make()->success()->title(__('events.notifications.updated'))),

            DeleteAction::make()
                ->modalWidth(Width::ExtraSmall)
                ->modalCloseButton(false)
                ->modalCancelAction(false)
                ->authorize(fn (Model $record): bool => Filament::auth()->user()?->can('delete', $record) ?? false)
                ->before(function (Model $record): void {
                    if ($record instanceof ProjectEvent) {
                        app(EventService::class)->delete($record);
                    }
                })
                ->successNotification(fn () => Notification::make()->success()->title(__('events.notifications.deleted'))),
        ];
    }

    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        $activity = ProjectEvent::find($event['id'] ?? null);

        if ($activity === null) {
            return true;
        }

        if (! (Filament::auth()->user()?->can('update', $activity) ?? false)) {
            return true;
        }

        $activity->starts_at = Carbon::parse($event['start']);
        $activity->ends_at = Carbon::parse($event['end'] ?? $event['start']);
        $activity->save();

        app(EventService::class)->update(
            $activity,
            $activity->attendees()->pluck('project_participant_id')->all(),
        );

        $this->refreshRecords();

        return false;
    }

    public function onEventResize(array $event, array $oldEvent, array $relatedEvents, array $startDelta, array $endDelta): bool
    {
        return $this->onEventDrop($event, $oldEvent, $relatedEvents, [], null, null);
    }
}
