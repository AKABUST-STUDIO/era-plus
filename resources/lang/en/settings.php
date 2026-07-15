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
            'last_admin_description' => 'You are the only Organization Admin. Transfer the role to another member before you can leave.',
            'last_admin_tooltip' => 'Promote another member to Organization Admin first.',
            'action' => 'Leave organization',
            'saved' => 'You have left the organization',
            'only_member_title' => 'You are the only member',
            'only_member_body' => 'Delete the organization instead of leaving it.',
            'last_admin_title' => 'You are the only Organization Admin',
            'last_admin_body' => 'Promote another member to Organization Admin before leaving.',
        ],

        'delete' => [
            'heading' => 'Delete organization',
            'description' => 'Permanently delete this organization and all of its data. This action cannot be undone.',
            'action' => 'Delete organization',
            'saved' => 'Organization deleted',
            'confirm_phrase' => 'delete my organization',
            'modal_heading' => 'Delete :name',
            'modal_description' => 'This permanently removes :name and cancels any active Stripe subscription. To confirm, type the organization name and the phrase ":phrase".',
            'name_label' => 'Type the organization name (:name)',
            'phrase_label' => 'Type ":phrase"',
            'name_mismatch' => 'Name does not match the organization.',
            'phrase_mismatch' => 'Phrase does not match.',
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
            'you' => 'You',
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

        'plan' => [
            'heading' => 'Plan',
            'current_label' => 'Current plan',
            'basic_pitch' => 'Upgrade to Pro for unlimited projects, advanced reporting, and priority support.',
            'upgrade' => 'Upgrade plan',
            'manage' => 'Manage subscription',
            'pro_active' => 'You are on the Pro plan. Unlimited projects.',
        ],

        'payment_method' => [
            'heading' => 'Payment method',
            'description' => 'Card details used for subscription charges.',
            'none' => 'No payment method on file.',
            'card_on_file' => ':type ending in :last4',
            'manage' => 'Manage payment method',
        ],

        'address' => [
            'heading' => 'Billing address',
            'description' => 'Used on invoices and for tax determination.',
            'line1' => 'Address line 1',
            'line2' => 'Address line 2',
            'city' => 'City',
            'state' => 'State or region',
            'postal_code' => 'Postal code',
            'country' => 'Country',
            'action' => 'Save billing address',
            'saved' => 'Billing address updated',
        ],

        'language' => [
            'heading' => 'Invoice language',
            'description' => 'Language used on invoice PDFs and billing emails.',
            'field' => 'Language',
            'action' => 'Save language',
            'saved' => 'Invoice language updated',
        ],

        'tax' => [
            'heading' => 'Tax ID',
            'description' => 'VAT or tax identification number printed on invoices.',
            'field' => 'Tax ID',
            'action' => 'Save tax ID',
            'saved' => 'Tax ID updated',
        ],
    ],

    'activity' => [
        'navigation_label' => 'Activity',
        'title' => 'Activity',
    ],

    'two_factor_required' => [
        'title' => 'Two-factor authentication required',
        'heading' => ':organization requires two-factor authentication',
        'body' => 'This organization enforces two-factor authentication for every member. Set up an authenticator app on your account to continue.',
        'set_up' => 'Set up two-factor',
    ],

    'email_verification_required' => [
        'title' => 'Email verification required',
        'heading' => ':organization requires a verified email address',
        'body' => 'We sent a verification link to :email. Open it from any device, then return here.',
        'resend' => 'Resend verification email',
        'resend_sent' => 'Verification email sent',
        'refresh' => 'I have verified — refresh',
    ],
];
