<?php

return [
    'pagination' => [
        'default_per_page' => (int) env('FEED_DEFAULT_PER_PAGE', 20),
        'max_per_page' => (int) env('FEED_MAX_PER_PAGE', 50),
    ],

    'cache' => [
        'first_page_ttl' => (int) env('FEED_FIRST_PAGE_TTL', 30),
    ],

    'notifications' => [
        'chunk_size' => (int) env('NOTIFICATION_CHUNK_SIZE', 1000),
        'tries' => (int) env('NOTIFICATION_JOB_TRIES', 3),
        'socket_enabled' => filter_var(env('NOTIFICATION_SOCKET_ENABLED', false), FILTER_VALIDATE_BOOL),
    ],
];
