<?php

return [
    'breadcrumb' => 'Settings',

    'work_in_progress' => [
        'heading' => 'Work in progress',
        'description' => 'This page is not available yet. It will be enabled in an upcoming release.',
    ],

    'general' => [
        'navigation_label' => 'General',
        'title' => 'General settings',

        'name' => [
            'heading' => 'Organization name',
            'description' => 'The display name shown to members across the workspace.',
            'field' => 'Name',
            'action' => 'Save name',
            'saved' => 'Organization name updated',
        ],

        'avatar' => [
            'heading' => 'Organization avatar',
            'description' => 'Upload an image to represent this organization across the workspace.',
            'action' => 'Save avatar',
            'saved' => 'Avatar updated',
        ],

        'url' => [
            'heading' => 'Organization URL',
            'description' => 'The slug that identifies this organization in its web address.',
            'field' => 'URL slug',
            'action' => 'Save URL',
            'saved' => 'Organization URL updated',
        ],

        'leave' => [
            'heading' => 'Leave organization',
            'description' => 'Remove yourself from this organization. You will lose access until you are invited back.',
            'action' => 'Leave organization',
            'saved' => 'You have left the organization',
            'only_member_title' => 'You are the only member',
            'only_member_body' => 'Delete the organization instead of leaving it.',
        ],

        'delete' => [
            'heading' => 'Delete organization',
            'description' => 'Permanently delete this organization and all of its data. This action cannot be undone.',
            'action' => 'Delete organization',
            'saved' => 'Organization deleted',
        ],
    ],

    'users' => [
        'navigation_label' => 'Users',
        'title' => 'Users',

        'invite' => [
            'heading' => 'Invite people',
            'description' => 'Send an invitation to join this organization.',
            'email' => 'Email address',
            'action' => 'Send invitation',
            'wip' => 'Invitations are not available yet.',
        ],

        'table' => [
            'name' => 'Name',
            'email' => 'Email',
            'joined' => 'Joined',
        ],
    ],

    'notifications' => [
        'navigation_label' => 'Notifications',
        'title' => 'Notifications',
    ],

    'security' => [
        'navigation_label' => 'Security',
        'title' => 'Security',

        'two_factor' => [
            'heading' => 'Two-factor authentication',
            'description' => 'Require every member to set up two-factor authentication before accessing this organization.',
            'label' => 'Enforce two-factor authentication for all members.',
            'coming_soon' => 'Coming soon',
        ],
    ],

    'billing' => [
        'navigation_label' => 'Billing',
        'title' => 'Billing',
    ],

    'activity' => [
        'navigation_label' => 'Activity',
        'title' => 'Activity',
    ],
];
