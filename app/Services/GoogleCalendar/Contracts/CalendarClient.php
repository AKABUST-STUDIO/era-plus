<?php

declare(strict_types=1);

namespace App\Services\GoogleCalendar\Contracts;

interface CalendarClient
{
    public function setSubject(?string $email): void;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createCalendar(array $payload): array;

    public function deleteCalendar(string $calendarId): void;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createEvent(string $calendarId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateEvent(string $calendarId, string $eventId, array $payload): array;

    public function deleteEvent(string $calendarId, string $eventId): void;

    /**
     * @return array<string, mixed>
     */
    public function getEvent(string $calendarId, string $eventId): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listEvents(string $calendarId): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function watchEvents(string $calendarId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function stopChannel(array $payload): void;
}
