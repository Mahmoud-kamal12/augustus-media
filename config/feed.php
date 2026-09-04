<?php

return [
    'pagination' => [
        'default_per_page' => (int) env('FEED_DEFAULT_PER_PAGE', 20),
        'max_per_page' => (int) env('FEED_MAX_PER_PAGE', 50),
    ],

    'cache' => [
        'first_page_ttl' => (int) env('FEED_FIRST_PAGE_TTL', 30),
        'like_count_ttl' => (int) env('LIKE_COUNT_TTL', 10),
    ],
];
