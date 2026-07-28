<?php

return [

    'title' => 'Register',

    'heading' => 'One step away from managing your Erasmus projects',

    'code' => [

        'subheading' => 'Enter the code we just sent to <strong>:email</strong>.',

    ],

    'actions' => [

        'login' => [
            'before' => 'Already have an account?',
            'label' => 'Sign in',
        ],

    ],

    'consent' => [

        'before' => 'By joining you agree to our',

        'between' => 'and',

        'terms' => [
            'label' => '<bold>Terms of Service</bold>',
        ],

        'privacy' => [
            'label' => '<bold>Privacy Policy</bold>',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'Email',
        ],

        'name' => [
            'label' => 'Name',
        ],

        'code' => [
            'label' => 'One-time code',
            'helper_text' => 'We sent a 6-digit code to :email',
        ],

        'password' => [
            'label' => 'Password',
            'validation_attribute' => 'password',
        ],

        'password_confirmation' => [
            'label' => 'Confirm password',
        ],

        'actions' => [

            'register' => [
                'label' => 'Continue with email',
            ],

            'authenticate' => [
                'label' => 'Sign in',
            ],

            'resend_code' => [
                'label' => 'Resend code',
            ],

        ],

    ],

    'messages' => [

        'session_expired' => 'Session expired. Please register again.',

        'account_missing' => 'Account not found.',

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Too many registration attempts',
            'body' => 'Please try again in :seconds seconds.',
        ],

        'code_resent' => [
            'title' => 'A new code is on the way.',
        ],

    ],

];
 