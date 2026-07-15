<?php

return [
    'menu' => [
        'account' => 'Your account',
        'upgrade' => 'Upgrade to Pro',
        'logout' => 'Sign out',
    ],

    'organizations' => [
        'title' => 'Organizations',
        'role' => [
            'owner' => 'Owner',
            'member' => 'Member',
        ],
        'actions' => [
            'open' => 'Open',
            'view' => 'View',
            'manage' => 'Manage',
        ],
        'create' => [
            'heading' => 'Need another organization?',
            'description' => 'Create a new organization to keep separate projects, finances, and members.',
            'action' => 'Create organization',
        ],
    ],

    'settings' => [
        'title' => 'Settings',
    ],

    'activity' => [
        'title' => 'Activity',
        'action' => 'Action',
        'subject' => 'Subject',
        'last_7_days' => 'Last 7 days',
        'last_30_days' => 'Last 30 days',
        'organizations' => [
            'heading' => 'Organizations',
            'description' => 'Jump into the activity log for each organization you belong to.',
            'view' => 'View activity →',
        ],
        'projects' => [
            'heading' => 'Projects',
            'description' => 'Jump into the activity log for each project you\'re a member of.',
            'view' => 'View activity →',
        ],
    ],

    'support' => [
        'title' => 'Support',
        'subject' => 'Subject',
        'priority' => 'Priority',
        'body' => 'Message',
        'status' => 'Status',
        'opened' => 'Opened',
        'submit' => 'Submit',
        'unread' => 'Unread',
        'new' => [
            'heading' => 'New support request',
        ],
        'priorities' => [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
        ],
        'actions' => [
            'reply' => 'Reply',
            'reply_to' => 'Reply to: :subject',
            'reply_sent' => 'Reply sent',
            'resolve' => 'Mark resolved',
            'resolved_sent' => 'Marked as resolved',
            'reopen' => 'Reopen',
            'reopened_sent' => 'Reopened',
        ],
    ],

    'authentication' => [
        'title' => 'Authentication',
    ],

    'billing' => [
        'title' => 'Billing',
        'empty' => 'You don\'t admin any organization yet.',
        'no_payment_method' => 'No payment method on file',
        'manage' => 'Manage in Stripe',
        'upgrade_pro' => 'Upgrade to Pro',
    ],

    'invoices' => [
        'title' => 'Invoices',
        'empty_no_orgs' => 'You don\'t admin any organization yet.',
        'empty_no_invoices' => 'No invoices yet across your organizations.',
        'date' => 'Date',
        'organization' => 'Organization',
        'total' => 'Total',
        'status' => 'Status',
        'download' => 'Download',
    ],
];
