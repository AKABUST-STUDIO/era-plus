<?php

return [

    'login_code' => [

        'subject' => 'Your sign-in code: :code',

        'heading' => 'Sign in to :app',

        'intro' => 'Use this one-time code to sign in:',

        'alternative' => 'Or click the button below to sign in directly:',

        'action' => 'Sign in',

        'expiry' => 'The code and link expire in :minutes minutes. If you didn\'t request this, you can safely ignore this email.',

        'outro' => 'Thanks,',

    ],

    'registration_code' => [

        'subject' => 'Confirm your :app account: :code',

        'heading' => 'Confirm your email',

        'intro' => 'Welcome to :app. Use this one-time code to confirm :email and finish setting up your account:',

        'alternative' => 'Or click the button below to confirm directly:',

        'action' => 'Confirm email',

        'expiry' => 'The code and link expire in :minutes minutes. If you didn\'t sign up, you can safely ignore this email.',

        'outro' => 'Thanks,',

    ],

    'account_already_exists' => [

        'subject' => 'You already have a :app account',

        'heading' => 'You already have an account',

        'intro' => 'Someone tried to register :email with :app, but an account already exists for that address.',

        'code' => 'If that was you, use this one-time code to sign in instead:',

        'alternative' => 'Or click the button below to sign in directly:',

        'action' => 'Sign in',

        'expiry' => 'The code and link expire in :minutes minutes.',

        'ignore' => 'If it wasn\'t you, you can safely ignore this email — nobody gained access to anything.',

        'outro' => 'Thanks,',

    ],

    'missing_account' => [

        'subject' => 'Sign-in attempt for :app',

        'heading' => 'No account for this email',

        'intro' => 'Someone tried to sign in to :app with :email, but there is no account for that address.',

        'create' => 'If that was you, create an account to get started:',

        'action' => 'Create an account',

        'ignore' => 'If it wasn\'t you, you can safely ignore this email — nobody gained access to anything.',

        'outro' => 'Thanks,',

    ],

    'organization_invitation' => [

        'subject' => 'You\'ve been added to :organization',

        'heading' => 'You\'ve been added to :organization',

        'intro' => ':inviter added you to **:organization** as **:role**.',

        'sign_in_intro' => 'Sign in with your email to open the workspace. We\'ll send you a one-time code — no password required.',

        'sign_in_action' => 'Sign in',

        'ignore' => 'If you weren\'t expecting this, you can safely ignore the email.',

        'outro' => 'Thanks,',

        'default_inviter' => 'A coordinator',

    ],

    'feedback_received' => [

        'subject' => 'New feedback: :subject',

        'heading' => 'New feedback received',

        'from' => 'From',

        'subject_label' => 'Subject',

        'rating' => 'Rating',

        'outro' => 'Thanks,',

    ],

    'support_request_received' => [

        'subject' => 'New support request: :subject',

        'heading' => 'New support request',

        'from' => 'From',

        'subject_label' => 'Subject',

        'body_label' => 'Message',

        'outro' => 'Thanks,',

    ],

    'welcome' => [

        'subject' => 'Welcome to :app',

        'heading' => 'Welcome to :app',

        'intro' => 'Hi :name, your account is ready.',

        'body' => ':app helps you run your projects, track finances and keep your team in sync.',

        'action' => 'Open :app',

        'help' => 'If you have any questions, just reply to this email.',

        'outro' => 'Thanks,',

    ],

];
