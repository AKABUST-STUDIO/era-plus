<?php

declare(strict_types=1);

namespace App\Services\GoogleCalendar;

use App\Services\GoogleCalendar\Contracts\CalendarClient;
use App\Support\GoogleCalendarCredentials;
use Google\Client;
use Google\Service\Calendar as GoogleCalendarService;
use Google\Service\Calendar\Calendar as GoogleCalendar;
use Google\Service\Calendar\Channel as GoogleChannel;
use Google\Service\Calendar\Event as GoogleEvent;
use RuntimeException;

class GoogleCalendarClient implements CalendarClient
{
    private const SCOPE = 'https://www.googleapis.com/auth/calendar';

    private ?GoogleCalendarService $service = null;

    private ?string $subject = null;

    public function setSubject(?string $email): void
    {
        $normalized = $email !== null && $email !== '' ? $email : null;

        if ($normalized === $this->subject) {
            return;
        }

        $this->subject = $normalized;
        $this->service = null;
    }

    public function createCalendar(array $payload): array
    {
        $calendar = new GoogleCalendar($payload);
        $response = $this->service()->calendars->insert($calendar);

        return $this->toArray($response);
    }

    public function deleteCalendar(string $calendarId): void
    {
        $this->service()->calendars->delete($calendarId);
    }

    public function createEvent(string $calendarId, array $payload): array
    {
        $event = new GoogleEvent($payload);
        $response = $this->service()->events->insert($calendarId, $event, ['sendUpdates' => 'all']);

        return $this->toArray($response);
    }

    public function updateEvent(string $calendarId, string $eventId, array $payload): array
    {
        $event = new GoogleEvent($payload);
        $response = $this->service()->events->update($calendarId, $eventId, $event, ['sendUpdates' => 'all']);

        return $this->toArray($response);
    }

    public function deleteEvent(string $calendarId, string $eventId): void
    {
        $this->service()->events->delete($calendarId, $eventId, ['sendUpdates' => 'all']);
    }

    public function getEvent(string $calendarId, string $eventId): array
    {
        return $this->toArray($this->service()->events->get($calendarId, $eventId));
    }

    public function listEvents(string $calendarId): array
    {
        $items = [];
        $pageToken = null;

        do {
            $params = [
                'showDeleted' => true,
                'singleEvents' => false,
                'maxResults' => 250,
            ];

            if ($pageToken !== null) {
                $params['pageToken'] = $pageToken;
            }

            $events = $this->service()->events->listEvents($calendarId, $params);

            foreach ($events->getItems() as $event) {
                $items[] = $this->toArray($event);
            }

            $pageToken = $events->getNextPageToken();
        } while ($pageToken !== null);

        return $items;
    }

    public function watchEvents(string $calendarId, array $payload): array
    {
        $channel = new GoogleChannel($payload);
        $response = $this->service()->events->watch($calendarId, $channel);

        return $this->toArray($response);
    }

    public function stopChannel(array $payload): void
    {
        $channel = new GoogleChannel($payload);
        $this->service()->channels->stop($channel);
    }

    private function service(): GoogleCalendarService
    {
        if ($this->service !== null) {
            return $this->service;
        }

        $path = GoogleCalendarCredentials::path();

        if (! is_file($path)) {
            throw new RuntimeException('Google Calendar service account credentials not found at: '.$path);
        }

        $client = new Client;
        $client->setAuthConfig($path);
        $client->setScopes([self::SCOPE]);

        $subject = $this->subject ?? GoogleCalendarCredentials::impersonate();

        if ($subject !== null) {
            $client->setSubject($subject);
        }

        return $this->service = new GoogleCalendarService($client);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(mixed $response): array
    {
        if ($response === null) {
            return [];
        }

        $encoded = json_encode($response);

        if ($encoded === false) {
            return [];
        }

        $decoded = json_decode($encoded, true);

        return is_array($decoded) ? $decoded : [];
    }
}
