<?php

return [
    'front' => [
        'max_age' => env('FRONT_CACHE_MAX_AGE', 900),
        'stale_while_revalidate' => env('FRONT_CACHE_STALE_WHILE_REVALIDATE', 120),
        'stale_if_error' => env('FRONT_CACHE_STALE_IF_ERROR', 600),
    ],
];
