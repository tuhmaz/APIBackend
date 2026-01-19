<?php

return [
    // Enable/disable heavy visitor tracking globally
    'visitor_tracking_enabled' => env('VISITOR_TRACKING_ENABLED', true),

    // Sample only 1 out of N requests for heavy tracking work (UA analysis, geo)
    'visitor_sampling_rate' => (int) env('VISITOR_SAMPLING_RATE', 10),

    // Minimum seconds between DB writes per IP for VisitorTracking
    'visitor_write_debounce_seconds' => (int) env('VISITOR_WRITE_DEBOUNCE', 60),

    // Cache TTL (seconds) for IP geolocation results
    'geo_cache_ttl' => (int) env('VISITOR_GEO_CACHE_TTL', 86400), // 1 day

    // Debounce VisitorSession log per session/IP (seconds)
    'visitor_session_log_debounce' => (int) env('VISITOR_SESSION_LOG_DEBOUNCE', 30),

    // Minimum minutes between last_activity updates per user
    'user_last_activity_minutes' => (int) env('USER_LAST_ACTIVITY_MINUTES', 1),
];
