<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase services including Cloud Messaging
    |
    */

    'server_key' => env('FIREBASE_SERVER_KEY'),
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'service_account_key' => env('FIREBASE_SERVICE_ACCOUNT_KEY'),

    /*
    |--------------------------------------------------------------------------
    | FCM Settings
    |--------------------------------------------------------------------------
    */

    'fcm' => [
        'server_key' => env('FIREBASE_SERVER_KEY'),
        'url' => 'https://fcm.googleapis.com/fcm/send',
    ],
];
