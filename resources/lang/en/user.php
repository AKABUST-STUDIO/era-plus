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
        'model' => 'Organization',
        'name' => 'Name',
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
        'information' => [
            'title' => 'Billing Information',
        ],
        'items' => [
            'title' => 'Billing Items',
            'item_description' => 'Visit :name\'s billing settings for details.',
            'view' => 'View Billing Settings',
            'empty' => 'You are not the owner of any organization yet.',
        ],
        'invoices' => [
            'title' => 'Invoices',
            'heading' => 'Invoices',
            'date' => 'Date',
            'description' => 'Description',
            'total' => 'Total',
            'status' => 'Status',
            'view' => 'Open in Stripe',
            'empty_heading' => 'No invoices yet',
            'empty_description' => 'Invoices will appear here once your subscription is billed.',
        ],
        'payment_method' => [
            'heading' => 'Payment Method',
            'description' => 'Payments for your subscriptions are made using the default card.',
            'manage' => 'Manage in Stripe',
        ],
        'invoice_email' => [
            'heading' => 'Invoice Email Recipient',
            'description' => 'By default, all your invoices will be sent to your account\'s email address. If you want to use a custom email address specifically for receiving invoices, enter it here.',
        ],
        'company' => [
            'heading' => 'Company Name',
            'description' => 'By default, your account name is shown on your invoice. If you want to show a custom name instead, please enter it here.',
        ],
        'address' => [
            'heading' => 'Billing Address',
            'description' => 'Used on your invoices.',
            'country' => 'Country',
            'line1' => 'Address line 1',
            'line2' => 'Address line 2',
            'city' => 'City',
            'state' => 'State / Province',
            'postal_code' => 'Postal code',
        ],
        'invoice_language' => [
            'heading' => 'Invoice Language',
            'description' => 'If your billing department is using a different language, enter it here.',
        ],
        'purchase_order' => [
            'heading' => 'Invoice Purchase Order',
            'description' => 'By default, no purchase order line is shown on your account\'s billing invoices. If you want to show a purchase order line, please enter it here.',
        ],
        'tax_id' => [
            'heading' => 'Tax ID',
            'description' => 'If you would like your invoice to render a specific tax ID, enter it here.',
            'type' => 'Type',
            'value' => 'ID',
        ],
        'actions' => [
            'save' => 'Save',
        ],
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
