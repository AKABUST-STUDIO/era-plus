<?php

return [
    'navigation' => [
        'label' => 'Events',
        'singular' => 'Event',
    ],
    'page' => [
        'title' => 'Events',
    ],
    'sections' => [
        'details' => 'Details',
        'details_description' => 'Give the event a name and describe what it is about.',
        'when' => 'When & where',
        'when_description' => 'Location, start and end time.',
        'participants' => 'Participants',
        'participants_description' => 'Choose who will attend this event.',
    ],
    'fields' => [
        'title' => 'Title',
        'description' => 'Description',
        'location' => 'Location',
        'starts_at' => 'Starts',
        'ends_at' => 'Ends',
        'participants' => 'Participants',
        'participants_placeholder' => 'Choose participants…',
        'all_participants' => 'Invite everyone',
        'all_participants_helper' => 'Every participant on this project will be invited. Turn off to pick specific people.',
        'when' => 'When',
        'all_day' => 'All day',
    ],
    'actions' => [
        'create' => 'Create event',
        'create_another' => 'Create & create another',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'today' => 'Today',
        'tools' => 'Tools',
    ],
    'notifications' => [
        'created' => 'Event created and synced to Google Calendar.',
        'updated' => 'Event updated.',
        'deleted' => 'Event deleted.',
    ],
    'response_status' => [
        'needsAction' => 'Awaiting response',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'tentative' => 'Tentative',
    ],
];
