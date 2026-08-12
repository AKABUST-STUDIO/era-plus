<?php

declare(strict_types=1);

namespace App\Services\GoogleCalendar;

use App\Services\GoogleCalendar\Contracts\CalendarClient;
use Illuminate\Support\Str;

class FakeCalendarClient implements CalendarClient
{
    /** @var array<string, array<string, array<string, mixed>>> */
    public array $calendars = [];

    /** @var array<int, array{type: string, calendar: ?string, payload: array<string, mixed>}> */
    public array $calls = [];

    public ?string $subject = null;

    public function setSubject(?string $email): void
    {
        $this->subject = $email !== null && $email !== '' ? $email : null;
    }

    public function createCalendar(array $payload): array
    {
        $id = 'cal_'.Str::random(12).'@group.calendar.google.com';
        $this->calendars[$id] = [];
        $this->calls[] = ['type' => 'createCalendar', 'calendar' => $id, 'payload' => $payload];

        return array_merge($payload, ['id' => $id]);
    }

    public function deleteCalendar(string $calendarId): void
    {
        unset($this->calendars[$calendarId]);
        $this->calls[] = ['type' => 'deleteCalendar', 'calendar' => $calendarId, 'payload' => []];
    }

    public function createEvent(string $calendarId, array $payload): array
    {
        $eventId = 'evt_'.Str::random(20);
        $event = array_merge($payload, [
            'id' => $eventId,
            'updated' => now()->toRfc3339String(),
        ]);
        $this->calendars[$calendarId][$eventId] = $event;
        $this->calls[] = ['type' => 'createEvent', 'calendar' => $calendarId, 'payload' => $payload];

        return $event;
    }

    public function updateEvent(string $calendarId, string $eventId, array $payload): array
    {
        $event = array_merge($this->calendars[$calendarId][$eventId] ?? [], $payload, [
            'id' => $eventId,
            'updated' => now()->toRfc3339String(),
        ]);
        $this->calendars[$calendarId][$eventId] = $event;
        $this->calls[] = ['type' => 'updateEvent', 'calendar' => $calendarId, 'payload' => $payload];

        return $event;
    }

    public function deleteEvent(string $calendarId, string $eventId): void
    {
        unset($this->calendars[$calendarId][$eventId]);
        $this->calls[] = ['type' => 'deleteEvent', 'calendar' => $calendarId, 'payload' => ['eventId' => $eventId]];
    }

    public function getEvent(string $calendarId, string $eventId): array
    {
        return $this->calendars[$calendarId][$eventId] ?? [];
    }

    public function listEvents(string $calendarId): array
    {
        return array_values($this->calendars[$calendarId] ?? []);
    }

    public function watchEvents(string $calendarId, array $payload): array
    {
        $this->calls[] = ['type' => 'watchEvents', 'calendar' => $calendarId, 'payload' => $payload];

        return [
            'id' => $payload['id'] ?? Str::uuid()->toString(),
            'resourceId' => 'resource_'.Str::random(10),
            'expiration' => (string) (now()->addDays(7)->getTimestampMs()),
        ];
    }

    public function stopChannel(array $payload): void
    {
        $this->calls[] = ['type' => 'stopChannel', 'calendar' => null, 'payload' => $payload];
    }
}
