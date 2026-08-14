<?php

return [
    'roles' => [
        'admin' => 'Admin',
        'member' => 'Member',
    ],
    'select_project' => [
        'heading' => 'Select a project',
        'description' => 'This page belongs to a single project. Pick one to continue.',
    ],
    'register' => [
        'label' => 'Create your first organization:',
        'action' => 'Continue',
        'action_corporate' => 'Inquire about corporate offer',
        'name_placeholder' => 'Organization name',
        'included' => 'Included:',
        'plans' => [
            'basic' => [
                'title' => 'Basic',
                'price' => 'Free',
                'features' => [
                    'Unlimited projects',
                    'Participant management with imports and exports',
                    'Organization members and role-based permissions',
                    'Event calendar with Google Calendar sync',
                    'Travel expenses tracking',
                    'Activity log across projects and organization',
                    'Support tickets and feedback',
                ],
            ],
            'pro' => [
                'title' => 'Pro',
                'price' => 'Free',
                'price_original' => '€99 / year',
                'badge' => 'Early Bird Access',
                'features' => [
                    'Unlimited projects',
                    'Participant management with imports and exports',
                    'Organization members and role-based permissions',
                    'Event calendar with Google Calendar sync',
                    'Travel expenses tracking',
                    'Activity log across projects and organization',
                    'Support tickets and feedback',
                    'Everything in Basic and many more',
                ],
            ],
            'corporate' => [
                'title' => 'Corporate',
                'price' => 'ask for quote',
                'features' => [
                    'Unlimited projects',
                    'Participant management with imports and exports',
                    'Organization members and role-based permissions',
                    'Event calendar with Google Calendar sync',
                    'Travel expenses tracking',
                    'Activity log across projects and organization',
                    'Support tickets and feedback',
                    'Everything in Basic and many more',
                    'Possibility of white-label',
                    'Custom project features',
                    'Custom project availability count',
                ],
            ],
        ],
        'contact_sales' => [
            'heading' => 'Talk to sales',
            'description' => 'Tell us a bit about your team and we\'ll be in touch with a tailored quote.',
            'subject_default' => 'Corporate enquiry',
            'subject_placeholder' => 'Subject',
            'body_placeholder' => 'How can we help?',
            'submit' => 'Send',
            'sent' => 'Message sent',
        ],
    ],
];
