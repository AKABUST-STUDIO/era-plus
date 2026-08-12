<?php

declare(strict_types=1);

namespace App\Services\GoogleCalendar;

use App\Enums\Project\AttendeeResponseStatus;
use App\Models\Project;
use App\Models\Project\ProjectEvent;
use App\Models\Project\ProjectEventAttendee;
use App\Models\Project\ProjectParticipant;
use App\Services\GoogleCalendar\Contracts\CalendarClient;
use App\Support\GoogleCalendarCredentials;
use App\Support\GoogleCalendarEventColor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventService
{
    public function __construct(
        private readonly CalendarClient $client,
        private readonly CalendarService $calendarService,
    ) {}

    /**
     * @param  array<int, int>  $participantIds
     */
    public function create(ProjectEvent $event, array $participantIds): ProjectEvent
    {
        $project = $event->project ?? Project::findOrFail($event->project_id);
        $calendarId = $this->calendarService->ensureCalendarForProject($project);

        $participants = $this->resolveParticipants($project, $participantIds);
        $payload = $this->buildPayload($event, $participants);

        $this->client->setSubject(GoogleCalendarCredentials::impersonate());

        try {
            $response = $this->client->createEvent($calendarId, $payload);
        } catch (\Throwable $e) {
            $event->delete();

            throw $e;
        }

        $event->forceFill([
            'google_event_id' => $response['id'] ?? null,
            'google_updated_at' => $this->parseTimestamp($response['updated'] ?? null),
        ])->save();

        $this->syncAttendees($event, $participants, $response['attendees'] ?? []);

        return $event->fresh(['attendees']);
    }

    /**
     * @param  array<int, int>  $participantIds
     */
    public function update(ProjectEvent $event, array $participantIds): ProjectEvent
    {
        $project = $event->project ?? Project::findOrFail($event->project_id);
        $calendarId = $this->calendarService->ensureCalendarForProject($project);

        $participants = $this->resolveParticipants($project, $participantIds);
        $payload = $this->buildPayload($event, $participants);

        $this->client->setSubject(GoogleCalendarCredentials::impersonate());

        if (empty($event->google_event_id)) {
            $response = $this->client->createEvent($calendarId, $payload);
        } else {
            $response = $this->client->updateEvent($calendarId, $event->google_event_id, $payload);
        }

        $event->forceFill([
            'google_event_id' => $response['id'] ?? $event->google_event_id,
            'google_updated_at' => $this->parseTimestamp($response['updated'] ?? null),
        ])->save();

        $this->syncAttendees($event, $participants, $response['attendees'] ?? []);

        return $event->fresh(['attendees']);
    }

    public function delete(ProjectEvent $event): void
    {
        if (empty($event->google_event_id)) {
            return;
        }

        $project = $event->project ?? Project::withoutGlobalScopes()->find($event->project_id);

        if ($project === null || empty($project->google_calendar_id)) {
            return;
        }

        $this->client->setSubject(GoogleCalendarCredentials::impersonate());
        $this->client->deleteEvent($project->google_calendar_id, $event->google_event_id);
    }

    public function syncFromGoogle(ProjectEvent $event): ProjectEvent
    {
        $project = $event->project ?? Project::findOrFail($event->project_id);

        if (empty($project->google_calendar_id) || empty($event->google_event_id)) {
            return $event;
        }

        $remote = $this->client->getEvent($project->google_calendar_id, $event->google_event_id);

        $this->applyRemote($event, $remote);

        return $event->fresh(['attendees']);
    }

    public function syncAllForProject(Project $project): void
    {
        if (empty($project->google_calendar_id)) {
            return;
        }

        $remoteEvents = $this->client->listEvents($project->google_calendar_id);

        foreach ($remoteEvents as $remote) {
            if (($remote['status'] ?? null) === 'cancelled') {
                ProjectEvent::withoutGlobalScopes()
                    ->where('project_id', $project->id)
                    ->where('google_event_id', $remote['id'] ?? null)
                    ->get()
                    ->each->delete();

                continue;
            }

            $eventId = $remote['id'] ?? null;

            if ($eventId === null) {
                continue;
            }

            $event = ProjectEvent::withoutGlobalScopes()
                ->where('project_id', $project->id)
                ->where('google_event_id', $eventId)
                ->first();

            if ($event === null) {
                $event = new ProjectEvent([
                    'project_id' => $project->id,
                    'google_event_id' => $eventId,
                    'title' => $remote['summary'] ?? '(untitled)',
                    'starts_at' => $this->parseTimestamp($remote['start']['dateTime'] ?? $remote['start']['date'] ?? null),
                    'ends_at' => $this->parseTimestamp($remote['end']['dateTime'] ?? $remote['end']['date'] ?? null),
                ]);
                $event->save();
            }

            $this->applyRemote($event, $remote);
        }
    }

    /**
     * @param  array<int, int>  $participantIds
     * @return Collection<int, ProjectParticipant>
     */
    private function resolveParticipants(Project $project, array $participantIds): Collection
    {
        if ($participantIds === []) {
            return collect();
        }

        return ProjectParticipant::withoutGlobalScopes()
            ->with('participable')
            ->where('project_id', $project->id)
            ->whereIn('id', $participantIds)
            ->get();
    }

    /**
     * @param  Collection<int, ProjectParticipant>  $participants
     * @return array<string, mixed>
     */
    private function buildPayload(ProjectEvent $event, Collection $participants): array
    {
        $project = $event->project ?? Project::findOrFail($event->project_id);
        $timezone = $project->timezone ?: 'UTC';

        $attendees = $participants
            ->map(fn (ProjectParticipant $p): ?array => $this->attendeeFromParticipant($p))
            ->filter()
            ->values()
            ->all();

        $seenEmails = array_flip(array_map(
            fn (array $a): string => mb_strtolower((string) ($a['email'] ?? '')),
            $attendees,
        ));

        foreach ($project->users as $staff) {
            $email = mb_strtolower((string) $staff->email);

            if ($email === '' || isset($seenEmails[$email])) {
                continue;
            }

            $attendees[] = [
                'email' => $staff->email,
                'displayName' => $staff->name ?: $staff->email,
                'responseStatus' => AttendeeResponseStatus::NeedsAction->value,
            ];
            $seenEmails[$email] = true;
        }

        $color = GoogleCalendarEventColor::for((string) $event->id);

        if ($event->all_day) {
            $startDate = Carbon::parse($event->starts_at)->toDateString();
            $endDate = Carbon::parse($event->ends_at)->toDateString();

            if ($endDate === $startDate) {
                $endDate = Carbon::parse($event->ends_at)->addDay()->toDateString();
            }

            $start = ['date' => $startDate];
            $end = ['date' => $endDate];
        } else {
            $start = ['dateTime' => Carbon::parse($event->starts_at)->toRfc3339String(), 'timeZone' => $timezone];
            $end = ['dateTime' => Carbon::parse($event->ends_at)->toRfc3339String(), 'timeZone' => $timezone];
        }

        $payload = [
            'summary' => $event->title,
            'description' => (string) ($event->description ?? ''),
            'location' => (string) ($event->location ?? ''),
            'start' => $start,
            'end' => $end,
            'colorId' => (string) $color['id'],
            'guestsCanSeeOtherGuests' => true,
        ];

        if ($attendees !== []) {
            $payload['attendees'] = $attendees;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function attendeeFromParticipant(ProjectParticipant $participant): ?array
    {
        $participable = $participant->participable;

        $email = $participable?->email ?? null;

        if ($email === null || $email === '') {
            return null;
        }

        return [
            'email' => $email,
            'displayName' => $participable?->name ?? $email,
            'responseStatus' => AttendeeResponseStatus::NeedsAction->value,
        ];
    }

    /**
     * @param  Collection<int, ProjectParticipant>  $participants
     * @param  array<int, array<string, mixed>>  $remoteAttendees
     */
    private function syncAttendees(ProjectEvent $event, Collection $participants, array $remoteAttendees): void
    {
        $statusByEmail = [];

        foreach ($remoteAttendees as $remote) {
            if (isset($remote['email'])) {
                $statusByEmail[mb_strtolower((string) $remote['email'])] = AttendeeResponseStatus::fromGoogle($remote['responseStatus'] ?? null);
            }
        }

        DB::transaction(function () use ($event, $participants, $statusByEmail): void {
            $keepIds = [];

            foreach ($participants as $participant) {
                $email = mb_strtolower((string) ($participant->participable?->email ?? ''));
                $status = $statusByEmail[$email] ?? AttendeeResponseStatus::NeedsAction;

                $attendee = ProjectEventAttendee::firstOrNew([
                    'project_event_id' => $event->id,
                    'project_participant_id' => $participant->id,
                ]);
                $attendee->response_status = $status;
                $attendee->save();

                $keepIds[] = $attendee->id;
            }

            ProjectEventAttendee::query()
                ->where('project_event_id', $event->id)
                ->when($keepIds !== [], fn ($q) => $q->whereNotIn('id', $keepIds))
                ->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function applyRemote(ProjectEvent $event, array $remote): void
    {
        $event->forceFill([
            'title' => $remote['summary'] ?? $event->title,
            'description' => $remote['description'] ?? $event->description,
            'location' => $remote['location'] ?? $event->location,
            'starts_at' => $this->parseTimestamp($remote['start']['dateTime'] ?? $remote['start']['date'] ?? null) ?? $event->starts_at,
            'ends_at' => $this->parseTimestamp($remote['end']['dateTime'] ?? $remote['end']['date'] ?? null) ?? $event->ends_at,
            'google_updated_at' => $this->parseTimestamp($remote['updated'] ?? null) ?? $event->google_updated_at,
        ])->save();

        $this->applyRemoteAttendeeStatuses($event, $remote['attendees'] ?? []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $remoteAttendees
     */
    private function applyRemoteAttendeeStatuses(ProjectEvent $event, array $remoteAttendees): void
    {
        if ($remoteAttendees === []) {
            return;
        }

        $statusByEmail = [];

        foreach ($remoteAttendees as $remote) {
            if (isset($remote['email'])) {
                $statusByEmail[mb_strtolower((string) $remote['email'])] = AttendeeResponseStatus::fromGoogle($remote['responseStatus'] ?? null);
            }
        }

        $event->attendees()->with('projectParticipant.participable')->get()->each(function (ProjectEventAttendee $attendee) use ($statusByEmail): void {
            $email = mb_strtolower((string) ($attendee->projectParticipant?->participable?->email ?? ''));

            if ($email === '' || ! isset($statusByEmail[$email])) {
                return;
            }

            $attendee->response_status = $statusByEmail[$email];
            $attendee->save();
        });
    }

    private function parseTimestamp(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
