<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for optimizing API performance for high-traffic scenarios
    |
    */

    'cache' => [
        'user_feed_ttl' => env('CACHE_USER_FEED_TTL', 120), // 2 minutes
        'user_stats_ttl' => env('CACHE_USER_STATS_TTL', 300), // 5 minutes
        'notifications_ttl' => env('CACHE_NOTIFICATIONS_TTL', 60), // 1 minute
        'business_accounts_ttl' => env('CACHE_BUSINESS_ACCOUNTS_TTL', 180), // 3 minutes
        'popular_tags_ttl' => env('CACHE_POPULAR_TAGS_TTL', 600), // 10 minutes
        'trending_posts_ttl' => env('CACHE_TRENDING_POSTS_TTL', 300), // 5 minutes
    ],

    'pagination' => [
        'max_per_page' => env('MAX_PER_PAGE', 50),
        'default_per_page' => env('DEFAULT_PER_PAGE', 20),
    ],

    'rate_limiting' => [
        'api_requests_per_minute' => env('API_RATE_LIMIT', 60),
        'auth_requests_per_minute' => env('AUTH_RATE_LIMIT', 10),
        'upload_requests_per_minute' => env('UPLOAD_RATE_LIMIT', 5),
    ],

    'database' => [
        'connection_timeout' => env('DB_CONNECTION_TIMEOUT', 10),
        'query_timeout' => env('DB_QUERY_TIMEOUT', 30),
        'max_connections' => env('DB_MAX_CONNECTIONS', 100),
    ],

    'notifications' => [
        'batch_size' => env('NOTIFICATION_BATCH_SIZE', 100),
        'queue_timeout' => env('NOTIFICATION_QUEUE_TIMEOUT', 30),
        'max_retries' => env('NOTIFICATION_MAX_RETRIES', 3),
    ],

    'optimization' => [
        'enable_query_cache' => env('ENABLE_QUERY_CACHE', true),
        'enable_response_cache' => env('ENABLE_RESPONSE_CACHE', true),
        'enable_background_jobs' => env('ENABLE_BACKGROUND_JOBS', true),
        'warm_up_caches' => env('WARM_UP_CACHES', true),
    ],
];
