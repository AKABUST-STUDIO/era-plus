<?php

return [
    'menu' => [
        'account' => 'Your account',
        'upgrade' => 'Upgrade to Pro',
        'logout' => 'Sign out',
        'language' => 'Language',
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
        'model' => 'Support ticket',
        'action' => 'Support',
        'subject' => 'Subject',
        'body' => 'Description',
        'status' => 'Status',
        'resolution' => 'Resolution',
        'resolution_pending' => 'Pending',
        'opened' => 'Opened',
        'resolved_at' => 'Resolved',
        'submit' => 'Submit',
        'new' => [
            'heading' => 'New support ticket',
            'description' => 'Tell us what happened. We\'ll get back to you as soon as we can.',
            'email_note' => 'You can also send us an email at <strong>support@rasmo.eu</strong>.',
        ],
        'empty' => [
            'heading' => 'No support tickets yet',
            'description' => 'When you open a support ticket, it will appear here.',
        ],
        'actions' => [
            'new' => 'New ticket',
        ],
    ],

    'feedback' => [
        'action' => 'Feedback',
        'heading' => 'Send us feedback',
        'description' => 'Tell us what\'s working, what isn\'t, and what you\'d like to see next.',
        'subject' => 'Subject',
        'description_field' => 'Tell us more…',
        'terms' => 'Sending feedback means you agree with the :terms and :policy.',
        'terms_link' => 'Terms of Service',
        'policy_link' => 'Privacy Policy',
        'submit' => 'Send feedback',
        'submitted' => 'Thanks — your feedback has been sent.',
    ],

    'authentication' => [
        'title' => 'Authentication',
        'passkeys' => [
            'heading' => 'Passkeys',
            'description' => 'Sign in without a password using your device\'s biometric sensor or a security key.',
            'register' => 'Register a passkey',
            'name_prompt' => 'Name this passkey (e.g. My MacBook)',
            'register_failed' => 'Could not register passkey',
            'empty' => 'You haven\'t registered any passkeys yet.',
            'added' => 'Added :time',
            'last_used' => 'last used :time',
            'remove' => 'Remove',
            'remove_confirm' => 'Remove this passkey?',
        ],
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
