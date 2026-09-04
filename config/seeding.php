<?php

return [
    'users' => (int) env('SEED_USERS', 10000),
    'posts' => (int) env('SEED_POSTS', 100000),
    'follows' => (int) env('SEED_FOLLOWS', 200000),
    'likes' => (int) env('SEED_LIKES', 500000),
    'chunk_size' => (int) env('SEED_CHUNK_SIZE', 1000),
];
