<?php

return [

    'title' => 'Login',

    'heading' => 'Sign in to :app',

    'code' => [

        'heading' => 'Check your email',

        'subheading' => 'If you have an :app account we just sent to a code to <strong>:email</strong>.',
    ],

    'actions' => [

        'register' => [
            'before' => 'Don\'t have an account?',
            'label' => 'Sign up',
        ],

        'request_password_reset' => [
            'label' => 'Forgot password?',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'Email',
        ],

        'code' => [
            'label' => 'One-time code',
            'helper_text' => 'We sent a 6-digit code to :email',
        ],

        'password' => [
            'label' => 'Password',
        ],

        'remember' => [
            'label' => 'Remember me',
        ],

        'actions' => [

            'request_code' => [
                'label' => 'Continue with email',
            ],

            'authenticate' => [
                'label' => 'Sign in',
            ],

            'resend_code' => [
                'label' => 'Resend code',
            ],

            'use_different_email' => [
                'label' => 'Use a different email',
            ],

            'passkey' => [
                'label' => 'Sign in with a passkey',
            ],

            'oauth' => [

                'google' => [
                    'label' => 'Continue with Google',
                ],

                'microsoft' => [
                    'label' => 'Continue with Microsoft',
                ],

                'apple' => [
                    'label' => 'Continue with Apple',
                ],

            ],

        ],

    ],

    'multi_factor' => [

        'heading' => 'Verify your identity',

        'subheading' => 'To continue signing in, you need to verify your identity.',

        'form' => [

            'provider' => [
                'label' => 'How would you like to verify?',
            ],

            'actions' => [

                'authenticate' => [
                    'label' => 'Confirm sign in',
                ],

            ],

        ],

    ],

    'messages' => [

        'failed' => 'These credentials do not match our records.',

        'session_expired' => 'Session expired. Please request a new code.',

        'account_missing' => 'Account no longer exists.',

        'passkey_failed' => 'Passkey sign-in failed',

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Too many login attempts',
            'body' => 'Please try again in :seconds seconds.',
        ],

    ],

];
