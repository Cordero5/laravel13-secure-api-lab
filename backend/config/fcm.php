<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    |
    | Used to notify tenants when an administrator publishes an announcement.
    | Fill these in from your Firebase project settings; while they are empty
    | the app logs a skip instead of attempting a request, so announcement
    | publishing keeps working before push is configured.
    |
    | server_key : legacy FCM server key (Project settings -> Cloud Messaging)
    | project_id : Firebase project id, required for the HTTP v1 endpoint
    |
    */

    'server_key' => env('FCM_SERVER_KEY'),

    'project_id' => env('FCM_PROJECT_ID'),

    'credentials_path' => env('FCM_CREDENTIALS_PATH'),

    'topic' => env('FCM_TOPIC', 'boarding_house_announcements'),

    'timeout' => (int) env('FCM_TIMEOUT', 5),

    'enabled' => (bool) env('FCM_ENABLED', false),

];
